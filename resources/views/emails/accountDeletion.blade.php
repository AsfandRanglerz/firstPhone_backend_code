<!DOCTYPE html>
<html>
<head>
    <title>Account Deleted - First Phone</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <div style="text-align:center; margin-bottom: 20px;">
<img src="{{ asset('public/admin/assets/images/FirstPhone-Logo.jpg') }}" 
     alt="First Phone Logo"
     style="max-width: 320px; width: 100%; height: auto; display: block; margin: 0 auto 20px;">

        <h3><strong>Account Deleted</strong></h3>
    </div>

<p>Hi {{ $user->name ?? 'User' }},</p>

    <p>Your <strong>First Phone</strong> account has been permanently deleted.</p>

    <p>If this wasn’t you or happened by mistake, contact us within 7 days at  
    <a href="mailto:info@firstphone.pk" style="color:#007bff;">info@firstphone.pk</a>.</p>

    <p>Thank you for being part of First Phone.</p>

    <p style="margin-top:25px;"><strong>First Phone Team</strong></p>
</body>
</html>
