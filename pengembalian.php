
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
    die("Level akun tidak valid. Silakan login ulang.");
}

if ($is_pengguna && empty($_SESSION['NIS'])) {
    die("NIS akun tidak ditemukan. Silakan login ulang.");
}

$kembali = $is_admin ? "admin.php" : "dashboard.php";

// =====================================
// PROSES PENGEMBALIAN
// HANYA ANGGOTA YANG BOLEH MENGEMBALIKAN
// =====================================
if (isset($_POST['kembalikan'])) {

    // Admin dilarang memproses pengembalian
    if (!$is_pengguna || $is_admin) {
        http_response_code(403);
        exit("Akses ditolak. Admin hanya boleh melihat data pengembalian.");
    }

    $id_peminjaman = (int) ($_POST['id_peminjaman'] ?? 0);

    if ($id_peminjaman < 1) {
        die("Pilih peminjaman yang ingin dikembalikan.");
    }

    mysqli_begin_transaction($koneksi);

    try {
        // Anggota hanya dapat mengembalikan pinjaman miliknya
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_peminjaman, NIS, id_buku, stok, status
             FROM peminjaman
             WHERE id_peminjaman = ?
               AND NIS = ?
             FOR UPDATE"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "is",
            $id_peminjaman,
            $_SESSION['NIS']
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal memeriksa peminjaman.");
        }

        $hasil = mysqli_stmt_get_result($stmt);
        $pinjaman = mysqli_fetch_assoc($hasil);

        if (!$pinjaman) {
            throw new Exception(
                "Peminjaman tidak ditemukan atau bukan milik akun kamu."
            );
        }

        if ($pinjaman['status'] !== 'Dipinjam') {
            throw new Exception(
                "Buku sudah dikembalikan atau statusnya bukan Dipinjam."
            );
        }

        $id_buku = (int) $pinjaman['id_buku'];
        $jumlah = (int) $pinjaman['stok'];

        if ($jumlah < 1) {
            throw new Exception("Jumlah buku tidak valid.");
        }

        // Kunci data buku
        $stmt_buku = mysqli_prepare(
            $koneksi,
            "SELECT id_buku
             FROM data_buku
             WHERE id_buku = ?
             FOR UPDATE"
        );

        mysqli_stmt_bind_param($stmt_buku, "i", $id_buku);
        mysqli_stmt_execute($stmt_buku);

        $hasil_buku = mysqli_stmt_get_result($stmt_buku);

        if (!mysqli_fetch_assoc($hasil_buku)) {
            throw new Exception("Data buku tidak ditemukan.");
        }

        // Ubah status peminjaman
        $stmt_status = mysqli_prepare(
            $koneksi,
            "UPDATE peminjaman
             SET status = 'Sudah Dikembalikan'
             WHERE id_peminjaman = ?
               AND NIS = ?
               AND status = 'Dipinjam'"
        );

        mysqli_stmt_bind_param(
            $stmt_status,
            "is",
            $id_peminjaman,
            $_SESSION['NIS']
        );

        if (!mysqli_stmt_execute($stmt_status)) {
            throw new Exception("Gagal mengubah status peminjaman.");
        }

        if (mysqli_stmt_affected_rows($stmt_status) !== 1) {
            throw new Exception(
                "Status peminjaman berubah. Silakan muat ulang halaman."
            );
        }

        // Tambahkan stok buku kembali
        $stmt_stok = mysqli_prepare(
            $koneksi,
            "UPDATE data_buku
             SET stok = stok + ?
             WHERE id_buku = ?"
        );

        mysqli_stmt_bind_param(
            $stmt_stok,
            "ii",
            $jumlah,
            $id_buku
        );

        if (!mysqli_stmt_execute($stmt_stok)) {
            throw new Exception("Gagal menambahkan stok buku.");
        }

        if (mysqli_stmt_affected_rows($stmt_stok) !== 1) {
            throw new Exception("Stok buku gagal diperbarui.");
        }

        // Catat riwayat pengembalian
        $tanggal_sekarang = date('Y-m-d');

        $stmt_simpan = mysqli_prepare(
            $koneksi,
            "INSERT INTO pengembalian
             (id_peminjaman, stok, tanggal_pengembalian, status)
             VALUES (?, ?, ?, 'Sudah Dikembalikan')"
        );

        mysqli_stmt_bind_param(
            $stmt_simpan,
            "iis",
            $id_peminjaman,
            $jumlah,
            $tanggal_sekarang
        );

        if (!mysqli_stmt_execute($stmt_simpan)) {
            throw new Exception(
                "Gagal mencatat pengembalian: " .
                mysqli_stmt_error($stmt_simpan)
            );
        }

        mysqli_commit($koneksi);

        echo "<script>
            alert('Buku berhasil dikembalikan! Status dan stok telah diperbarui.');
            window.location.href = 'pengembalian.php';
        </script>";
        exit;

    } catch (Throwable $e) {
        mysqli_rollback($koneksi);

        $pesan = json_encode(
            $e->getMessage(),
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_HEX_AMP
        );

        echo "<script>
            alert($pesan);
            window.location.href = 'pengembalian.php';
        </script>";
        exit;
    }
}

