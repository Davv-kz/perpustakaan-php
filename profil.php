<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$pesan = "";
$jenis_pesan = "";

if (empty($_SESSION['token_profil'])) {
    $_SESSION['token_profil'] = bin2hex(random_bytes(32));
}

// Ambil data akun yang sedang login
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT username, nama_lengkap, level, foto_profil
     FROM login WHERE username = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$akun = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$akun) {
    die("Data akun tidak ditemukan.");
}

// Upload foto profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['token']) ||
        !hash_equals($_SESSION['token_profil'], $_POST['token'])
    ) {
        $pesan = "Permintaan tidak valid. Muat ulang halaman.";
        $jenis_pesan = "error";
    } elseif (
        !isset($_FILES['foto']) ||
        $_FILES['foto']['error'] !== UPLOAD_ERR_OK
    ) {
        $pesan = "Pilih foto terlebih dahulu.";
        $jenis_pesan = "error";
    } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
        $pesan = "Ukuran foto maksimal 2 MB.";
        $jenis_pesan = "error";
    } else {
        $tmp = $_FILES['foto']['tmp_name'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);

        $tipe = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        if (
            !is_uploaded_file($tmp) ||
            !isset($tipe[$mime]) ||
            @getimagesize($tmp) === false
        ) {
            $pesan = "Gunakan foto JPG, PNG, atau WEBP.";
            $jenis_pesan = "error";
        } else {
            $folder = __DIR__ . "/uploads/profil/";

            if (!is_dir($folder)) {
                mkdir($folder, 0755, true);
            }

            if (!is_dir($folder) || !is_writable($folder)) {
                $pesan = "Folder uploads/profil tidak dapat ditulisi.";
                $jenis_pesan = "error";
            } else {
                $nama_file = bin2hex(random_bytes(16))
                    . "." . $tipe[$mime];

                $lokasi = "uploads/profil/" . $nama_file;
                $tujuan = $folder . $nama_file;

                if (move_uploaded_file($tmp, $tujuan)) {
                    $stmt = mysqli_prepare(
                        $koneksi,
                        "UPDATE login SET foto_profil = ?
                         WHERE username = ?"
                    );

                    mysqli_stmt_bind_param(
                        $stmt, "ss", $lokasi, $username
                    );

                    if (mysqli_stmt_execute($stmt)) {
                        $foto_lama = $akun['foto_profil'] ?? '';

                        if (
                            strpos($foto_lama, 'uploads/profil/') === 0 &&
                            basename($foto_lama) ===
                                substr($foto_lama, strlen('uploads/profil/'))
                        ) {
                            $file_lama = __DIR__ . "/" . $foto_lama;

                            if (is_file($file_lama)) {
                                @unlink($file_lama);
                            }
                        }

                        $akun['foto_profil'] = $lokasi;
                        $pesan = "Foto profil berhasil diperbarui!";
                        $jenis_pesan = "success";
                    } else {
                        @unlink($tujuan);
                        $pesan = "Gagal menyimpan foto ke database.";
                        $jenis_pesan = "error";
                    }

                    mysqli_stmt_close($stmt);
                } else {
                    $pesan = "Gagal mengunggah foto.";
                    $jenis_pesan = "error";
                }
            }
        }
    }
}

function aman($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

$foto = $akun['foto_profil'] ?? '';
$foto_valid = (
    $foto !== '' &&
    strpos($foto, 'uploads/profil/') === 0 &&
    basename($foto) === substr($foto, strlen('uploads/profil/')) &&
    is_file(__DIR__ . "/" . $foto)
);

$nama = $akun['nama_lengkap'] ?: $akun['username'];
$inisial = strtoupper(substr($nama, 0, 1));
$level = $_SESSION['level'] ?? $akun['level'] ?? '';
$nis = $_SESSION['NIS'] ?? '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Profil Saya - Perpustakaan</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 25px 15px;
    font-family: Arial, sans-serif;
    background: #f3f6fb;
    color: #263248;
}

.container {
    max-width: 760px;
    margin: auto;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    gap: 12px;
}

.header h1 {
    font-size: 25px;
    margin: 0;
}

.back {
    color: #2563eb;
    text-decoration: none;
    font-weight: bold;
}

