<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Workspace Invitation</title>
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
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .logo {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5, #3730a3);
            color: #ffffff;
            font-weight: bold;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }

        .heading {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 12px 0;
        }

        .text {
            font-size: 15px;
            line-height: 1.6;
            color: #475569;
            margin: 0 0 20px 0;
        }

        .role-badge {
            display: inline-block;
            background-color: #e0e7ff;
            color: #3730a3;
            font-weight: 600;
            font-size: 13px;
            padding: 4px 12px;
            border-radius: 9999px;
            margin-bottom: 20px;
            text-transform: capitalize;
        }

        .message-box {
            background-color: #f1f5f9;
            border-left: 4px solid #6366f1;
            padding: 16px;
            border-radius: 8px;
            font-style: italic;
            font-size: 14px;
            color: #334155;
            margin-bottom: 28px;
        }

        .btn {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            padding: 14px 32px;
            border-radius: 10px;
            text-align: center;
        }

        .footer {
            margin-top: 36px;
            pt-24px;
            border-top: 1px solid #f1f5f9;
            font-size: 13px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="card">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $orgName }}"
                style="max-height: 48px; margin-bottom: 24px; border-radius: 8px;">
        @else
            <div class="logo">{{ strtoupper(substr($orgName, 0, 2)) }}</div>
        @endif

        <h1 class="heading">You're invited to join {{ $orgName }}</h1>

        <p class="text">
            <strong>{{ $inviterName }}</strong> has invited you to join the <strong>{{ $orgName }}</strong> workspace on
            KDAnalytiks.
        </p>

        <div>
            <span class="role-badge">Role: {{ $role }}</span>
        </div>

        @if($message)
            <div class="message-box">
                "{{ $message }}"
            </div>
        @endif

        <p class="text">
            As a member, you'll be able to collaborate on research surveys, access shared Socius AI intelligence, and
            contribute to organization projects.
        </p>

        <div style="text-align: center; margin: 32px 0;">
            <a href="{{ $acceptUrl }}" class="btn">Accept Invitation</a>
        </div>

        <p class="text" style="font-size: 13px; color: #64748b;">
            Note: This invitation link will expire on <strong>{{ $expiresAt }}</strong>.
        </p>

        <div class="footer">
            <p>KDAnalytiks | Research & Data Analytics Ecosystem</p>
        </div>
    </div>
</body>

</html>