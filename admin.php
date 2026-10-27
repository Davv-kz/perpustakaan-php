<?php
session_start();
include "koneksi.php";

// ==========================
// CEK ADMIN
// ==========================
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: index.php");
    exit;
}

// ==========================
// FUNGSI JUMLAH DATA
// ==========================
function jumlahData($koneksi, $tabel)
{
    $query = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM `$tabel`");

    if (!$query) {
        return 0;
    }

    $data = mysqli_fetch_assoc($query);
    return $data['total'] ?? 0;
}

$total_anggota = jumlahData($koneksi, "anggota");
$total_buku = jumlahData($koneksi, "data_buku");
$total_peminjaman = jumlahData($koneksi, "peminjaman");
$total_pengembalian = jumlahData($koneksi, "pengembalian");
$total_pengurus = jumlahData($koneksi, "data_pengurus");

// ==========================
// HAPUS ANGGOTA
// ==========================
if (isset($_GET['hapus'])) {

    $nis = $_GET['hapus'];

    $stmt = mysqli_prepare(
        $koneksi,
        "DELETE FROM anggota WHERE NIS = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $nis);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: admin.php");
        exit;
    }

    die("Gagal menghapus data: " . mysqli_error($koneksi));
}

// ==========================
// UPDATE ANGGOTA
// ==========================
if (isset($_POST['update_anggota'])) {

    $nis = $_POST['NIS'];
    $nama = $_POST['nama_anggota'];
    $jurusan = $_POST['jurusan'];
    $alamat = $_POST['alamat'];

    $stmt = mysqli_prepare(
        $koneksi,
        "UPDATE anggota
         SET nama_anggota = ?, jurusan = ?, alamat = ?
         WHERE NIS = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $nama,
        $jurusan,
        $alamat,
        $nis
    );

    if (mysqli_stmt_execute($stmt)) {
        header("Location: admin.php");
        exit;
    }

    die("Gagal mengubah data: " . mysqli_error($koneksi));
}

// ==========================
// PROFIL ANGGOTA
// ==========================
$profil = null;

if (isset($_GET['profil'])) {

    $nis = $_GET['profil'];

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM anggota WHERE NIS = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $nis);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $profil = mysqli_fetch_assoc($result);
}

// ==========================
// DATA EDIT
// ==========================
$edit_data = null;

if (isset($_GET['edit'])) {

    $nis = $_GET['edit'];

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM anggota WHERE NIS = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $nis);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $edit_data = mysqli_fetch_assoc($result);
}

// ==========================
// AMBIL SEMUA ANGGOTA
// ==========================
$anggota = mysqli_query(
    $koneksi,
    "SELECT * FROM anggota ORDER BY NIS ASC"
);

if (!$anggota) {
    die("Gagal mengambil data anggota: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Perpustakaan</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #f1f5f9;
    min-height: 100vh;
}

/* ==========================
   HEADER
========================== */

.header {
    background: #172554;
    color: white;
    padding: 25px;
    text-align: center;
}

/* ==========================
   SIDEBAR
========================== */

.sidebar {
    width: 240px;
    background: #1e3a8a;
    color: white;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    padding: 25px 15px;
}

.sidebar h2 {
    text-align: center;
    margin-bottom: 30px;
    color: white;
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 13px 15px;
    margin-bottom: 8px;
    border-radius: 8px;
}

.sidebar a:hover {
    background: #2563eb;
}

/* ==========================
   CONTENT
========================== */

.main {
    margin-left: 240px;
    padding: 30px;
}

.container {
    width: 100%;
    max-width: 1200px;
    margin: auto;
}

/* ==========================
   CARD STATISTIK
========================== */

.cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,.1);
}

.stat-card h3 {
    color: #475569;
    margin-bottom: 10px;
}

.stat-card p {
    font-size: 30px;
    font-weight: bold;
    color: #172554;
}

/* ==========================
   CARD
========================== */

.card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,.1);
    margin-bottom: 25px;
}

h2 {
    color: #172554;
    margin-bottom: 20px;
}

/* ==========================
   TABLE
========================== */

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 950px;
}

th {
    background: #172554;
    color: white;
    padding: 12px;
}

td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: center;
}

/* ==========================
   BUTTON
========================== */

.btn {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 7px;
    color: white;
    text-decoration: none;
    margin: 2px;
    border: none;
    cursor: pointer;
}

.profil {
    background: #2563eb;
}

.edit {
    background: #f59e0b;
}

.hapus {
    background: #dc2626;
}

.kembali {
    background: #172554;
}

.simpan {
    background: #2563eb;
}

/* ==========================
   FORM
========================== */

input,
textarea {
    width: 100%;
    padding: 10px;
    margin: 7px 0 15px;
    border: 1px solid #ccc;
    border-radius: 7px;
}

textarea {
    min-height: 100px;
}

.profil-data {
    line-height: 2;
}

/* ==========================
   RESPONSIVE
========================== */

