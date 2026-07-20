<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $thread->title ?: 'Socius Chat Export' }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 13px;
            line-height: 1.7;
            color: #1a1a2e;
            margin: 0;
            padding: 30px 40px;
            background: #ffffff;
        }

        /* ── Header ── */
        .export-header {
            border-bottom: 3px solid #f59e0b;
            padding-bottom: 14px;
            margin-bottom: 28px;
        }

        .export-header h1 {
            margin: 0 0 4px;
            font-size: 20px;
            font-weight: 800;
            color: #1a1a2e;
        }

        .export-header p {
            margin: 0;
            font-size: 11px;
            color: #888;
        }

        /* ── Message bubbles ── */
        .message {
            margin-bottom: 24px;
            padding: 14px 18px;
            border-radius: 10px;
            page-break-inside: avoid;
        }

        .message-user {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
        }

        .message-socius {
            background-color: #f8fafc;
            border-left: 4px solid #6366f1;
        }

        .role-label {
            font-weight: 800;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 6px;
            color: #555;
        }

        .meta-label {
            font-size: 10px;
            color: #aaa;
            margin-bottom: 10px;
        }

        /* ── Markdown content styling ── */
        .content h1,
        .content h2,
        .content h3,
        .content h4,
        .content h5,
        .content h6 {
            font-weight: 700;
            color: #1a1a2e;
            margin: 16px 0 6px;
            line-height: 1.3;
        }

        .content h1 {
            font-size: 17px;
        }

        .content h2 {
            font-size: 15px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
        }

        .content h3 {
            font-size: 13px;
        }

        .content h4,
        .content h5,
        .content h6 {
            font-size: 12px;
        }

        .content p {
            margin: 8px 0;
        }

        .content strong {
            font-weight: 700;
        }

        .content em {
            font-style: italic;
        }

        .content ul,
        .content ol {
            margin: 8px 0 8px 20px;
            padding: 0;
        }

        .content li {
            margin: 3px 0;
        }

        .content blockquote {
            border-left: 3px solid #d1d5db;
            padding: 6px 12px;
            margin: 10px 0;
            color: #555;
            background: #f9fafb;
        }

        .content code {
            background: #f1f5f9;
            padding: 1px 5px;
            border-radius: 4px;
            font-size: 11px;
            font-family: 'Courier New', monospace;
        }

        .content pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 12px;
            border-radius: 6px;
            font-size: 11px;
            overflow: hidden;
            white-space: pre-wrap;
            word-break: break-all;
        }

        /* ── Tables ── */
        .content table {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0;
            font-size: 12px;
            page-break-inside: avoid;
        }

        .content table th {
            background-color: #6366f1;
            color: #fff;
            font-weight: 700;
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #4f46e5;
        }

        .content table td {
            border: 1px solid #e5e7eb;
            padding: 7px 10px;
            vertical-align: top;
        }

        .content table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* ── Chart / Diagram images ── */
        .chart-img,
        .mermaid-img {
            text-align: center;
            margin: 14px 0;
            page-break-inside: avoid;
        }

        .chart-img img,
        .mermaid-img img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        /* ── Footer ── */
        .export-footer {
            text-align: center;
            font-size: 10px;
            color: #bbb;
            margin-top: 50px;
            border-top: 1px solid #f1f5f9;
            padding-top: 14px;
        }
    </style>
</head>

<body>
    <div class="export-header">
        <h1>{{ $thread->title ?: ($isSingleMessage ? 'Socius Report' : 'Socius Chat Export') }}</h1>
        <p>Exported on {{ now()->toDayDateTimeString() }} &nbsp;|&nbsp; User: {{ Auth::user()->name }}</p>
    </div>

    @foreach($processedMessages as $msg)
        @if(!$isSingleMessage || $msg['role'] === 'assistant')
            <div class="message {{ $msg['role'] === 'user' ? 'message-user' : 'message-socius' }}">
                @if(!$isSingleMessage)
                    <div class="role-label">{{ $msg['role'] === 'user' ? 'User' : 'Socius' }}</div>
                    <div class="meta-label">{{ \Carbon\Carbon::parse($msg['created_at'])->format('Y-m-d H:i') }}</div>
                @endif
                <div class="content">{!! $msg['html'] !!}</div>
            </div>
        @endif
    @endforeach

    <div class="export-footer">
        Generated by KDAnalytiks &mdash; Socius AI
    </div>
</body>

</html>