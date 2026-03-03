<!DOCTYPE html>
<html>
<head>
    <title>New Mobile Request - First Phone</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f9fafb; padding:20px;">

    <div style="max-width:600px; margin:auto; background:#ffffff; padding:20px; border-radius:10px;">

        <!-- Logo -->
        <div style="text-align:center; margin-bottom: 20px;">
            <img src="{{ asset('public/admin/assets/images/FirstPhone-Logo.jpg') }}" 
                 alt="First Phone Logo"
                 style="max-width: 220px; width: 100%; height: auto;">
        </div>

        <!-- Heading -->
        <h2 style="color:#333; text-align:center;">New Mobile Request Nearby</h2>

        <p>Dear Vendor,</p>
        
        <p>
            A new mobile request has been posted near your location. Here are the details:
        </p>

        <!-- Request Details Box -->
        <div style="background-color:#f4f6f8; padding:18px; border-radius:8px; margin:20px 0;">

            <p><strong>Customer Name:</strong> {{ $data['data']->customer_name ?? 'N/A' }}</p>

            <p><strong>Brand:</strong> {{ $data['data']->brand_name ?? 'Unknown Brand' }}</p>

            <p><strong>Model:</strong> {{ $data['data']->model_name ?? 'Unknown Model' }}</p>

            <p><strong>Price Range:</strong> 
                Rs {{ $data['data']->min_price ?? '0' }} - Rs {{ $data['data']->max_price ?? '0' }}
            </p>

            <p><strong>ROM:</strong> {{ $data['data']->storage ?? 'N/A' }}</p>

            <p><strong>RAM:</strong> {{ $data['data']->ram ?? 'N/A' }}</p>

            <p><strong>Color:</strong> {{ $data['data']->color ?? 'N/A' }}</p>

            <p><strong>Condition:</strong> {{ ucfirst($data['data']->condition ?? 'N/A') }}</p>

            <p><strong>Location:</strong> {{ $data['data']->location ?? 'N/A' }}</p>

            @if(!empty($data['data']->description))
                <p><strong>Description:</strong> {{ $data['data']->description }}</p>
            @endif

        </div>

        <p>
            Please check the app dashboard to respond to this request as soon as possible.
        </p>

        <p>
            Regards,<br>
            <strong>First Phone Team</strong>
        </p>

    </div>

</body>
</html>