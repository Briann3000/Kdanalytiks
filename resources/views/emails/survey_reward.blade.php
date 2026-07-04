<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ __('Reward Earned!') }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 40px;
            border: 1px solid #eee;
            border-radius: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 24px;
            font-weight: 900;
            color: #4f46e5;
            text-decoration: none;
        }

        .icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .content {
            text-align: center;
        }

        h1 {
            color: #111827;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        p {
            color: #4b5563;
            margin-bottom: 20px;
        }

        .reward-badge {
            background: #f0fdf4;
            border: 2px solid #bbf7d0;
            color: #166534;
            padding: 20px;
            border-radius: 16px;
            display: inline-block;
            margin-bottom: 30px;
        }

        .reward-amount {
            font-size: 32px;
            font-weight: 900;
            display: block;
        }

        .reward-label {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn {
            background: #4f46e5;
            color: white !important;
            padding: 16px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            display: inline-block;
            transition: background 0.3s ease;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <a href="{{ config('app.url') }}" class="logo">KDAnalytiks</a>
        </div>

        <div class="content">
            <div class="icon">🎉</div>
            <h1>{{ __('Thank You for Your Contribution!') }}</h1>
            <p>{{ __('You have successfully completed the survey:') }} <strong>{{ $survey->title }}</strong></p>

            @if($rewardAmount > 0)
                <div class="reward-badge">
                    <span class="reward-label">{{ __('Reward Added to Wallet') }}</span>
                    <span class="reward-amount">{{ number_format($rewardAmount, 2) }} {{ $currency }}</span>
                </div>

                <p>{{ __('Your reward has been automatically added to your KDAnalytiks wallet. You can view your balance and initiate withdrawals at any time.') }}
                </p>
            @else
                <p>{{ __('Your response has been successfully recorded. We appreciate your time and insights!') }}</p>
            @endif

            <a href="{{ $loginUrl }}" class="btn">{{ __('Sign in to Your Account') }}</a>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} KDAnalytiks. {{ __('All rights reserved.') }}</p>
            <p>{{ __('If you did not participate in this survey, please ignore this email.') }}</p>
        </div>
    </div>
</body>

</html>