<!DOCTYPE html>
<html>

<head>
    <title>Account Deactivated - First Phone</title>
</head>

<body style="font-family: Arial, sans-serif;">
    
        <!-- Logo and Header -->
        <div style="text-align:center; margin-bottom: 20px;">
<img src="{{ asset('public/admin/assets/images/FirstPhone-Logo.jpg') }}" 
     alt="First Phone Logo"
     style="max-width: 320px; width: 100%; height: auto; display: block; margin: 0 auto 20px;">

            <h2>Account Deactivation</h2>
        </div>

        <p style="font-size: 15px; color: #333;">Hi <strong>{{ $name ?? 'User' }}</strong>,</p>

        <!-- Message -->
        <p style="font-size: 15px; color: #333; line-height: 1.6;">
            We’re regret to inform you that your account has been <strong>deactivated</strong> by our administrator.  
            This means you currently won’t be able to log in or access your First Phone account.
        </p>

        <!-- Reason -->
        @if (!empty($reason))
        <div style="background-color: #fff4f4; border-left: 4px solid #c0392b; padding: 12px 15px; margin: 20px 0; border-radius: 6px;">
            <p style="margin: 0; font-size: 15px; color: #333;">
                <strong>Reason:</strong> {{ $reason }}
            </p>
        </div>
        @endif

        <!-- Support -->
        <p style="font-size: 14px; color: #555; line-height: 1.6;">
            If you believe this action was a mistake or need further clarification,  
            please contact our support team at 
            <a href="mailto:info@firstphone.pk" style="color: #021642; text-decoration: none; font-weight: bold;">info@firstphone.pk</a>.
        </p>

        <!-- Footer -->
        <p style="font-size: 14px; color: #666; margin-top: 30px;">
            Regards,<br>
            <strong style="color: #021642;">The First Phone Team</strong>
        </p>
</body>

</html>
