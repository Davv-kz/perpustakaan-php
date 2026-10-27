
<?php
session_start();
include "koneksi.php";

$error = "";

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string(
        $koneksi,
        trim($_POST['username'] ?? '')
    );

    $password = $_POST['password'] ?? '';

    $query = mysqli_query(
        $koneksi,
        "SELECT * FROM login WHERE username='$username' LIMIT 1"
    );

    if (!$query) {
        die("Query gagal: " . mysqli_error($koneksi));
    }

    $data = mysqli_fetch_assoc($query);

    if ($data && password_verify($password, $data['password'])) {

        // Hapus session lama
        session_regenerate_id(true);
        unset($_SESSION['admin'], $_SESSION['NIS']);

        $_SESSION['username'] = $data['username'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        $_SESSION['level'] = $data['level'];

        // LOGIN ADMIN
        if ($data['level'] === 'admin') {

            $_SESSION['admin'] = true;

            header("Location: admin.php");
            exit;
        }

        // LOGIN ANGGOTA / PENGGUNA
        if ($data['level'] === 'anggota') {

            // Cari NIS anggota berdasarkan nama lengkap
            $nama = mysqli_real_escape_string(
                $koneksi,
                $data['nama_lengkap']
            );

            $cari_anggota = mysqli_query(
                $koneksi,
                "SELECT NIS
                 FROM anggota
                 WHERE nama_anggota='$nama'
                 LIMIT 2"
            );

            if (!$cari_anggota) {
                die("Gagal mencari NIS: " . mysqli_error($koneksi));
            }

            // Pastikan ditemukan tepat satu anggota
            if (mysqli_num_rows($cari_anggota) !== 1) {
                $error = "Data anggota tidak ditemukan atau nama tidak unik. Hubungi admin.";
            } else {

                $anggota = mysqli_fetch_assoc($cari_anggota);

                $_SESSION['NIS'] = $anggota['NIS'];

                header("Location: dashboard.php");
                exit;
            }

        } else {

            $error = "Level akun tidak valid.";

        }

    } else {

        $error = "Username atau password salah!";

    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Perpustakaan</title>
    <link rel="stylesheet" href="styleperpustakaan.css">
</head>

<body>

<div class="login-container">

    <h2>LOGIN</h2>
    <p>Perpustakaan</p>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">

        <input
            type="text"
            name="username"
            placeholder="Username"
            required
        >

        <input
            type="password"
            name="password"
            placeholder="Password"
            required
        >

        <button type="submit" name="login">
            Login
        </button>

    </form>

    <p>Belum mempunyai akun?</p>
    <a href="register.php">Register</a>

    <br><br>

    <a href="index.php">Kembali</a>

</div>

</body>
</html>
