<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isPending ? __('Survey Ownership Transfer Invitation') : __('Survey Ownership Transferred') }}</title>
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
            background-color: #f8fafc;
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

        .ownership-badge {
            display: inline-block;
            background-color: #fef3c7;
            color: #92400e;
            font-weight: 700;
            font-size: 12px;
            padding: 5px 12px;
            border-radius: 9999px;
            margin-top: 10px;
            border: 1px solid #fde68a;
        }

        .btn-wrapper {
            text-align: center;
            margin: 34px 0 24px 0;
        }

        .btn {
            display: inline-block;
            background-color: #d97706;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            padding: 14px 36px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 14px rgba(217, 119, 6, 0.35);
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

        @if($isPending)
            <h1 class="heading">{{ __("You're invited to take ownership of a survey") }}</h1>
            <p class="text">
                <strong>{{ $previousOwnerName }}</strong> ({{ $previousOwnerEmail }}) has invited you to take full primary
                ownership of the following survey on KDAnalytiks:
            </p>
        @else
            <h1 class="heading">{{ __("Survey Ownership Transferred") }}</h1>
            <p class="text">
                <strong>{{ $previousOwnerName }}</strong> ({{ $previousOwnerEmail }}) has transferred full primary ownership
                of the following survey to you:
            </p>
        @endif

        <div class="survey-card">
            <p class="survey-card-sub">{{ __('Project Title') }}</p>
            <p class="survey-card-title">{{ $surveyTitle }}</p>
            <span class="ownership-badge">
                👑 {{ $isPending ? __('Pending Primary Owner') : __('Primary Owner') }}
            </span>
        </div>

        <p class="text">
            @if($isPending)
                {{ __('As the new owner, you will have complete administrative control over this survey, including questions, responses, settings, and team collaborators. Simply create an account or sign in using this email address to claim ownership.') }}
            @else
                {{ __('You now have complete administrative control over this survey, including questions, responses, settings, and team collaborators.') }}
            @endif
        </p>

        <div class="btn-wrapper">
            <a href="{{ $actionUrl }}" class="btn">
                @if($isPending)
                    {{ __('Claim Ownership & Get Started') }}
                @else
                    {{ __('Open Survey Dashboard') }}
                @endif
            </a>
        </div>

        @if($isPending)
            <p class="text" style="font-size: 13px; color: #64748b; text-align: center;">
                {{ __("If you don't have a KDAnalytiks account yet, clicking the button above will let you register and instantly receive ownership of this survey.") }}
            </p>
        @endif

        <div class="footer">
            <p>{{ __('KDAnalytiks — Next-Generation Research & Field Data Collection Platform') }}</p>
            <p>{{ __('If you believe you received this invitation by mistake, you can safely ignore this email.') }}</p>
        </div>
    </div>
</body>

</html>