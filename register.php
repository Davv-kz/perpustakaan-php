<?php
include "koneksi.php";

$error = "";

if (isset($_POST['register'])) {

    $nis = mysqli_real_escape_string($koneksi, $_POST['NIS']);
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_anggota']);
    $jurusan = mysqli_real_escape_string($koneksi, $_POST['jurusan']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);

    $password = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi_password'];

    // Cek NIS
    $cekNIS = mysqli_query(
        $koneksi,
        "SELECT * FROM anggota WHERE NIS='$nis'"
    );

    if (mysqli_num_rows($cekNIS) > 0) {

        $error = "NIS sudah terdaftar!";

    } else {

        // Cek username
        $cekUsername = mysqli_query(
            $koneksi,
            "SELECT * FROM login WHERE username='$username'"
        );

        if (mysqli_num_rows($cekUsername) > 0) {

            $error = "Username sudah digunakan!";

        } elseif ($password != $konfirmasi) {

            $error = "Password tidak sama!";

        } else {

            // Enkripsi password
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Simpan data anggota
            $anggota = mysqli_query($koneksi, "
                INSERT INTO anggota
                (NIS, nama_anggota, jurusan, alamat)
                VALUES
                ('$nis', '$nama', '$jurusan', '$alamat')
            ");

            if ($anggota) {

                // Simpan akun login
                $login = mysqli_query($koneksi, "
                    INSERT INTO login
                    (nama_lengkap, username, password, level)
                    VALUES
                    ('$nama', '$username', '$passwordHash', 'anggota')
                ");

                if ($login) {

                    header("Location: login.php?register=success");
                    exit;

                } else {

                    mysqli_query(
                        $koneksi,
                        "DELETE FROM anggota WHERE NIS='$nis'"
                    );

                    $error = "Registrasi gagal!";
                }

            } else {

                $error = "Data anggota gagal disimpan!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register - Perpustakaan</title>

    <link rel="stylesheet" href="styleperpustakaan.css">
</head>

<body>

<div class="login-container">

    <h2>REGISTER</h2>

    <p>Daftar sebagai anggota perpustakaan</p>

    <?php if ($error != ""): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <input
            type="text"
            name="NIS"
            placeholder="NIS"
            required
        >

        <input
            type="text"
            name="nama_anggota"
            placeholder="Nama Lengkap"
            required
        >

        <input
            type="text"
            name="jurusan"
            placeholder="Jurusan"
            required
        >

        <textarea
            name="alamat"
            placeholder="Alamat"
            required
        ></textarea>

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

        <input
            type="password"
            name="konfirmasi_password"
            placeholder="Konfirmasi Password"
            required
        >

        <button type="submit" name="register">
            Register
        </button>

    </form>

    <p>Sudah mempunyai akun?</p>

    <a href="login.php">Login</a>

</div>

</body>
</html>