<!DOCTYPE html>
<html>
<head>
    <title>Order Placed - First Phone</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <div style="text-align:center; margin-bottom: 20px;">
<img src="{{ asset('public/admin/assets/images/FirstPhone-Logo.jpg') }}" 
     alt="First Phone Logo"
     style="max-width: 320px; width: 100%; height: auto; display: block; margin: 0 auto 20px;">

        <h3><strong>New Order Placed</strong></h3>
    </div>

<p>Hi {{ $name ?? 'Vendor' }},</p>

    <p>
        You have received a new order on <strong>First Phone</strong>. 
        Please review the order details below and process it accordingly.
    </p>

    <div style="background-color: #f4f6f8; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <p><strong>Order Number:</strong> {{ $order_number }}</p>
    </div>

    <p>
        Please make sure to update the order status on time to ensure a smooth experience for the customer.
    </p>

    <p>Thank you,<br>
    <strong>First Phone Team</strong></p>
</body>
</html>