.card {
    background: white;
    padding: 26px;
    border-radius: 18px;
    box-shadow: 0 5px 24px #17255412;
    margin-bottom: 20px;
}

.profile-top {
    display: flex;
    align-items: center;
    gap: 20px;
    padding-bottom: 24px;
    margin-bottom: 22px;
    border-bottom: 1px solid #e5eaf2;
}

.avatar {
    width: 110px;
    height: 110px;
    flex-shrink: 0;
    border-radius: 50%;
    object-fit: cover;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #dbeafe;
    color: #1d4ed8;
    font-size: 40px;
    font-weight: bold;
}

.profile-top h2 {
    margin: 0 0 8px;
    overflow-wrap: anywhere;
}

.muted {
    color: #64748b;
    font-size: 14px;
}

.role {
    display: inline-block;
    margin-top: 10px;
    padding: 6px 12px;
    border-radius: 20px;
    background: #dbeafe;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: bold;
}

.info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.info-item {
    padding: 15px;
    background: #f8fafc;
    border-radius: 10px;
    min-width: 0;
}

.info-item strong {
    display: block;
    margin-top: 8px;
    overflow-wrap: anywhere;
}

input[type="file"] {
    width: 100%;
    margin: 18px 0;
    padding: 12px;
    border: 1px solid #dbe2ea;
    border-radius: 9px;
}

button {
    width: 100%;
    padding: 13px;
    border: none;
    border-radius: 9px;
    background: #2563eb;
    color: white;
    font-weight: bold;
    font-size: 15px;
    cursor: pointer;
}

button:hover {
    background: #1d4ed8;
}

.pesan {
    padding: 13px;
    margin-bottom: 18px;
    border-radius: 9px;
}

.success {
    background: #dcfce7;
    color: #166534;
}

.error {
    background: #fee2e2;
    color: #991b1b;
}

@media (max-width: 480px) {
    .card {
        padding: 18px;
    }

    .avatar {
        width: 80px;
        height: 80px;
        font-size: 30px;
    }

    .info {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>
<div class="container">

    <div class="header">
        <h1>Profil Saya</h1>
    </div>

    <?php if ($pesan !== ''): ?>
        <div class="pesan <?= aman($jenis_pesan) ?>">
            <?= aman($pesan) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="profile-top">
            <?php if ($foto_valid): ?>
                <img class="avatar" src="<?= aman($foto) ?>" alt="Foto profil">
            <?php else: ?>
                <div class="avatar"><?= aman($inisial) ?></div>
            <?php endif; ?>

            <div>
                <h2><?= aman($nama) ?></h2>
                <div class="muted">@<?= aman($akun['username']) ?></div>
                <span class="role"><?= aman(strtoupper($level)) ?></span>
            </div>
        </div>

        <h3>Informasi Akun</h3>

        <div class="info">
            <div class="info-item">
                <span class="muted">Nama Lengkap</span>
                <strong><?= aman($nama) ?></strong>
            </div>

            <div class="info-item">
                <span class="muted">Username</span>
                <strong><?= aman($akun['username']) ?></strong>
            </div>

            <div class="info-item">
                <span class="muted">Hak Akses</span>
                <strong><?= aman(ucfirst($level)) ?></strong>
            </div>

            <div class="info-item">
                <span class="muted">NIS</span>
                <strong><?= $level === 'anggota' ? aman($nis) : '-' ?></strong>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Ganti Foto Profil</h3>
        <p class="muted">
            Format JPG, PNG, atau WEBP. Ukuran maksimal 2 MB.
        </p>

        <form method="POST" enctype="multipart/form-data">
            <input
                type="hidden"
                name="token"
                value="<?= aman($_SESSION['token_profil']) ?>"
            >

            <input
                type="file"
                name="foto"
                accept=".jpg,.jpeg,.png,.webp"
                required
            >

            <button type="submit">Unggah Foto Profil</button>
        </form>
    </div>

    <a href="dashboard.php" style="
    display: inline-block;
            margin: 12px 0;
            padding: 10px 18px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            transition: background .2s ease;
"
>
    Kembali ke Dashboard
</a>
</div>
</body>
</html>