@media (max-width: 900px) {

    .sidebar {
        width: 200px;
    }

    .main {
        margin-left: 200px;
    }

    .cards {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 600px) {

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .main {
        margin-left: 0;
        padding: 15px;
    }

    .cards {
        grid-template-columns: 1fr;
    }

}

</style>

</head>

<body>

<!-- ==========================
     SIDEBAR
========================== -->

<div class="sidebar">

    <h2>📚 PERPUSTAKAAN</h2>

    <a href="admin.php">🏠 Dashboard</a>

    <a href="anggota.php">👥 Data Anggota</a>

    <a href="buku.php">📚 Data Buku</a>

    <a href="peminjaman.php">📖 Peminjaman</a>

    <a href="pengembalian.php">↩️ Pengembalian</a>

    <a href="pengurus.php">👨‍💼 Pengurus</a>

    <a href="index.php">🚪 Keluar</a>

</div>


<!-- ==========================
     MAIN
========================== -->

<div class="main">

<div class="container">


<div class="header">

    <h1>🔐 Admin Perpustakaan</h1>

</div>


<br>


<!-- ==========================
     STATISTIK
========================== -->

<div class="cards">

    <div class="stat-card">
        <h3>👥 Total Anggota</h3>
        <p><?= $total_anggota; ?></p>
    </div>

    <div class="stat-card">
        <h3>📚 Total Buku</h3>
        <p><?= $total_buku; ?></p>
    </div>

    <div class="stat-card">
        <h3>📖 Peminjaman</h3>
        <p><?= $total_peminjaman; ?></p>
    </div>

    <div class="stat-card">
        <h3>↩️ Pengembalian</h3>
        <p><?= $total_pengembalian; ?></p>
    </div>

</div>


<!-- ==========================
     PROFIL ANGGOTA
========================== -->

<?php if ($profil) { ?>

<div class="card">

    <h2>👤 Profil Anggota</h2>

    <div class="profil-data">

        <p>
            <strong>NIS:</strong>
            <?= htmlspecialchars($profil['NIS']); ?>
        </p>

        <p>
            <strong>Nama:</strong>
            <?= htmlspecialchars($profil['nama_anggota']); ?>
        </p>

        <p>
            <strong>Jurusan:</strong>
            <?= htmlspecialchars($profil['jurusan']); ?>
        </p>

        <p>
            <strong>Alamat:</strong>
            <?= htmlspecialchars($profil['alamat']); ?>
        </p>

    </div>

    <br>

    <a href="admin.php" class="btn kembali">
        ← Kembali
    </a>

</div>

<?php } ?>


<!-- ==========================
     FORM EDIT
========================== -->

<?php if ($edit_data) { ?>

<div class="card">

    <h2>✏️ Edit Anggota</h2>

    <form method="POST">

        <label>NIS</label>

        <input
            type="text"
            name="NIS"
            value="<?= htmlspecialchars($edit_data['NIS']); ?>"
            readonly
        >

        <label>Nama Anggota</label>

        <input
            type="text"
            name="nama_anggota"
            value="<?= htmlspecialchars($edit_data['nama_anggota']); ?>"
            required
        >

        <label>Jurusan</label>

        <input
            type="text"
            name="jurusan"
            value="<?= htmlspecialchars($edit_data['jurusan']); ?>"
            required
        >

        <label>Alamat</label>

        <textarea
            name="alamat"
            required
        ><?= htmlspecialchars($edit_data['alamat']); ?></textarea>

        <button
            type="submit"
            name="update_anggota"
            class="btn simpan"
        >
            💾 Simpan Perubahan
        </button>

        <a href="admin.php" class="btn kembali">
            Batal
        </a>

    </form>

</div>

<?php } ?>


<!-- ==========================
     SEMUA ANGGOTA
========================== -->

<div class="card">

    <h2>👥 Data Anggota</h2>

    <div class="table-wrapper">

        <table>

            <tr>

                <th>No</th>
                <th>NIS</th>
                <th>Nama Anggota</th>
                <th>Jurusan</th>
                <th>Alamat</th>
                <th>Aksi</th>

            </tr>

            <?php

            $no = 1;

            while ($row = mysqli_fetch_assoc($anggota)) {

            ?>

            <tr>

                <td>
                    <?= $no++; ?>
                </td>

                <td>
                    <?= htmlspecialchars($row['NIS']); ?>
                </td>

                <td>
                    <?= htmlspecialchars($row['nama_anggota']); ?>
                </td>

                <td>
                    <?= htmlspecialchars($row['jurusan']); ?>
                </td>

                <td>
                    <?= htmlspecialchars($row['alamat']); ?>
                </td>

                <td>

                    <a
                        href="admin.php?profil=<?= urlencode($row['NIS']); ?>"
                        class="btn profil"
                    >
                        👤 Profil
                    </a>

                    <a
                        href="admin.php?edit=<?= urlencode($row['NIS']); ?>"
                        class="btn edit"
                    >
                        ✏️ Edit
                    </a>

                    <a
                        href="admin.php?hapus=<?= urlencode($row['NIS']); ?>"
                        class="btn hapus"
                        onclick="return confirm('Yakin ingin menghapus anggota ini?')"
                    >
                        🗑️ Hapus
                    </a>

                </td>

            </tr>

            <?php } ?>

        </table>

    </div>

</div>


<!-- ==========================
     MENU LAINNYA
========================== -->

<div class="card">

    <h2>⚡ Menu Cepat</h2>

    <a href="buku.php" class="btn kembali">
        📚 Data Buku
    </a>

    <a href="peminjaman.php" class="btn kembali">
        📖 Peminjaman
    </a>

    <a href="pengembalian.php" class="btn kembali">
        ↩️ Pengembalian
    </a>

    <a href="pengurus.php" class="btn kembali">
        👨‍💼 Pengurus
    </a>

</div>


<!-- KELUAR -->

<a href="index.php" class="btn kembali">
    ← Keluar
</a>


</div>

</div>

</body>

</html>