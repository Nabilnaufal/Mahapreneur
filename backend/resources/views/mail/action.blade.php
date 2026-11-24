<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>{{ $title }}</title>
</head>
<body style="margin:0;background:#F7FAFC;font-family:Arial,Helvetica,sans-serif;color:#0A1926">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#F7FAFC">
        <tr><td align="center" style="padding:36px 16px">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px">
                <tr><td style="padding:0 8px 18px">
                    <a href="{{ config('mahapreneur.app_url') }}" style="text-decoration:none;color:#102C40">
                        <img src="{{ config('mahapreneur.app_url') }}/logo-mark.png" width="34" height="34" alt="Mahapreneur" style="vertical-align:middle;border:0;border-radius:10px" />
                        <span style="display:inline-block;vertical-align:middle;margin-left:8px;font-size:19px;font-weight:800;letter-spacing:-.4px">Mahapreneur</span>
                    </a>
                </td></tr>
                <tr><td style="background:#102C40;border-radius:22px 22px 0 0;padding:7px 28px 0"><div style="height:5px;border-radius:99px;background:#DBFF00"></div></td></tr>
                <tr><td style="background:#102C40;padding:30px 28px 34px">
                    <p style="margin:0 0 12px;color:#DBFF00;font-size:11px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase">Mahapreneur</p>
                    <h1 style="margin:0;color:#FFFFFF;font-size:26px;line-height:1.25;letter-spacing:-.5px">{{ $title }}</h1>
                </td></tr>
                <tr><td style="background:#FFFFFF;padding:30px 28px 28px;border-radius:0 0 22px 22px;box-shadow:0 12px 30px rgba(10,25,38,.08)">
                    <div style="color:#33475B;font-size:15px;line-height:1.7">{{ $bodyText }}</div>
                    @if ($ctaLabel && $ctaUrl)
                        <p style="margin:26px 0 0"><a href="{{ $ctaUrl }}" style="display:inline-block;background:#DBFF00;color:#0A1926;padding:13px 22px;border-radius:999px;font-size:14px;font-weight:700;text-decoration:none">{{ $ctaLabel }} &nbsp; →</a></p>
                    @endif
                    <p style="margin:26px 0 0;padding-top:20px;border-top:1px solid #E7EDF1;color:#64798C;font-size:12px;line-height:1.6">Jika kamu tidak melakukan permintaan ini, abaikan email ini. Jangan bagikan tautan ini kepada siapa pun.</p>
                </td></tr>
                <tr><td align="center" style="padding:20px 8px 0;color:#64798C;font-size:12px;line-height:1.6">Mahapreneur · Satu kampus, ribuan peluang jual beli.<br /><span style="color:#9AAAB7">Email otomatis, mohon tidak membalas.</span></td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