// =====================================
// DAFTAR PEMINJAMAN YANG BELUM KEMBALI
// =====================================
if ($is_admin) {
    // Admin dapat melihat semua pinjaman aktif
    $pinjaman_aktif = mysqli_query(
        $koneksi,
        "SELECT p.id_peminjaman, p.NIS, p.id_buku,
                p.stok, p.tanggal_peminjaman,
                p.tanggal_pengembalian, b.judul_buku
         FROM peminjaman p
         LEFT JOIN data_buku b ON p.id_buku = b.id_buku
         WHERE p.status = 'Dipinjam'
         ORDER BY p.id_peminjaman DESC"
    );
} else {
    // Anggota hanya melihat pinjaman miliknya
    $stmt_aktif = mysqli_prepare(
        $koneksi,
        "SELECT p.id_peminjaman, p.NIS, p.id_buku,
                p.stok, p.tanggal_peminjaman,
                p.tanggal_pengembalian, b.judul_buku
         FROM peminjaman p
         LEFT JOIN data_buku b ON p.id_buku = b.id_buku
         WHERE p.status = 'Dipinjam'
           AND p.NIS = ?
         ORDER BY p.id_peminjaman DESC"
    );

    mysqli_stmt_bind_param(
        $stmt_aktif,
        "s",
        $_SESSION['NIS']
    );

    mysqli_stmt_execute($stmt_aktif);
    $pinjaman_aktif = mysqli_stmt_get_result($stmt_aktif);
}

if (!$pinjaman_aktif) {
    die("Gagal mengambil pinjaman aktif: " . mysqli_error($koneksi));
}

// =====================================
// RIWAYAT PENGEMBALIAN
// =====================================
if ($is_admin) {
    // Admin dapat melihat semua riwayat
    $pengembalian = mysqli_query(
        $koneksi,
        "SELECT pg.id_pengembalian, pg.id_peminjaman,
                pg.stok, pg.tanggal_pengembalian,
                pg.status, p.NIS, p.id_buku,
                p.tanggal_peminjaman,
                p.tanggal_pengembalian AS batas_pengembalian,
                b.judul_buku
         FROM pengembalian pg
         INNER JOIN peminjaman p
             ON pg.id_peminjaman = p.id_peminjaman
         LEFT JOIN data_buku b
             ON p.id_buku = b.id_buku
         ORDER BY pg.id_pengembalian DESC"
    );
} else {
    // Anggota hanya melihat riwayat miliknya
    $stmt_riwayat = mysqli_prepare(
        $koneksi,
        "SELECT pg.id_pengembalian, pg.id_peminjaman,
                pg.stok, pg.tanggal_pengembalian,
                pg.status, p.NIS, p.id_buku,
                p.tanggal_peminjaman,
                p.tanggal_pengembalian AS batas_pengembalian,
                b.judul_buku
         FROM pengembalian pg
         INNER JOIN peminjaman p
             ON pg.id_peminjaman = p.id_peminjaman
         LEFT JOIN data_buku b
             ON p.id_buku = b.id_buku
         WHERE p.NIS = ?
         ORDER BY pg.id_pengembalian DESC"
    );

    mysqli_stmt_bind_param(
        $stmt_riwayat,
        "s",
        $_SESSION['NIS']
    );

    mysqli_stmt_execute($stmt_riwayat);
    $pengembalian = mysqli_stmt_get_result($stmt_riwayat);
}

