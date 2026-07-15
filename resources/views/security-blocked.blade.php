<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>403 — {{ __('security_admin.blocked_title') }}</title>
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
               font-family:'Tajawal','Segoe UI',sans-serif; background:#0f1115; color:#e5e7eb; padding:24px; }
        .box { max-width:520px; text-align:center; background:#171a21; border:1px solid #262b36;
               border-radius:18px; padding:40px 32px; box-shadow:0 30px 80px -30px rgba(0,0,0,.7); }
        .ic { width:72px; height:72px; margin:0 auto 18px; border-radius:9999px; display:flex;
              align-items:center; justify-content:center; background:rgba(239,68,68,.12); font-size:34px; }
        h1 { font-size:22px; margin:0 0 10px; color:#fff; }
        p { margin:0 0 8px; color:#9aa3b2; line-height:1.9; font-size:15px; }
        .code { margin-top:18px; font-size:12px; color:#5b6472; letter-spacing:1px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="ic">🛡️</div>
        <h1>{{ __('security_admin.blocked_title') }}</h1>
        <p>{{ __('security_admin.blocked_body') }}</p>
        <p>{{ __('security_admin.blocked_contact') }}</p>
        <div class="code">Ref: 403 · {{ now()->format('Y-m-d H:i') }} UTC</div>
    </div>
</body>
</html>
