<!DOCTYPE html>
<html>
<head>
    <title>Order Shipped - First Phone</title>
</head>
<body style="font-family: Arial, sans-serif;">
    
    <div style="text-align:center; margin-bottom: 20px;">
        <img src="{{ asset('public/admin/assets/images/FirstPhone-Logo.jpg') }}" 
             alt="First Phone Logo"
             style="max-width: 320px; width: 100%; height: auto; display: block; margin: 0 auto 20px;">

        <h2 style="color: #333;"><strong>Order Shipped</strong></h2>
    </div>
       
    <p>Hi {{ $name ?? 'User' }},</p>

    <p>
        Great news! Your order has been successfully <strong>shipped</strong> and is on the way.
    </p>

    <div style="background-color: #f4f6f8; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <p><strong>Order Number:</strong> {{ $order_number }}</p>
    </div>


    {{-- <p>
        If you have any questions regarding your shipment, feel free to contact our support team at 
        <a href="mailto:support@firstphone.pk">support@firstphone.pk</a>.
    </p> --}}

    <p>Thank you for choosing <strong>First Phone</strong>.<br>
    <strong>First Phone Team</strong></p>

</body>
</html> 