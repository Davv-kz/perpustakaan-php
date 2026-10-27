<?php
session_start();
include "koneksi.php";

// =====================================
// CEK LOGIN DAN HAK AKSES
// =====================================
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$level = $_SESSION['level'] ?? '';
$is_admin = ($level === 'admin');
$is_pengguna = ($level === 'anggota');

if (!$is_admin && !$is_pengguna) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

if ($is_pengguna && empty($_SESSION['NIS'])) {
    die("NIS akun tidak ditemukan. Silakan login ulang.");
}

$kembali = $is_admin ? "admin.php" : "dashboard.php";
$error = "";
$success = "";

// =====================================
// PROSES PEMINJAMAN
// ADMIN HANYA BOLEH MELIHAT DATA
// =====================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_admin) {
        http_response_code(403);
        exit("Akses ditolak. Admin hanya boleh melihat data peminjaman.");
    }

    if (!$is_pengguna || !isset($_POST['simpan_peminjaman'])) {
        http_response_code(400);
        exit("Permintaan tidak valid.");
    }

    $id_buku = (int) ($_POST['id_buku'] ?? 0);
    $stok_pinjam = (int) ($_POST['stok'] ?? 0);
    $tanggal_peminjaman = $_POST['tanggal_peminjaman'] ?? date('Y-m-d');
    $tanggal_pengembalian = $_POST['tanggal_pengembalian'] ?? '';

    if (
        $id_buku <= 0 ||
        $stok_pinjam <= 0 ||
        $tanggal_peminjaman !== date('Y-m-d') ||
        $tanggal_pengembalian === '' ||
        $tanggal_pengembalian < $tanggal_peminjaman
    ) {
        $error = "Data peminjaman tidak valid.";
    } else {
        mysqli_begin_transaction($koneksi);

        try {
            // Periksa stok buku
            $stmt_buku = mysqli_prepare(
                $koneksi,
                "SELECT stok
                 FROM data_buku
                 WHERE id_buku = ?
                 FOR UPDATE"
            );

            mysqli_stmt_bind_param($stmt_buku, "i", $id_buku);
            mysqli_stmt_execute($stmt_buku);

            $hasil_buku = mysqli_stmt_get_result($stmt_buku);
            $buku = mysqli_fetch_assoc($hasil_buku);

            if (!$buku) {
                throw new Exception("Buku tidak ditemukan.");
            }

            if ((int) $buku['stok'] < $stok_pinjam) {
                throw new Exception("Stok buku tidak mencukupi.");
            }

            // Kurangi stok buku
            $stmt_update = mysqli_prepare(
                $koneksi,
                "UPDATE data_buku
                 SET stok = stok - ?
                 WHERE id_buku = ?"
            );

            mysqli_stmt_bind_param(
                $stmt_update,
                "ii",
                $stok_pinjam,
                $id_buku
            );

            if (!mysqli_stmt_execute($stmt_update)) {
                throw new Exception("Gagal memperbarui stok buku.");
            }

            // Simpan data peminjaman
            $nis = $_SESSION['NIS'];
            $status = "Dipinjam";

            $stmt_pinjam = mysqli_prepare(
                $koneksi,
                "INSERT INTO peminjaman
                (NIS, id_buku, stok, tanggal_peminjaman,
                 tanggal_pengembalian, status)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt_pinjam,
                "siisss",
                $nis,
                $id_buku,
                $stok_pinjam,
                $tanggal_peminjaman,
                $tanggal_pengembalian,
                $status
            );

            if (!mysqli_stmt_execute($stmt_pinjam)) {
                throw new Exception("Gagal menyimpan peminjaman.");
            }

            mysqli_commit($koneksi);

            header("Location: peminjaman.php?berhasil=1");
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            $error = $e->getMessage();
        }
    }
}

if (isset($_GET['berhasil'])) {
    $success = "Peminjaman berhasil disimpan.";
}

// =====================================
// DAFTAR BUKU
// =====================================
$daftar_buku = mysqli_query(
    $koneksi,
    "SELECT id_buku, judul_buku, stok
     FROM data_buku
     ORDER BY judul_buku ASC"
);

if (!$daftar_buku) {
    die("Gagal mengambil data buku: " . mysqli_error($koneksi));
}

