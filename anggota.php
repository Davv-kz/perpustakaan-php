<?php
session_start();
include "koneksi.php";

// =========================
// CEK LOGIN
// =========================
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// =========================
// TENTUKAN ADMIN / PENGGUNA
// =========================
$is_admin = isset($_SESSION['admin']) && $_SESSION['admin'] === true;

if ($is_admin) {
    $kembali = "admin.php";
} else {
    $kembali = "dashboard.php";
}


/* =========================
   TAMBAH DATA ANGGOTA
========================= */
if ($is_admin && isset($_POST['simpan_anggota'])) {

    $nis = $_POST['NIS'];
    $nama = $_POST['nama_anggota'];
    $jurusan = $_POST['jurusan'];
    $alamat = $_POST['alamat'];

    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO anggota (NIS, nama_anggota, jurusan, alamat)
         VALUES (?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $nis,
        $nama,
        $jurusan,
        $alamat
    );

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>
                alert('Anggota berhasil ditambahkan!');
                window.location.href='anggota.php';
              </script>";
        exit;
    } else {
        echo "<script>
                alert('Gagal menambahkan anggota!');
                window.location.href='anggota.php';
              </script>";
        exit;
    }
}


/* =========================
   HAPUS DATA ANGGOTA
========================= */
if ($is_admin && isset($_GET['hapus'])) {

    $nis = $_GET['hapus'];

    $stmt = mysqli_prepare(
        $koneksi,
        "DELETE FROM anggota WHERE NIS = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $nis);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>
                alert('Anggota berhasil dihapus!');
                window.location.href='anggota.php';
              </script>";
        exit;
    }
}


/* =========================
   UPDATE DATA ANGGOTA
========================= */
if ($is_admin && isset($_POST['update_anggota'])) {

    $nis_lama = $_POST['nis_lama'];
    $nis = $_POST['NIS'];
    $nama = $_POST['nama_anggota'];
    $jurusan = $_POST['jurusan'];
    $alamat = $_POST['alamat'];

    $stmt = mysqli_prepare(
        $koneksi,
        "UPDATE anggota
         SET NIS = ?, nama_anggota = ?, jurusan = ?, alamat = ?
         WHERE NIS = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "sssss",
        $nis,
        $nama,
        $jurusan,
        $alamat,
        $nis_lama
    );

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>
                alert('Data anggota berhasil diperbarui!');
                window.location.href='anggota.php';
              </script>";
        exit;
    }
}


/* =========================
   DATA EDIT
========================= */
$data_edit = null;

if ($is_admin && isset($_GET['edit'])) {

    $nis_edit = $_GET['edit'];

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM anggota WHERE NIS = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $nis_edit);
    mysqli_stmt_execute($stmt);

    $hasil_edit = mysqli_stmt_get_result($stmt);
    $data_edit = mysqli_fetch_assoc($hasil_edit);
}


/* =========================
   DATA PROFIL
========================= */
$data_profil = null;

if (isset($_GET['profil'])) {

    $nis_profil = $_GET['profil'];

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM anggota WHERE NIS = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $nis_profil);
    mysqli_stmt_execute($stmt);

    $hasil_profil = mysqli_stmt_get_result($stmt);
    $data_profil = mysqli_fetch_assoc($hasil_profil);
}


/* =========================
   AMBIL DATA ANGGOTA
========================= */
$anggota = mysqli_query(
    $koneksi,
    "SELECT * FROM anggota ORDER BY NIS"
);

