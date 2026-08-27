<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            background-color: #0f172a;
            color: #ffffff;
            margin: 0;
            padding: 0;
            height: 100vh;
        }
        .wrapper {
            padding-top: 250px;
            text-align: center;
        }
        .badge {
            background-color: #0284c7;
            color: #ffffff;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            display: inline-block;
        }
        .title {
            font-size: 26px;
            font-weight: bold;
            margin-top: 30px;
            color: #f8fafc;
            letter-spacing: 1px;
        }
        .sub {
            font-size: 14px;
            color: #94a3b8;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="badge">DOKUMEN {{ sprintf('%02d', $number) }}</div>
        <div class="title">{{ strtoupper($documentTypeName) }}</div>
        <div class="sub">Nomor: {{ $documentNumber ?: '-' }} &bull; Status Verifikasi: {{ strtoupper($status) }}</div>
    </div>
</body>
</html>
