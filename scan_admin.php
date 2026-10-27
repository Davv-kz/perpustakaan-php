<?php
session_start();

$qr_benar = "ADMIN-2026-SECRET";
$error = "";

if (isset($_POST['qr'])) {
    $qr = trim($_POST['qr']);

    if (hash_equals($qr_benar, $qr)) {
        session_regenerate_id(true);

        // Atur sesi khusus admin
        session_unset();
        $_SESSION['admin'] = true;
        $_SESSION['level'] = 'admin';
        $_SESSION['username'] = 'admin';
        $_SESSION['nama_lengkap'] = 'Administrator';

        // Admin tidak memiliki NIS anggota
        unset($_SESSION['NIS']);

        header("Location: admin.php");
        exit;
    } else {
        $error = "QR Code Admin salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR Admin</title>

    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #172554, #2563eb);
        }

        .container {
            width: 90%;
            max-width: 500px;
            background: white;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 15px 40px rgba(0,0,0,.2);
        }

        h1 {
            color: #172554;
        }

        p {
            color: #64748b;
        }

        #reader {
            width: 100%;
            margin: 20px auto;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .back {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: #2563eb;
        }
    </style>
</head>

<body>
<div class="container">

    <h1>🔐 Admin</h1>
    <p>Arahkan kamera ke QR Code Admin</p>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div id="reader"></div>

    <form method="POST" id="qrForm">
        <input type="hidden" name="qr" id="qr">
    </form>

    <a href="index.php" class="back">← Kembali</a>
</div>

<script>
let sudahScan = false;

function berhasilScan(decodedText) {
    if (sudahScan) return;

    decodedText = decodedText.trim();

    if (decodedText === "ADMIN-2026-SECRET") {
        sudahScan = true;
        document.getElementById("qr").value = decodedText;
        document.getElementById("qrForm").submit();
    } else {
        alert("QR Code bukan QR Admin!");
    }
}

const scanner = new Html5QrcodeScanner(
    "reader",
    {
        fps: 10,
        qrbox: 250,
        formatsToSupport: [
            Html5QrcodeSupportedFormats.QR_CODE
        ]
    },
    false
);

scanner.render(
    berhasilScan,
    function(error) {
        // Abaikan pesan pemindaian selama belum berhasil
    }
);
</script>

</body>
</html>