if (!$anggota) {
    die("Gagal mengambil data anggota: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Data Anggota Perpustakaan</title>

<link rel="stylesheet" href="styleperpustakaan.css">

<style>

body {
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    padding: 30px;
}

.container {
    max-width: 1100px;
    margin: auto;
}

h1 {
    color: #172554;
    margin-bottom: 25px;
}

h2 {
    color: #1e3a8a;
}

.form-box,
.profil-box {
    background: white;
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,.08);
}

input,
textarea {
    width: 100%;
    padding: 10px;
    margin-top: 7px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
}

textarea {
    min-height: 80px;
}

button,
.btn {
    border: none;
    padding: 9px 14px;
    border-radius: 8px;
    text-decoration: none;
    cursor: pointer;
    display: inline-block;
    margin: 3px;
}

.btn-simpan {
    background: #2563eb;
    color: white;
}

.btn-lihat {
    background: #16a34a;
    color: white;
}

.btn-edit {
    background: #f59e0b;
    color: white;
}

.btn-hapus {
    background: #dc2626;
    color: white;
}

.btn-kembali {
    background: #2563eb !important;
    color: white !important;
    padding: 9px 14px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 5px 20px rgba(0,0,0,.08);
}

th {
    background: #172554;
    color: white;
    padding: 12px;
}

td {
    padding: 12px;
    border-bottom: 1px solid #e2e8f0;
}

tr:hover {
    background: #f8fafc;
}

.profil-item {
    margin: 12px 0;
}

.profil-item b {
    display: inline-block;
    width: 120px;
}

</style>

</head>

<body>

<div class="container">

<h1>👥 Data Anggota Perpustakaan</h1>


<!-- =========================
     PROFIL
========================= -->

<?php if ($data_profil) { ?>

<div class="profil-box">

    <h2>👤 Profil Anggota</h2>

    <div class="profil-item">
        <b>NIS</b> :
        <?= htmlspecialchars($data_profil['NIS']); ?>
    </div>

    <div class="profil-item">
        <b>Nama</b> :
        <?= htmlspecialchars($data_profil['nama_anggota']); ?>
    </div>

    <div class="profil-item">
        <b>Jurusan</b> :
        <?= htmlspecialchars($data_profil['jurusan']); ?>
    </div>

    <div class="profil-item">
        <b>Alamat</b> :
        <?= htmlspecialchars($data_profil['alamat']); ?>
    </div>

    <a href="anggota.php" class="btn btn-kembali">
        ⬅️ Kembali
    </a>

</div>

<?php } ?>


<!-- =========================
     FORM EDIT ADMIN
========================= -->

<?php if ($is_admin && $data_edit) { ?>

<div class="form-box">

    <h2>✏️ Edit Anggota</h2>

    <form method="POST">

        <input
            type="hidden"
            name="nis_lama"
            value="<?= htmlspecialchars($data_edit['NIS']); ?>"
        >

        <label>NIS</label>

        <input
            type="text"
            name="NIS"
            value="<?= htmlspecialchars($data_edit['NIS']); ?>"
            required
        >

        <br><br>

        <label>Nama Anggota</label>

        <input
            type="text"
            name="nama_anggota"
            value="<?= htmlspecialchars($data_edit['nama_anggota']); ?>"
            required
        >

        <br><br>

        <label>Jurusan</label>

        <input
            type="text"
            name="jurusan"
            value="<?= htmlspecialchars($data_edit['jurusan']); ?>"
            required
        >

        <br><br>

        <label>Alamat</label>

        <textarea
            name="alamat"
            required
        ><?= htmlspecialchars($data_edit['alamat']); ?></textarea>

        <br><br>

        <button
            type="submit"
            name="update_anggota"
            class="btn btn-simpan"
        >
            Simpan Perubahan
        </button>

        <a href="anggota.php" class="btn btn-kembali">
            Batal
        </a>

    </form>

</div>

<?php } ?>


<!-- =========================
     FORM TAMBAH ADMIN
========================= -->

<?php if ($is_admin && !$data_edit) { ?>

<div class="form-box">

    <h2>➕ Tambah Anggota</h2>

    <form method="POST">

        <label>NIS</label>

        <input
            type="text"
            name="NIS"
            required
        >

        <br><br>

        <label>Nama Anggota</label>

        <input
            type="text"
            name="nama_anggota"
            required
        >

        <br><br>

        <label>Jurusan</label>

        <input
            type="text"
            name="jurusan"
            required
        >

        <br><br>

        <label>Alamat</label>

        <textarea
            name="alamat"
            required
        ></textarea>

        <br><br>

        <button
            type="submit"
            name="simpan_anggota"
            class="btn btn-simpan"
        >
            Simpan Anggota
        </button>

    </form>

</div>

<?php } ?>


<!-- =========================
     DAFTAR ANGGOTA
========================= -->

<h2>📋 Daftar Anggota</h2>

<br>

<table>

<tr>

    <th>NIS</th>
    <th>Nama Anggota</th>
    <th>Jurusan</th>
    <th>Alamat</th>
    <th>Aksi</th>

</tr>

<?php while ($row = mysqli_fetch_assoc($anggota)) { ?>

<tr>

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

        <!-- PROFIL, BISA UNTUK SEMUA USER -->

        <a
            href="anggota.php?profil=<?= urlencode($row['NIS']); ?>"
            class="btn btn-lihat"
        >
            👤 Profil
        </a>


        <!-- EDIT DAN HAPUS HANYA ADMIN -->

        <?php if ($is_admin) { ?>

            <a
                href="anggota.php?edit=<?= urlencode($row['NIS']); ?>"
                class="btn btn-edit"
            >
                ✏️ Edit
            </a>

            <a
                href="anggota.php?hapus=<?= urlencode($row['NIS']); ?>"
                class="btn btn-hapus"
                onclick="return confirm('Yakin ingin menghapus anggota ini?')"
            >
                🗑️ Hapus
            </a>

        <?php } ?>

    </td>

</tr>

<?php } ?>

</table>

<br>

<a href="<?= $kembali ?>" class="btn btn-kembali">
    ⬅️ Kembali
</a>

</div>

</body>

</html>