<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpustakaan</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
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
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 15px 40px rgba(0,0,0,.2);
        }

        .icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        h1 {
            color: #172554;
            margin-bottom: 10px;
        }

        p {
            color: #64748b;
            margin-bottom: 30px;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            margin: 12px 0;
            border-radius: 12px;
            text-decoration: none;
            color: white;
            font-weight: bold;
        }

        .admin {
            background: #2563eb;
        }

        .pengguna {
            background: #172554;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="icon">📚</div>

    <h1>Perpustakaan Sekolah</h1>

    <p>Silakan pilih jenis akses</p>

    <a href="scan_admin.php" class="btn admin">
        🔐 Masuk sebagai Admin
    </a>

    <a href="login.php" class="btn pengguna">
        👤 Login sebagai Pengguna
    </a>

</div>

</body>
</html>