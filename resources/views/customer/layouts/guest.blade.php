<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Barbershop SaaS') }} - Customer Sign Up</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --ink:          #0D1117;
            --surface:      #161B22;
            --surface-2:    #1C2330;
            --border:       rgba(255,255,255,0.07);
            --border-strong:rgba(255,255,255,0.13);
            --gold:         #C9A84C;
            --rust:         #B54B2A;
            --cream:        #F5EFE0;
            --muted:        #8B9AAD;
            --radius-lg:    16px;
            --font-display: 'Bebas Neue', sans-serif;
            --font-body:    'DM Sans', sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            background: var(--ink);
            color: var(--cream);
            font-family: var(--font-body);
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
        }

        a { text-decoration: none; color: var(--gold); }
        a:hover { text-decoration: underline; }

        .auth-card {
            width: 100%;
            max-width: 460px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            margin: 20px;
            animation: slideUp 0.5s ease;
        }

        .auth-header h1 {
            font-family: var(--font-display);
            font-size: 42px;
            margin: 0 0 8px 0;
            line-height: 1;
            text-align: center;
            color: var(--cream);
        }

        .auth-header p {
            text-align: center;
            color: var(--muted);
            margin: 0 0 32px 0;
            font-size: 14px;
        }

        .form-group { margin-bottom: 20px; }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            color: var(--muted);
        }

        .form-input {
            width: 100%;
            background: var(--surface-2);
            border: 1px solid var(--border-strong);
            color: var(--cream);
            font-family: var(--font-body);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 15px;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--gold);
        }

        .btn-submit {
            width: 100%;
            background: var(--rust);
            color: var(--cream);
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.2s;
        }

        .btn-submit:hover {
            background: #a34123;
        }

        .error-message {
            color: #ff8a6e;
            font-size: 12px;
            margin-top: 6px;
            display: block;
        }

        .footer-link {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: var(--muted);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="auth-card">
        @yield('content')
    </div>
</body>
</html>
