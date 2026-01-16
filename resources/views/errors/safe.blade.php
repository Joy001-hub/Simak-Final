<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simak</title>
    <style>
        :root {
            color-scheme: light;
        }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f5fb;
            color: #0f172a;
            font-family: Arial, Helvetica, sans-serif;
        }
        .toast-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        .toast {
            background: #b91c3b;
            color: #fff;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        .hint {
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            color: #0f172a;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="toast-wrap">
        <div class="toast">{{ $message ?? 'Kesalahan pada input' }}</div>
        <div class="hint">Silakan periksa input Anda.</div>
        <a class="btn" href="{{ url('/') }}">Kembali</a>
    </div>
</body>
</html>

