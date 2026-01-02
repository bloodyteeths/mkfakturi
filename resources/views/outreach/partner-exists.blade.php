<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Exists - Facturino</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; margin: 0; padding: 40px; }
        .card { max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        .icon { font-size: 64px; margin-bottom: 20px; }
        h1 { color: #f59e0b; margin: 0 0 10px; }
        p { color: #666; margin: 10px 0; }
        .btn { display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">ℹ️</div>
        <h1>Partner Already Exists</h1>
        <p>A partner account was already created for <strong>{{ $deal_name }}</strong>.</p>
        <p>Partner ID: #{{ $partner_id }}</p>
        <a href="https://app-eu1.hubspot.com" class="btn">Back to HubSpot</a>
    </div>
</body>
</html>
