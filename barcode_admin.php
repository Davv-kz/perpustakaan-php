<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Admin</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #e8eef4;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,.15);
        }

        h1 {
            color: #172554;
        }

        p {
            color: #64748b;
        }

        .qr {
            width: 300px;
            height: 300px;
            display: block;
            margin: 25px auto;
        }

        .kode {
            font-weight: bold;
            color: #172554;
        }
    </style>
</head>

<body>

<div class="box">

    <h1>🔐 QR Code Admin</h1>

    <p>Gunakan QR Code ini untuk masuk sebagai Admin</p>

    <img
        class="qr"
        src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=ADMIN-2026-SECRET"
        alt="QR Code Admin"
    >

    <div class="kode">
        ADMIN-2026-SECRET
    </div>

</div>

</body>
</html>