<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

include "koneksi.php";

$nama = $_SESSION['nama_lengkap'] ?? $_SESSION['username'];
$username = $_SESSION['username'];
$level = $_SESSION['level'] ?? 'anggota';

// Mengambil foto profil pengguna yang sedang login
$stmt_foto = mysqli_prepare(
    $koneksi,
    "SELECT foto_profil FROM login WHERE username = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt_foto, "s", $username);
mysqli_stmt_execute($stmt_foto);
$hasil_foto = mysqli_stmt_get_result($stmt_foto);
$data_foto = mysqli_fetch_assoc($hasil_foto);
$foto_profil = $data_foto['foto_profil'] ?? '';
mysqli_stmt_close($stmt_foto);

// Validasi lokasi foto
if (
    $foto_profil === '' ||
    strpos($foto_profil, 'uploads/profil/') !== 0 ||
    basename($foto_profil) !== substr(
        $foto_profil,
        strlen('uploads/profil/')
    ) ||
    !is_file(__DIR__ . '/' . $foto_profil)
) {
    $foto_profil = '';
}

// Menghitung statistik perpustakaan
$jml_anggota = mysqli_fetch_assoc(
    mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM anggota")
)['total'];

$jml_buku = mysqli_fetch_assoc(
    mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM data_buku")
)['total'];

$jml_peminjaman = mysqli_fetch_assoc(
    mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM peminjaman")
)['total'];

$jml_pengembalian = mysqli_fetch_assoc(
    mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pengembalian")
)['total'];

$jml_pengurus = mysqli_fetch_assoc(
    mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM data_pengurus")
)['total'];

function h($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

$inisial = strtoupper(substr($nama, 0, 1));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Perpustakaan</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f1f5f9;
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 240px;
            background: linear-gradient(180deg, #172554, #2563eb);
            color: white;
            min-height: 100vh;
            padding: 25px 15px;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            overflow-y: auto;
        }

        .logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .icon-logo {
            font-size: 45px;
            margin-bottom: 5px;
        }

        .logo h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .logo p {
            font-size: 11px;
            color: #dbeafe;
        }

        .judul-menu {
            font-size: 11px;
            color: #bfdbfe;
            margin: 20px 10px 10px;
            font-weight: bold;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            padding: 13px 15px;
            margin-bottom: 7px;
            border-radius: 10px;
            font-size: 14px;
            transition: 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(255, 255, 255, 0.20);
            transform: scale(1.03);
        }

        .content {
            margin-left: 240px;
            width: calc(100% - 240px);
            padding: 30px;
            min-width: 0;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .top-header h1 {
            color: #172554;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .top-header p {
            color: #64748b;
        }

        /* Profil pengguna di kanan atas */
        .user-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            padding: 8px 14px 8px 8px;
            border-radius: 30px;
            color: #172554;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .08);
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: 0.2s;
            flex-shrink: 0;
            max-width: 260px;
        }

        .user-badge:hover {
            background: #eff6ff;
            transform: translateY(-2px);
        }

        .foto-navbar,
        .avatar-navbar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .foto-navbar {
            object-fit: cover;
        }

        .avatar-navbar {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #2563eb;
            color: white;
            font-size: 18px;
        }

        .nama-navbar {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .panah-profil {
            font-size: 24px;
            color: #64748b;
        }

        .welcome {
            background: linear-gradient(100deg, #1685d8, #2563eb);
            color: white;
            padding: 28px;
            border-radius: 18px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .10);
        }

        .welcome h2 {
            font-size: 23px;
            margin-bottom: 8px;
        }

        .welcome p {
            color: #e0f2fe;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 18px;
        }

        .card {
            background: white;
            padding: 23px 15px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .07);
            transition: 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, .15);
        }

        .icon {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .card h3 {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .jumlah {
            font-size: 28px;
            font-weight: bold;
            color: #172554;
        }

        .menu {
            margin-top: 25px;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .07);
        }

        .menu h2 {
            color: #172554;
            font-size: 20px;
            margin-bottom: 18px;
        }

        .menu-links {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .menu a {
            display: inline-block;
            text-decoration: none;
            background: #2563eb;
            color: white;
            padding: 11px 17px;
            border-radius: 8px;
            font-size: 13px;
            transition: 0.3s;
        }

        .menu a:hover {
            background: #1d4ed8;
            transform: scale(1.03);
        }

        .logout {
            background: #dc2626 !important;
            margin-top: 20px;
            justify-content: center;
        }

        .logout:hover {
            background: #b91c1c !important;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            color: #64748b;
            font-size: 12px;
        }

        @media (max-width: 1200px) {
            .cards {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .sidebar {
                width: 200px;
            }

            .content {
                margin-left: 200px;
                width: calc(100% - 200px);
                padding: 20px;
            }

            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .top-header h1 {
                font-size: 24px;
            }
        }

        @media (max-width: 550px) {
            body {
                display: block;
            }

            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
                max-height: none;
            }

            .content {
                margin-left: 0;
                width: 100%;
                padding: 18px;
            }

            .top-header {
                flex-direction: row;
                align-items: center;
                gap: 10px;
            }

            .top-header h1 {
                font-size: 22px;
            }

            .top-header p {
                font-size: 12px;
            }

            .user-badge {
                gap: 6px;
                padding: 5px;
                max-width: 155px;
                font-size: 12px;
            }

            .foto-navbar,
            .avatar-navbar {
                width: 36px;
                height: 36px;
            }

            .panah-profil {
                font-size: 20px;
            }

            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .card {
                padding: 20px 8px;
            }

            .jumlah {
                font-size: 24px;
            }

            .welcome {
                padding: 22px;
            }

            .welcome h2 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar">

        <div class="logo">
            <div class="icon-logo">📚</div>
            <h2>PERPUSTAKAAN</h2>
            <h2>Sekolah</h2>
            <p>Sistem Informasi Perpustakaan Sekolah</p>
        </div>

        <div class="judul-menu">MENU UTAMA</div>

        <a href="dashboard.php" class="active">
            🏠 <span>Dashboard</span>
        </a>

        <a href="anggota.php">
            👥 <span>Data Anggota</span>
        </a>

        <a href="buku.php">
            📚 <span>Data Buku</span>
        </a>

        <a href="peminjaman.php">
            📖 <span>Peminjaman</span>
        </a>

        <a href="pengembalian.php">
            🔄 <span>Pengembalian</span>
        </a>

        <a href="pengurus.php">
            👤 <span>Data Pengurus</span>
        </a>

        <a href="index.php" class="logout">
            🚪 <span>Logout</span>
        </a>

    </div>

    <div class="content">

        <div class="top-header">

            <div>
                <h1>Dashboard</h1>
                <p>Selamat datang di sistem perpustakaan sekolah 📚</p>
            </div>

            <a href="profil.php" class="user-badge" title="Lihat profil saya">

                <?php if ($foto_profil !== ''): ?>

                    <img
                        src="<?= h($foto_profil) ?>"
                        alt="Foto profil"
                        class="foto-navbar"
                    >

                <?php else: ?>

                    <span class="avatar-navbar">
                        <?= h($inisial) ?>
                    </span>

                <?php endif; ?>

                <span class="nama-navbar"><?= h($nama) ?></span>
                <span class="panah-profil">›</span>

            </a>

        </div>

        <div class="welcome">

            <h2>Selamat Datang, <?= h($nama) ?>! 👋</h2>

            <p>
                Username:
                <b><?= h($username) ?></b>
                &nbsp; | &nbsp;
                Level:
                <b><?= h($level) ?></b>
            </p>

        </div>

        <div class="cards">

            <div class="card">
                <div class="icon">👥</div>
                <h3>Total Anggota</h3>
                <div class="jumlah"><?= h($jml_anggota) ?></div>
            </div>

            <div class="card">
                <div class="icon">📚</div>
                <h3>Total Buku</h3>
                <div class="jumlah"><?= h($jml_buku) ?></div>
            </div>

            <div class="card">
                <div class="icon">📖</div>
                <h3>Total Peminjaman</h3>
                <div class="jumlah"><?= h($jml_peminjaman) ?></div>
            </div>

            <div class="card">
                <div class="icon">🔄</div>
                <h3>Total Pengembalian</h3>
                <div class="jumlah"><?= h($jml_pengembalian) ?></div>
            </div>

            <div class="card">
                <div class="icon">👨‍💼</div>
                <h3>Total Pengurus</h3>
                <div class="jumlah"><?= h($jml_pengurus) ?></div>
            </div>

        </div>

        <div class="menu">

            <h2>Menu Perpustakaan</h2>

            <div class="menu-links">

                <a href="anggota.php">👥 Anggota</a>
                <a href="buku.php">📚 Data Buku</a>
                <a href="peminjaman.php">📖 Peminjaman</a>
                <a href="pengembalian.php">🔄 Pengembalian</a>
                <a href="pengurus.php">👤 Pengurus</a>

            </div>

        </div>

        <div class="footer">
            &#169; 2026 Kyy. All Rights Reserved
        </div>

    </div>

</body>
</html>