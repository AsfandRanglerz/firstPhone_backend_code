<!DOCTYPE html>
<html>
<head>
    <title>Welcome to First Phone</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <div style="text-align:center; margin-bottom: 20px;">
<img src="{{ asset('public/admin/assets/images/FirstPhone-Logo.jpg') }}" 
     alt="First Phone Logo"
     style="max-width: 320px; width: 100%; height: auto; display: block; margin: 0 auto 20px;">
      {{-- <h2 style="color: #021642; margin: 0;">Welcome to <span style="color: #007bff;">First Phone</span></h2> --}}
    </div>

    <p style="font-size: 15px; color: #333;">Hi <strong>{{ $user->name ?? 'User' }}</strong>,</p>

    <p style="font-size: 15px; color: #333;">Your account has been successfully created. Below are your account details:</p>

    <div style="background: #f4f7ff; border-left: 4px solid #007bff; padding: 15px 20px; border-radius: 6px; margin: 20px 0;">
      <p style="margin: 5px 0;"><strong>Email:</strong> {{ $user->email ?? 'N/A' }}</p>
      <p style="margin: 5px 0;"><strong>Password:</strong> {{ $user->plain_password  ?? 'N/A' }}</p>
    </div>

    <p style="font-size: 14px; color: #555;">
      Please keep this information safe and secure.  
      If you have any questions or need assistance, contact us anytime at 
      <a href="mailto:info@firstphone.pk" style="color: #007bff; text-decoration: none;">info@firstphone.pk</a>.
    </p>

    <!-- Footer -->
    <p style="margin-top: 35px; font-size: 14px; color: #555;">Thanks,<br><strong>First Phone Team</strong></p>
</body>
</html>