if (!$pengembalian) {
    die("Gagal mengambil riwayat pengembalian: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian Buku</title>
    <link rel="stylesheet" href="styleperpustakaan.css">

    <style>
        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

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

<h1>Pengembalian Buku</h1>

<h2>Buku yang Belum Dikembalikan</h2>

<div class="tabel-container">
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>ID Peminjaman</th>
                <th>NIS</th>
                <th>Buku</th>
                <th>Jumlah</th>
                <th>Tanggal Pinjam</th>
                <th>Batas Pengembalian</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
        <?php if (mysqli_num_rows($pinjaman_aktif) > 0): ?>
            <?php while ($p = mysqli_fetch_assoc($pinjaman_aktif)): ?>
                <tr>
                    <td><?= (int) $p['id_peminjaman'] ?></td>
                    <td><?= htmlspecialchars($p['NIS']) ?></td>
                    <td>
                        <?= htmlspecialchars(
                            $p['judul_buku'] ?? 'Buku tidak ditemukan'
                        ) ?>
                    </td>
                    <td><?= (int) $p['stok'] ?></td>
                    <td><?= htmlspecialchars($p['tanggal_peminjaman']) ?></td>
                    <td><?= htmlspecialchars($p['tanggal_pengembalian']) ?></td>
                    <td>
                        <?php if ($is_pengguna): ?>
                            <form method="POST"
                                  action="pengembalian.php"
                                  onsubmit="return confirm('Yakin ingin mengembalikan buku ini?')">

                                <input
                                    type="hidden"
                                    name="id_peminjaman"
                                    value="<?= (int) $p['id_peminjaman'] ?>"
                                >

                                <button type="submit" name="kembalikan">
                                    Kembalikan
                                </button>
                            </form>
                        <?php else: ?>
                            <span>Hanya melihat</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">Tidak ada buku yang menunggu pengembalian.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<hr>

<h2>Riwayat Pengembalian</h2>

<div class="tabel-container">
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>ID Pengembalian</th>
                <th>ID Peminjaman</th>
                <th>NIS</th>
                <th>Judul Buku</th>
                <th>Jumlah</th>
                <th>Tanggal Peminjaman</th>
                <th>Batas Pengembalian</th>
                <th>Tanggal Dikembalikan</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
        <?php if (mysqli_num_rows($pengembalian) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($pengembalian)): ?>
                <tr>
                    <td><?= (int) $row['id_pengembalian'] ?></td>
                    <td><?= (int) $row['id_peminjaman'] ?></td>
                    <td><?= htmlspecialchars($row['NIS']) ?></td>
                    <td>
                        <?= htmlspecialchars(
                            $row['judul_buku'] ?? 'Buku tidak ditemukan'
                        ) ?>
                    </td>
                    <td><?= (int) $row['stok'] ?></td>
                    <td><?= htmlspecialchars($row['tanggal_peminjaman']) ?></td>
                    <td><?= htmlspecialchars($row['batas_pengembalian']) ?></td>
                    <td><?= htmlspecialchars($row['tanggal_pengembalian']) ?></td>
                    <td>
                        <span class="status status-kembali">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="9">Belum ada riwayat pengembalian.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<br>

<a href="<?= htmlspecialchars($kembali) ?>" class="kembali">
    ⬅️ Kembali
</a>

</body>
</html>
