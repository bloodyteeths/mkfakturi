<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Created - Facturino</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; margin: 0; padding: 40px; }
        .card { max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        .icon { font-size: 64px; margin-bottom: 20px; }
        h1 { color: #22c55e; margin: 0 0 10px; }
        p { color: #666; margin: 10px 0; }
        .details { background: #f9fafb; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: left; }
        .details p { margin: 8px 0; }
        .label { color: #888; font-size: 12px; text-transform: uppercase; }
        .value { color: #333; font-weight: 600; }
        .btn { display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✅</div>
        <h1>Partner Created!</h1>
        <p>The partner account has been created and invite email sent.</p>
        
        <div class="details">
            <p><span class="label">Company</span><br><span class="value">{{ $company_name }}</span></p>
            <p><span class="label">Email</span><br><span class="value">{{ $email }}</span></p>
            <p><span class="label">Partner ID</span><br><span class="value">#{{ $partner_id }}</span></p>
        </div>
        
        <a href="https://app-eu1.hubspot.com" class="btn">Back to HubSpot</a>
    </div>
</body>
</html>
