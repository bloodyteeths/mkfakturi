<!DOCTYPE html>
<html lang="en">

<head>
    <title>Feature Disabled</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            background: #F9FBFF;
        }

        .message-container {
            max-width: 600px;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .icon {
            font-size: 48px;
            color: #FFA500;
            margin-bottom: 20px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: #595959;
            margin-bottom: 10px;
        }

        .message {
            font-size: 16px;
            color: #A5ACC1;
            line-height: 1.5;
        }
    </style>
</head>

<body>
    <div class="message-container">
        <div class="icon">⚠</div>
        <div class="title">Feature Not Available</div>
        <div class="message">
            {{ $message ?? 'This feature is currently disabled. Please contact your administrator to enable it.' }}
        </div>
    </div>
</body>

</html>

<!-- CLAUDE-CHECKPOINT -->
