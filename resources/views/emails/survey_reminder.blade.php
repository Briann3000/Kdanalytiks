<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
        }

        .header {
            background: #4f46e5;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        .content {
            padding: 20px;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4f46e5;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 20px;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Survey Reminder</h2>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>We are following up on our invitation to participate in the survey:
                <strong>{{ $survey->title }}</strong>.
            </p>

            <p>If you haven't had a chance to share your thoughts, it's not too late! Your responses are valuable to us
                and help us improve.</p>

            <p>Please click the button below to resume or start the survey.</p>

            <div style="text-align: center;">
                <a href="{{ $inviteUrl }}" class="button">Resume Survey</a>
            </div>

            <p style="margin-top: 30px; font-size: 13px;">If you're having trouble clicking the button, copy and paste
                the URL below into your web browser:<br>
                <a href="{{ $inviteUrl }}">{{ $inviteUrl }}</a>
            </p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} KDAnalytics. All rights reserved.</p>
        </div>
    </div>
</body>

</html>