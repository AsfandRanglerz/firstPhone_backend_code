<!DOCTYPE html>
<html>
<head>
    <title>Sub Admin Account Created</title>
</head>
<body style="font-family: Arial, sans-serif;">

    <div style="text-align:center; margin-bottom: 20px;">
        <img src="{{ asset('public/admin/assets/images/FirstPhone-Logo.jpg') }}" 
             alt="First Phone Logo"
             style="max-width: 320px; width: 100%; height: auto; display: block; margin: 0 auto 20px;">
    </div>

    <p style="font-size: 15px; color: #333;">
        Hi <strong>{{ $data['name'] ?? 'Sub Admin' }}</strong>,
    </p>

    <p style="font-size: 15px; color: #333;">
        Your account as a <strong>Sub-Admin</strong> has been successfully created. 
        Below are your login credentials:
    </p>

    <div style="background: #f4f7ff; border-left: 4px solid #007bff; padding: 15px 20px; border-radius: 6px; margin: 20px 0;">
        <p style="margin: 5px 0;"><strong>Email:</strong> {{ $data['email'] }}</p>
        <p style="margin: 5px 0;"><strong>Password:</strong> {{ $data['password'] }}</p>
    </div>

    <p style="font-size: 14px; color: #555;">
        Please keep your login credentials secure. 
        If you have any questions or need assistance, contact us anytime at 
        <a href="mailto:support@firstphone.pk" style="color: #007bff; text-decoration: none;">
            support@firstphone.pk
        </a>.
    </p>

    <!-- Footer -->
    <p style="margin-top: 35px; font-size: 14px; color: #555;">
        Thanks,<br>
        <strong>First Phone Team</strong>
    </p>

</body>
</html>