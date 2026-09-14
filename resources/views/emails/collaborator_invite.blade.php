<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Survey Collaboration Invitation') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 40px 20px;
        }

        .card {
            max-width: 580px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 40px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .logo {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2271b1, #135e96);
            color: #ffffff;
            font-weight: 900;
            font-size: 20px;
            text-align: center;
            line-height: 44px;
            display: inline-block;
        }

        .brand-title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .heading {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 14px 0;
            line-height: 1.3;
        }

        .text {
            font-size: 15px;
            line-height: 1.6;
            color: #475569;
            margin: 0 0 20px 0;
        }

        .survey-card {
            background-color: #f1f5f9;
            border-radius: 14px;
            padding: 20px;
            margin: 24px 0;
            border: 1px solid #e2e8f0;
        }

        .survey-card-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 6px 0;
        }

        .survey-card-sub {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }

        .permissions-heading {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin: 20px 0 10px 0;
        }

        .permission-badge {
            display: inline-block;
            background-color: #eff6ff;
            color: #1d4ed8;
            font-weight: 600;
            font-size: 12px;
            padding: 5px 12px;
            border-radius: 9999px;
            margin: 0 6px 6px 0;
            border: 1px solid #bfdbfe;
        }

        .btn-wrapper {
            text-align: center;
            margin: 34px 0 24px 0;
        }

        .btn {
            display: inline-block;
            background-color: #2271b1;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            padding: 14px 36px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 14px rgba(34, 113, 177, 0.35);
        }

        .footer {
            margin-top: 36px;
            padding-top: 24px;
            border-top: 1px solid #f1f5f9;
            font-size: 12px;
            color: #94a3b8;
            text-align: center;
            line-height: 1.5;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="brand-header">
            <div class="logo">KD</div>
            <div>
                <h3 class="brand-title">KDAnalytiks</h3>
            </div>
        </div>

        <h1 class="heading">{{ __("You're invited to collaborate") }}</h1>

        <p class="text">
            <strong>{{ $inviterName }}</strong> ({{ $inviterEmail }}) has invited you to collaborate on their project on
            KDAnalytiks.
        </p>

        <div class="survey-card">
            <p class="survey-card-sub">{{ __('Project Title') }}</p>
            <p class="survey-card-title">{{ $surveyTitle }}</p>
        </div>

        <div class="permissions-heading">{{ __('Granted Permissions') }}</div>
        <div>
            @foreach($permissionsList as $permission)
                <span class="permission-badge">{{ $permission }}</span>
            @endforeach
        </div>

        <div class="btn-wrapper">
            <a href="{{ $actionUrl }}" class="btn">
                @if($isRegistered)
                    {{ __('Open Project in KDAnalytiks') }}
                @else
                    {{ __('Accept Invitation & Get Started') }}
                @endif
            </a>
        </div>

        @if(!$isRegistered)
            <p class="text" style="font-size: 13px; color: #64748b; text-align: center;">
                {{ __('If you do not have an account yet, clicking above will allow you to sign up and immediately access this project.') }}
            </p>
        @endif

        <div class="footer">
            <p>{{ __('KDAnalytiks — Next-Generation Research & Field Data Collection Platform') }}</p>
            <p>{{ __('If you believe you received this invitation by mistake, you can safely ignore this email.') }}</p>
        </div>
    </div>
</body>

</html>