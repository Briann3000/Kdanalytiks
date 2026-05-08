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
            border-bottom: 2px solid
                {{ $branding['brandColor'] ?? '#dc2626' }}
            ;
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
            color:
                {{ $branding['brandColor'] ?? '#dc2626' }}
            ;
        }

        .question-section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }

        .question-title {
            background-color: #f3f4f6;
            color:
                {{ $branding['brandColor'] ?? '#dc2626' }}
            ;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 18px;
            margin-bottom: 15px;
            border-left: 5px solid
                {{ $branding['brandColor'] ?? '#dc2626' }}
            ;
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
            background-color:
                {{ $branding['brandColor'] ?? '#dc2626' }}
            ;
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

        .disclaimer-box {
            margin-top: 40px;
            padding: 20px;
            background-color: #fffaf0;
            border: 1px solid #feebc8;
            border-radius: 8px;
            color: #744210;
            font-size: 11px;
            page-break-inside: avoid;
        }

        .disclaimer-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: block;
        }
    </style>
</head>

<body>

    <div class="header">
        @if(isset($branding) && ($branding['customLogo'] || $branding['customOrgName']))
            <div style="margin-bottom: 20px;">
                @if($branding['customLogo'])
                    @php $logoPath = storage_path('app/public/' . $branding['customLogo']); @endphp
                    <img src="{{ $logoPath }}" style="max-height: 60px; max-width: 200px; object-fit: contain;">
                @endif
                @if($branding['customOrgName'])
                    <div style="font-size: 18px; font-weight: bold; color: #4b5563; margin-top: 5px;">
                        {{ $branding['customOrgName'] }}
                    </div>
                @endif
            </div>
        @endif
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

    @if(!empty($aiSummary))
        <div class="summary-box"
            style="background-color: #eef2ff; border: 1px solid #c3dafe; text-align: left; margin-bottom: 30px;">
            <h3 style="color: #4338ca; margin-top: 0;">Chapter 4: Executive Thematic Analysis</h3>
            <div style="font-size: 13px; color: #374151; white-space: pre-wrap;">{{ $aiSummary }}</div>
        </div>
    @endif

    @foreach($analysis as $index => $item)
        <div class="question-section">
            <div class="question-title">
                Q{{ $index + 1 }}: {{ $item['label'] }}
            </div>

            @if($item['isChartable'])
                @if(!empty($item['chartUrl']))
                    <div style="text-align: center; margin-bottom: 20px;">
                        <img src="{{ $item['chartBase64'] ?? $item['chartUrl'] }}"
                            style="max-width: 100%; height: auto; max-height: 250px;">
                    </div>
                @endif

                @if(!empty($item['aiInsight']) && is_string($item['aiInsight']))
                    <div
                        style="background-color: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                        <span
                            style="font-size: 10px; font-weight: bold; color: #15803d; text-transform: uppercase; display: block; margin-bottom: 5px;">AI
                            Statistical Interpretation</span>
                        <p style="font-size: 12px; color: #374151; margin: 0; line-height: 1.4;">{{ $item['aiInsight'] }}</p>
                    </div>
                @endif
                <table>
                    <thead>
                        <tr>
                            <th width="50%">Choice</th>
                            <th width="20%">Count</th>
                            <th width="30%">Distribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($item['stats'] as $stat)
                            @if(!($stat['is_missing'] ?? false))
                                <tr>
                                    <td>{{ $stat['value'] }}</td>
                                    <td>{{ $stat['count'] }} <small style="color:#6b7280;">({{ $stat['percentage'] }}%)</small></td>
                                    <td>
                                        <div class="bar-container">
                                            <div class="bar-fill" style="width: {{ $stat['percentage'] }}%;"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f9fafb; font-weight: bold;">
                            <td>TOTAL</td>
                            <td>{{ $item['answered_count'] }} <small style="color:#6b7280;">(100%)</small></td>
                            <td>
                                <div class="bar-container">
                                    <div class="bar-fill" style="width: 100%;"></div>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            @else
                @if(!empty($item['aiInsight']) && is_array($item['aiInsight']))
                    <div
                        style="background-color: #f0fdf4; border: 1px solid #bbf7d0; padding: 15px; border-radius: 12px; margin-bottom: 25px;">
                        <div style="margin-bottom: 15px; border-bottom: 1px solid #dcfce7; padding-bottom: 10px;">
                            <span style="font-size: 10px; font-weight: bold; color: #15803d; text-transform: uppercase;">Sentiment
                                Breakdown</span>
                            <div
                                style="height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; margin-top: 6px; width: 100%;">
                                <div
                                    style="width: {{ $item['aiInsight']['sentiment_breakdown']['Positive'] }}%; background: #10b981; float: left; height: 100%;">
                                </div>
                                <div
                                    style="width: {{ $item['aiInsight']['sentiment_breakdown']['Neutral'] }}%; background: #fbbf24; float: left; height: 100%;">
                                </div>
                                <div
                                    style="width: {{ $item['aiInsight']['sentiment_breakdown']['Negative'] }}%; background: #ef4444; float: left; height: 100%;">
                                </div>
                            </div>
                            <div style="font-size: 9px; margin-top: 4px; color: #166534; font-weight: bold;">
                                Positive: {{ $item['aiInsight']['sentiment_breakdown']['Positive'] }}% |
                                Neutral: {{ $item['aiInsight']['sentiment_breakdown']['Neutral'] }}% |
                                Negative: {{ $item['aiInsight']['sentiment_breakdown']['Negative'] }}%
                            </div>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <span style="font-size: 10px; font-weight: bold; color: #15803d; text-transform: uppercase;">Key
                                Thematic Mapping</span>
                            <table style="width: 100%; border: none; margin-top: 5px;">
                                @foreach($item['aiInsight']['key_themes'] as $theme)
                                    <tr>
                                        <td
                                            style="border: none; padding: 4px 0; vertical-align: top; width: 30%; font-size: 11px; font-weight: bold; color: #166534;">
                                            {{ $theme['theme'] }}</td>
                                        <td style="border: none; padding: 4px 0; vertical-align: top; font-size: 11px; color: #374151;">
                                            {{ $theme['explanation'] }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>

                        <div>
                            <span
                                style="font-size: 10px; font-weight: bold; color: #15803d; text-transform: uppercase;">Representative
                                Voter Quotes</span>
                            @foreach($item['aiInsight']['representative_quotes'] as $quote)
                                <div
                                    style="font-size: 11px; font-style: italic; color: #4b5563; margin-top: 8px; padding-left: 12px; border-left: 3px solid #10b981; line-height: 1.4;">
                                    "{{ $quote }}"
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <ul class="text-answer-list">
                    @forelse(array_slice((array) $item['answers'], 0, 15) as $answer)
                        @php
                            $answerStr = is_array($answer) ? json_encode($answer) : (string) $answer;
                            $isSignature = str_contains($answerStr, 'base64,');
                            $isMedia = str_starts_with($answerStr, 'uploads/');
                            $isImage = $isMedia && in_array(strtolower(pathinfo($answerStr, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                        @endphp
                        <li style="margin-bottom: 10px;">
                            @if($isSignature)
                                <div style="margin-top: 5px;">
                                    <span style="font-size: 10px; color: #6b7280; display: block; margin-bottom: 3px;">Captured
                                        Signature:</span>
                                    <img src="{{ $answerStr }}"
                                        style="max-height: 60px; border: 1px solid #e5e7eb; border-radius: 4px;">
                                </div>
                            @elseif($isImage)
                                <div style="margin-top: 5px;">
                                    <span style="font-size: 10px; color: #6b7280; display: block; margin-bottom: 3px;">Uploaded
                                        Image:</span>
                                    <img src="{{ public_path('storage/' . $answerStr) }}"
                                        style="max-height: 120px; border: 1px solid #e5e7eb; border-radius: 8px;">
                                </div>
                            @elseif($isMedia)
                                <span style="color: #4f46e5; font-weight: bold;">[Media File: {{ $answerStr }}]</span>
                            @else
                                {{ $answerStr }}
                            @endif
                        </li>
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

    @if(isset($isPremium) && $isPremium)
        <div style="page-break-before: always;">
            <div class="header">
                <h2 style="text-transform: uppercase; color: {{ $branding['brandColor'] ?? '#dc2626' }};">Appendix: Raw Data
                    Dump</h2>
                <p>Record of respondent submissions</p>
                @if($responses->count() > 50)
                    <p style="font-size: 10px; color: #ef4444; font-style: italic;">Note: To prevent PDF generation timeouts,
                        this appendix is limited to the first 50 responses. Please export as CSV or DOCX to view the full
                        dataset.</p>
                @endif
            </div>

            @foreach($responses->take(50) as $resp)
                <div style="margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                    <div style="background-color: #f9fafb; padding: 8px; border-radius: 4px; margin-bottom: 10px;">
                        <span style="font-size: 10px; font-weight: bold; color: #6b7280;">RESPONSE ID: #{{ $resp->id }} |
                            SUBMITTED: {{ $resp->created_at->format('M d, Y H:i') }}</span>
                    </div>

                    <table style="font-size: 10px; border: none;">
                        @foreach($analysis as $item)
                            @php
                                $ans = null;
                                if (!empty($survey->json_schema) && $survey->json_schema !== '[]') {
                                    $data = json_decode($resp->answers->first()->value ?? '[]', true);
                                    foreach ((array) $data as $entry) {
                                        if (isset($entry['name']) && $entry['name'] === $item['id']) {
                                            $ans = $entry['userData'] ?? null;
                                            break;
                                        }
                                    }
                                } else {
                                    $ans = $resp->answers->where('question_id', $item['id'])->first()?->value;
                                }
                            @endphp
                            <tr>
                                <td style="width: 30%; font-weight: bold; background-color: #fcfcfc; border: 1px solid #f3f4f6;">
                                    {{ $item['label'] }}</td>
                                <td style="border: 1px solid #f3f4f6;">
                                    @if(is_array($ans))
                                        {{ implode(', ', $ans) }}
                                    @elseif(str_contains((string) $ans, 'base64,'))
                                        [Signature Captured]
                                    @elseif(str_starts_with((string) $ans, 'uploads/'))
                                        [Media: {{ basename($ans) }}]
                                    @else
                                        {{ $ans ?: '—' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endforeach
        </div>
    @endif

    <div class="disclaimer-box">
        <span class="disclaimer-title">Data Integrity & Validation Disclaimer</span>
        <p>This report has been automatically generated by KMSurveyTool. The statistics and AI insights provided are
            based on raw data collected from survey respondents. While we employ rigorous data validation protocols,
            PRC™ Consulting does not guarantee the absolute accuracy of individual qualitative interpretations provided
            by the AI engine. This report should be used as a strategic guide and cross-referenced with the raw dataset
            for critical decision-making.</p>
    </div>

    @if(!isset($branding) || $branding['showKmBranding'])
        <div class="footer">
            Powered by KMSurveyTool™ | Executive Analytics Export
        </div>
    @endif

</body>

</html>