// =====================================
// RIWAYAT PEMINJAMAN
// =====================================
if ($is_admin) {
    $peminjaman = mysqli_query(
        $koneksi,
        "SELECT p.*, a.nama_anggota, b.judul_buku
         FROM peminjaman p
         LEFT JOIN anggota a ON p.NIS = a.NIS
         LEFT JOIN data_buku b ON p.id_buku = b.id_buku
         ORDER BY p.id_peminjaman DESC"
    );
} else {
    $stmt_riwayat = mysqli_prepare(
        $koneksi,
        "SELECT p.*, a.nama_anggota, b.judul_buku
         FROM peminjaman p
         LEFT JOIN anggota a ON p.NIS = a.NIS
         LEFT JOIN data_buku b ON p.id_buku = b.id_buku
         WHERE p.NIS = ?
         ORDER BY p.id_peminjaman DESC"
    );

    mysqli_stmt_bind_param(
        $stmt_riwayat,
        "s",
        $_SESSION['NIS']
    );

    mysqli_stmt_execute($stmt_riwayat);
    $peminjaman = mysqli_stmt_get_result($stmt_riwayat);
}

if (!$peminjaman) {
    die("Gagal mengambil data peminjaman: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Peminjaman</title>

    <link rel="stylesheet" href="styleperpustakaan.css">

    <style>
        .btn-kembali {
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
        }

        .btn-kembali:hover {
            background: #1d4ed8;
        }

        .pesan-error {
            color: #b91c1c;
            margin: 12px 0;
        }

        .pesan-sukses {
            color: #15803d;
            margin: 12px 0;
        }

        /* WARNA STATUS */
        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        /* STATUS DIPINJAM - OREN */
        .status-pinjam {
            background: #ffedd5;
            color: #9a3412;
        }

        /* STATUS SUDAH DIKEMBALIKAN - HIJAU */
        .status-kembali {
            background: #dcfce7;
            color: #166534;
        }

        .tabel-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>Data Peminjaman Buku</h2>

    <?php if ($error !== ''): ?>
        <p class="pesan-error">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <p class="pesan-sukses">
            <?= htmlspecialchars($success) ?>
        </p>
    <?php endif; ?>

    <?php if ($is_pengguna): ?>

        <h3>Form Peminjaman</h3>

        <form method="POST" action="peminjaman.php">

            <label>Buku</label>

            <select name="id_buku" required>
                <option value="">-- Pilih Buku --</option>

                <?php while ($buku = mysqli_fetch_assoc($daftar_buku)): ?>
                    <?php if ((int) $buku['stok'] > 0): ?>
                        <option value="<?= (int) $buku['id_buku'] ?>">
                            <?= htmlspecialchars($buku['judul_buku']) ?>
                            (Stok: <?= (int) $buku['stok'] ?>)
                        </option>
                    <?php endif; ?>
                <?php endwhile; ?>

            </select>

            <label>Jumlah Buku</label>

            <input
                type="number"
                name="stok"
                min="1"
                value="1"
                required
            >

            <label>Tanggal Peminjaman</label>

            <input
                type="date"
                name="tanggal_peminjaman"
                value="<?= date('Y-m-d') ?>"
                required
            >

            <label>Tanggal Pengembalian</label>

            <input
                type="date"
                name="tanggal_pengembalian"
                min="<?= date('Y-m-d') ?>"
                required
            >

            <button type="submit" name="simpan_peminjaman">
                Pinjam Buku
            </button>

        </form>

    <?php endif; ?>

    <h3>Riwayat Peminjaman</h3>

    <div class="tabel-container">
        <table border="1" cellpadding="8" cellspacing="0">

            <thead>
                <tr>
                    <th>ID Peminjaman</th>
                    <th>NIS</th>
                    <th>Nama Anggota</th>
                    <th>Judul Buku</th>
                    <th>Jumlah</th>
                    <th>Tanggal Peminjaman</th>
                    <th>Batas Pengembalian</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

                <?php if (mysqli_num_rows($peminjaman) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($peminjaman)): ?>

                        <tr>
                            <td>
                                <?= (int) $row['id_peminjaman'] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['NIS'] ?? '-') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['nama_anggota'] ?? '-') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['judul_buku'] ?? '-') ?>
                            </td>

                            <td>
                                <?= (int) ($row['stok'] ?? 1) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['tanggal_peminjaman']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['tanggal_pengembalian']) ?>
                            </td>

                            <td>
                                <?php
                                $status = $row['status'] ?? 'Dipinjam';

                                if ($status === 'Dipinjam') {
                                    $kelas_status = 'status-pinjam';
                                } else {
                                    $kelas_status = 'status-kembali';
                                }
                                ?>

                                <span class="status <?= $kelas_status ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8">Belum ada data peminjaman.</td>
                    </tr>

                <?php endif; ?>

            </tbody>
        </table>
    </div>

</div>

<br>

<a href="<?= htmlspecialchars($kembali) ?>" class="kembali">
    ⬅️ Kembali
</a>

</body>
</html>