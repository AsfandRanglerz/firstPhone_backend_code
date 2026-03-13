<!DOCTYPE html>
<html>
<head>
    <title>Admin Password Reset</title>
</head>
<body style="font-family: Arial, sans-serif;">

<div style="text-align:center; margin-bottom: 20px;">
    <img src="{{ asset('public/admin/assets/images/FirstPhone-Logo.jpg') }}" 
         alt="First Phone Logo"
         style="max-width: 320px; width: 100%; height: auto; display: block; margin: 0 auto 20px;">
</div>


<p style="font-size: 15px; color: #333;">
    Click the button below to reset your password securely.
</p>

<div style="text-align:center; margin: 25px 0;">
    <a href="{{ $data['url'] }}"
       style="background:#4AB95A; color:#fff; padding:12px 25px; text-decoration:none; border-radius:5px; font-size:15px;">
        Reset Password
    </a>
</div>

<p style="font-size: 14px; color: #555;">
    If the button above does not work, copy and paste the link below into your browser:
</p>

<p style="word-break: break-all;">
    <a href="{{ $data['url'] }}">{{ $data['url'] }}</a>
</p>


<!-- Footer -->
<p style="margin-top: 35px; font-size: 14px; color: #555;">
    Regards,<br>
    <strong>First Phone</strong>
</p>

</body>
</html>