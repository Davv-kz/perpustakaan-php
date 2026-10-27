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
// CEK ADMIN
// ==========================
$is_admin = isset($_SESSION['admin']) && $_SESSION['admin'] === true;

$kembali = $is_admin ? "admin.php" : "dashboard.php";

// ==========================
// TAMBAH BUKU - KHUSUS ADMIN
// ==========================
if (isset($_POST['simpan_buku'])) {

    if (!$is_admin) {
        echo "<script>
                alert('Hanya admin yang dapat menambahkan buku!');
                window.location.href='buku.php';
              </script>";
        exit;
    }

    $id_buku = mysqli_real_escape_string(
        $koneksi, $_POST['id_buku']
    );

    $judul_buku = mysqli_real_escape_string(
        $koneksi, $_POST['judul_buku']
    );

    $penulis = mysqli_real_escape_string(
        $koneksi, $_POST['penulis']
    );

    $tahun_terbit = (int) $_POST['tahun_terbit'];
    $stok = (int) $_POST['stok'];

    if ($stok < 0) {
        echo "<script>
                alert('Stok tidak boleh kurang dari 0!');
                window.location.href='buku.php';
              </script>";
        exit;
    }

    $query = "INSERT INTO data_buku
              (id_buku, judul_buku, penulis, tahun_terbit, stok)
              VALUES
              ('$id_buku', '$judul_buku', '$penulis',
               '$tahun_terbit', '$stok')";

    if (mysqli_query($koneksi, $query)) {

        echo "<script>
                alert('Buku berhasil ditambahkan!');
                window.location.href='buku.php';
              </script>";
        exit;

    } else {
        echo "Gagal menyimpan: " . mysqli_error($koneksi);
    }
}

// ==========================
// AMBIL DATA BUKU
// ==========================
$buku = mysqli_query(
    $koneksi,
    "SELECT * FROM data_buku"
);

if (!$buku) {
    die("Gagal mengambil data buku: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Data Buku</title>

    <link rel="stylesheet" href="styleperpustakaan.css">
</head>

<body>

<h1>Data Buku Perpustakaan</h1>

<?php if ($is_admin) { ?>

    <h2>Tambah Buku</h2>

    <form method="POST">

        <label>ID Buku</label><br>
        <input type="text" name="id_buku" required>

        <br><br>

        <label>Judul Buku</label><br>
        <input type="text" name="judul_buku" required>

        <br><br>

        <label>Penulis</label><br>
        <input type="text" name="penulis" required>

        <br><br>

        <label>Tahun Terbit</label><br>
        <input type="number" name="tahun_terbit" required>

        <br><br>

        <label>Stok Buku</label><br>
        <input
            type="number"
            name="stok"
            min="0"
            value="10"
            required
        >

        <br><br>

        <button type="submit" name="simpan_buku">
            Simpan Buku
        </button>

    </form>

    <hr>

<?php } ?>

<h2>Daftar Buku</h2>

<table border="1" cellpadding="8" cellspacing="0">

    <tr>
        <th>ID Buku</th>
        <th>Judul Buku</th>
        <th>Penulis</th>
        <th>Tahun Terbit</th>
        <th>Stok</th>
    </tr>

    <?php while ($row = mysqli_fetch_assoc($buku)) { ?>

    <tr>

        <td>
            <?= htmlspecialchars($row['id_buku']); ?>
        </td>

        <td>
            <?= htmlspecialchars($row['judul_buku']); ?>
        </td>

        <td>
            <?= htmlspecialchars($row['penulis']); ?>
        </td>

        <td>
            <?= htmlspecialchars($row['tahun_terbit']); ?>
        </td>

        <td>
            <?= (int) $row['stok']; ?>
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