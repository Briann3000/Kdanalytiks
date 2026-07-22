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
            padding: 28px 32px;
            margin-bottom: 24px;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }

        .header .meta {
            font-size: 9px;
            opacity: 0.8;
            letter-spacing: 0.5px;
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
            padding: 14px 16px;
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
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>{{ $survey->title }}</h1>
        @if($survey->description)
            <p class="meta">{{ Str::limit($survey->description, 180) }}</p>
        @endif
        <span class="badge">Survey Summary Report</span>
        <span class="badge">{{ $exportedAt }}</span>
    </div>

    <div class="section">
        <div class="section-title">Question Frequency Analysis</div>

        @foreach($summaryData as $i => $item)
            @php $q = $item['question'];
            $total = $item['total']; @endphp
            <div class="question-card">
                <div class="question-num">Question {{ $i + 1 }} &mdash; {{ strtoupper($q->type) }}</div>
                <div class="question-text">{{ $q->text }}</div>

                @if($total === 0)
                    <p class="no-data">No responses recorded for this question.</p>
                @else
                    <table class="freq-table">
                        <thead>
                            <tr>
                                <th>Response</th>
                                <th>Count</th>
                                <th>Distribution</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($item['frequencies'] as $value => $count)
                                @php $pct = round(($count / $total) * 100, 1); @endphp
                                <tr>
                                    <td>{{ $value ?: '(blank)' }}</td>
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
                                <td style="font-weight:700;color:#0f172a;">Total</td>
                                <td style="font-weight:700;color:#2271b1;">{{ $total }}</td>
                                <td></td>
                                <td class="pct">100%</td>
                            </tr>
                        </tbody>
                    </table>
                @endif
            </div>
        @endforeach
    </div>

    <div class="footer">
        <span>{{ $survey->title }}</span>
        <span>Generated {{ $exportedAt }} &mdash; KM Survey Tool</span>
    </div>

</body>

</html>