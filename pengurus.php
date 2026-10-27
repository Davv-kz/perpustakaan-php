<?php
session_start();
include "koneksi.php";

// ==========================
// CEK LOGIN
// ==========================
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// ==========================
// TENTUKAN HALAMAN KEMBALI
// ==========================
$is_admin = isset($_SESSION['admin']) && $_SESSION['admin'] === true;
$kembali = $is_admin ? "admin.php" : "dashboard.php";


// ==========================
// AMBIL DATA PENGURUS
// ==========================
$pengurus = mysqli_query($koneksi, "SELECT * FROM data_pengurus");

if (!$pengurus) {
    die("Gagal mengambil data pengurus: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Data Pengurus Perpustakaan</title>

    <link rel="stylesheet" href="styleperpustakaan.css">

</head>

<body>

<h1>Data Pengurus Perpustakaan</h1>

<table border="1" cellpadding="8" cellspacing="0">

    <tr>

        <th>ID Pengurus</th>
        <th>Nama Pengurus</th>
        <th>Jabatan</th>
        <th>No Telepon</th>

    </tr>

    <?php while ($row = mysqli_fetch_assoc($pengurus)) { ?>

    <tr>

        <td>
            <?= htmlspecialchars($row['id_pengurus']); ?>
        </td>

        <td>
            <?= htmlspecialchars($row['nama_pengurus']); ?>
        </td>

        <td>
            <?= htmlspecialchars($row['jabatan']); ?>
        </td>

        <td>
            <?= htmlspecialchars($row['no_telepon']); ?>
        </td>

    </tr>

    <?php } ?>

</table>

<br>

<a href="<?= $kembali ?>" class="kembali">
    ⬅️ Kembali
</a>

</body>

</html>