<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $survey->title }} — Survey Summary</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.5;
            background: #fff;
        }

        .header {
            background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
            color: white;
            padding: 24px 32px;
            margin-bottom: 20px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-logo {
            max-height: 48px;
            max-width: 140px;
            margin-bottom: 8px;
            background: white;
            padding: 4px;
            border-radius: 6px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
            color: #ffffff;
        }

        .header .org-name {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 4px;
        }

        .header .meta {
            font-size: 9px;
            opacity: 0.85;
            letter-spacing: 0.3px;
            margin-top: 4px;
        }

        .badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 8px;
            margin-right: 4px;
        }

        .section {
            margin: 0 32px 20px;
        }

        .section-title {
            font-size: 8px;
            font-weight: 800;
            color: #94a3b8;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 12px;
        }

        .question-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .question-num {
            font-size: 8px;
            font-weight: 800;
            color: #2271b1;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .question-text {
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
        }

        .freq-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .freq-table th {
            background: #e8f0fb;
            color: #2271b1;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 5px 8px;
            text-align: left;
        }

        .freq-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 9px;
            color: #334155;
        }

        .freq-table tr:last-child td {
            border-bottom: none;
        }

        .bar-container {
            width: 100px;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            display: inline-block;
            vertical-align: middle;
        }

        .bar-fill {
            height: 6px;
            background: #2271b1;
            border-radius: 3px;
        }

        .pct {
            color: #64748b;
            font-size: 8px;
            font-weight: 700;
        }

        .no-data {
            font-size: 9px;
            color: #94a3b8;
            font-style: italic;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 8px 32px;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #94a3b8;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
    </style>
</head>

<body>

    <div class="header">
        <table class="header-table">
            <tr>
                <td style="vertical-align: top;">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" alt="Export Logo" class="header-logo">
                    @endif
                    @if($survey->export_org_name)
                        <div class="org-name">{{ $survey->export_org_name }}</div>
                    @endif
                    <h1>{{ $survey->title }}</h1>
                    @if($survey->description)
                        <p class="meta">{{ Str::limit($survey->description, 180) }}</p>
                    @endif
                    <div style="margin-top: 6px;">
                        <span class="badge">{{ __("Survey Summary Report") }}</span>
                        <span class="badge">{{ $exportedAt }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">{{ __("Question Frequency Analysis") }}</div>

        @forelse($summaryData as $i => $item)
            @php 
                                                    $q = $item['question'];
                $total = $item['total']; 
            @endphp
            <div class="question-card">
                <div class="question-num">Question {{ $i + 1 }} &mdash; {{ strtoupper($q->type ?? 'TEXT') }}</div>
                <div class="question-text">{{ $q->text ?? 'Question' }}</div>

                @if($total === 0)
                    <p class="no-data">No responses recorded for this question.</p>
                @else
                    <table class="freq-table">
                        <thead>
                            <tr>
                                <th>{{ __("Response") }}</th>
                                <th>{{ __("Count") }}</th>
                                <th>{{ __("Distribution") }}</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($item['frequencies'] as $value => $count)
                                @php $pct = round(($count / $total) * 100, 1); @endphp
                                <tr>
                                    <td>{{ $value !== '' ? $value : '(blank)' }}</td>
                                    <td>{{ $count }}</td>
                                    <td>
                                        <div class="bar-container">
                                            <div class="bar-fill" style="width: {{ $pct }}%;"></div>
                                        </div>
                                    </td>
                                    <td class="pct">{{ $pct }}%</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td style="font-weight:700;color:#0f172a;">{{ __("Total Answers") }}</td>
                                <td style="font-weight:700;color:#2271b1;">{{ $total }}</td>
                                <td></td>
                                <td class="pct">100%</td>
                            </tr>
                        </tbody>
                    </table>
                @endif
            </div>
        @empty
            <div class="question-card">
                <p class="no-data">No questions or survey fields found to summarize.</p>
            </div>
        @endforelse
    </div>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td style="text-align: left;">{{ $survey->export_org_name ?: $survey->title }}</td>
                <td style="text-align: right;">
                    Generated {{ $exportedAt }}
                    @if(!$survey->remove_kd_branding)
                        &mdash; KDAnalytiks
                    @endif
                </td>
            </tr>
        </table>
    </div>

</body>

</html>