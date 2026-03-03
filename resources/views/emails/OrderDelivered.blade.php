<!DOCTYPE html>
<html>
<head>
    <title>Order Delivered - First Phone</title>
</head>
<body style="font-family: Arial, sans-serif;">
    
    <div style="text-align:center; margin-bottom: 20px;">
        <img src="{{ asset('public/admin/assets/images/FirstPhone-Logo.jpg') }}" 
             alt="First Phone Logo"
             style="max-width: 320px; width: 100%; height: auto; display: block; margin: 0 auto 20px;">

        <h2 style="color: #333;"><strong>Order Delivered</strong></h2>
    </div>

    <p>Hi {{ $name ?? 'Customer' }},</p>

    <p>
        We are pleased to inform you that your order has been successfully <strong>delivered</strong>.
        We hope you are satisfied with your purchase from <strong>First Phone</strong>.
    </p>

    <div style="background-color: #f4f6f8; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <p><strong>Order Number:</strong> {{ $order_number }}</p>
    </div>

    <p>
        Thank you for shopping with us. Your trust in <strong>First Phone</strong> means a lot to us.
        We look forward to serving you again in the future.
    </p>

    <p>
        If you have any feedback or need support, feel free to contact us at 
        <a href="mailto:support@firstphone.pk">support@firstphone.pk</a>.
    </p>

    <p>Warm regards,<br>
    <strong>First Phone Team</strong></p>

</body>
</html>