<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $survey->title }} - Analytical Report</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #111827;
            margin: 0;
            font-size: 28px;
        }

        .header p {
            color: #6b7280;
            margin-top: 5px;
            font-size: 14px;
        }

        .summary-box {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }

        .summary-box h3 {
            margin: 0 0 10px 0;
            color: #374151;
        }

        .summary-stats {
            display: inline-block;
            width: 30%;
            text-align: center;
        }

        .summary-stats strong {
            display: block;
            font-size: 20px;
            color: #4f46e5;
        }

        .question-section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }

        .question-title {
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 18px;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #e5e7eb;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f9fafb;
            color: #4b5563;
        }

        .bar-container {
            width: 100%;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            height: 12px;
            margin-top: 5px;
        }

        .bar-fill {
            background-color: #4f46e5;
            height: 100%;
        }

        .text-answer-list {
            list-style-type: none;
            padding: 0;
        }

        .text-answer-list li {
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
            color: #4b5563;
        }

        .text-answer-list li::before {
            content: "•";
            color: #9ca3af;
            font-weight: bold;
            display: inline-block;
            width: 1em;
            margin-left: -1em;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>{{ $survey->title }}</h1>
        <p>Analytical Executive Report - Generated {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <div class="summary-box">
        <h3>Survey Overview</h3>
        <div class="summary-stats">
            <strong>{{ $responses->count() }}</strong>
            Total Responses
        </div>
        <div class="summary-stats">
            <strong>{{ count((array) $analysis) }}</strong>
            Questions Analyzed
        </div>
        <div class="summary-stats">
            <strong>{{ $survey->created_at->format('M d, Y') }}</strong>
            Launch Date
        </div>
    </div>

    @foreach($analysis as $index => $item)
        <div class="question-section">
            <div class="question-title">
                Q{{ $index + 1 }}: {{ $item['label'] }}
            </div>

            @if($item['isChartable'])
                @php
                    // Recalculate frequencies for display in PDF tabular format
                    $freqs = array_count_values((array) $item['answers']);
                    arsort($freqs);
                    $totalAnswersForThisQ = count((array) $item['answers']) ?: 1; // prevent div by zero
                @endphp

                <table>
                    <thead>
                        <tr>
                            <th width="50%">Choice</th>
                            <th width="15%">Count</th>
                            <th width="35%">Distribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($freqs as $choice => $count)
                            @php $percentage = round(($count / $totalAnswersForThisQ) * 100); @endphp
                            <tr>
                                <td>{{ $choice }}</td>
                                <td>{{ $count }} <small style="color:#6b7280;">({{ $percentage }}%)</small></td>
                                <td>
                                    <div class="bar-container">
                                        <div class="bar-fill" style="width: {{ $percentage }}%;"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <ul class="text-answer-list">
                    @forelse(array_slice((array) $item['answers'], 0, 15) as $answer)
                        <li>{{ htmlspecialchars(is_array($answer) ? json_encode($answer) : $answer) }}</li>
                    @empty
                        <li style="color: #9ca3af; font-style: italic;">No text responses recorded.</li>
                    @endforelse
                </ul>
                @if(count((array) $item['answers']) > 15)
                    <p style="font-size: 12px; color: #6b7280; font-style: italic;">(Showing only latest 15 text responses to
                        conserve space. Generate CSV for full dump.)</p>
                @endif
            @endif
        </div>
    @endforeach

    <div class="footer">
        Powered by KMSurveyTool™ | Executive Analytics Export
    </div>

</body>

</html>