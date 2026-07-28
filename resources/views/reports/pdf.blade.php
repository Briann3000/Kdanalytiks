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
            style="background-color: #f9fafb; border: 1px solid #e5e7eb; text-align: left; margin-bottom: 30px;">
            <h3 style="color: #111827; margin-top: 0;">Executive Thematic Analysis</h3>
            <div style="font-size: 13px; color: #374151; line-height: 1.6;">
                @foreach(array_filter(preg_split('/\n+/', trim($aiSummary))) as $aiPara)
                    <p style="margin: 0 0 8px 0;">{{ preg_replace('/\*\*(.*?)\*\*/', '$1', trim($aiPara)) }}</p>
                @endforeach
            </div>
        </div>
    @endif

    @php $qNum = 1; @endphp
    @foreach($analysis as $index => $item)
        @php
            $labelLower = strtolower($item['label'] ?? '');
            if (str_contains($labelLower, 'respondent id') || str_contains($labelLower, 'respondent_id')) {
                continue;
            }
        @endphp

        <div class="question-section">
            <div class="question-title" style="background-color: #f9fafb; color: #111827; border-left: 5px solid {{ $branding['brandColor'] ?? '#4f46e5' }};">
                Q{{ $qNum }}: {{ $item['label'] }}
            </div>
            @php $qNum++; @endphp

            @if($item['isChartable'])
                @if(!empty($item['chartUrl']) && empty($item['isLikertLike']))
                    <div style="text-align: center; margin-bottom: 20px;">
                        <img src="{{ $item['chartBase64'] ?? $item['chartUrl'] }}"
                            style="max-width: 100%; height: auto; max-height: 250px;">
                    </div>
                @endif

                @if(!empty($item['aiInsight']) && is_string($item['aiInsight']))
                    <div style="margin-bottom: 15px;">
                        @foreach(array_filter(preg_split('/\n+/', trim($item['aiInsight']))) as $aiPara)
                            <p style="font-size: 12px; color: #374151; margin: 0 0 6px 0; line-height: 1.5;">{{ preg_replace('/\*\*(.*?)\*\*/', '$1', trim($aiPara)) }}</p>
                        @endforeach
                    </div>
                @endif

                @if(!empty($item['isLikertLike']))
                    <!-- Likert Table -->
                    <table>
                        <thead>
                            <tr>
                                <th rowspan="2">Value</th>
                                @foreach($item['stats'] as $stat)
                                    @if(!($stat['is_missing'] ?? false))
                                        <th colspan="2" style="text-align: center;">{{ $stat['value'] }}</th>
                                    @endif
                                @endforeach
                            </tr>
                            <tr>
                                @foreach($item['stats'] as $stat)
                                    @if(!($stat['is_missing'] ?? false))
                                        <th style="text-align: center; font-size: 10px;">Frequency</th>
                                        <th style="text-align: center; font-size: 10px;">%</th>
                                    @endif
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $item['label'] }}</td>
                                @php
                                    $totalFreqLikert = array_sum(array_column(array_filter($item['stats'], fn($s) => !isset($s['is_missing']) || !$s['is_missing']), 'count'));
                                @endphp
                                @foreach($item['stats'] as $stat)
                                    @if(!($stat['is_missing'] ?? false))
                                        @php
                                            $percentLikert = $totalFreqLikert > 0 ? ($stat['count'] / $totalFreqLikert) * 100 : 0;
                                        @endphp
                                        <td style="text-align: center;">{{ number_format($stat['count']) }}</td>
                                        <td style="text-align: center;">{{ number_format($percentLikert, 1) }}%</td>
                                    @endif
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                @else
                    <!-- Standard 5-column table -->
                    <table>
                        <thead>
                            <tr>
                                <th>Value</th>
                                <th style="text-align: right;">Frequency</th>
                                <th style="text-align: right;">Percent</th>
                                <th style="text-align: right;">Valid Percent</th>
                                <th style="text-align: right;">Cumulative Percent</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalFreq = 0;
                                $validFreq = 0;
                                foreach($item['stats'] as $s) {
                                    if (!isset($s['is_missing']) || !$s['is_missing']) {
                                        $validFreq += $s['count'];
                                    }
                                    $totalFreq += $s['count'];
                                }
                                if ($validFreq === 0) $validFreq = $totalFreq;
                                $cumulativePerc = 0;
                            @endphp
                            @foreach($item['stats'] as $stat)
                                @php
                                    $isMissing = isset($stat['is_missing']) && $stat['is_missing'];
                                @endphp
                                @if(!($isMissing && $stat['count'] == 0))
                                    @php
                                        $percent = $totalFreq > 0 ? ($stat['count'] / $totalFreq) * 100 : 0;
                                        if ($isMissing) {
                                            $validPercent = null;
                                            $cumPercentDisplay = '-';
                                        } else {
                                            $validPercent = $validFreq > 0 ? ($stat['count'] / $validFreq) * 100 : 0;
                                            $cumulativePerc += $validPercent;
                                            $cumPercentDisplay = number_format($cumulativePerc, 1) . '%';
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $stat['value'] }}</td>
                                        <td style="text-align: right;">{{ number_format($stat['count']) }}</td>
                                        <td style="text-align: right;">{{ number_format($percent, 1) }}%</td>
                                        <td style="text-align: right;">{{ $validPercent !== null ? number_format($validPercent, 1) . '%' : '-' }}</td>
                                        <td style="text-align: right;">{{ $cumPercentDisplay }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background-color: #f9fafb; font-weight: bold;">
                                <td>Total</td>
                                <td style="text-align: right;">{{ number_format($totalFreq) }}</td>
                                <td style="text-align: right;">100.0%</td>
                                <td style="text-align: right;">100.0%</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            @else
                <!-- Qualitative Insights -->
                @if(!empty($item['aiInsight']) && is_array($item['aiInsight']))
                    <div style="margin-bottom: 20px;">
                        @php
                            $pos = $item['aiInsight']['sentiment_breakdown']['Positive'] ?? 0;
                            $neu = $item['aiInsight']['sentiment_breakdown']['Neutral'] ?? 0;
                            $neg = $item['aiInsight']['sentiment_breakdown']['Negative'] ?? 0;
                            
                            if ($pos >= 60) {
                                $sentNarr = "Sentiment analysis of responses reveals a predominantly positive tone ({$pos}% positive, {$neu}% neutral, {$neg}% negative), suggesting general satisfaction and agreement.";
                            } elseif ($neg >= 60) {
                                $sentNarr = "Sentiment analysis indicates a predominantly negative tone ({$neg}% negative, {$neu}% neutral, {$pos}% positive), highlighting core concerns among respondents.";
                            } elseif ($neu >= 50) {
                                $sentNarr = "Respondent sentiment is largely neutral ({$neu}% neutral, {$pos}% positive, {$neg}% negative), reflecting balanced or objective views.";
                            } else {
                                $sentNarr = "Responses reflect a mixed sentiment profile: {$pos}% positive, {$neu}% neutral, and {$neg}% negative, demonstrating varied perspectives.";
                            }
                        @endphp
                        <p style="font-size: 12px; color: #374151; line-height: 1.6; margin-bottom: 10px;">{{ $sentNarr }}</p>

                        @if(!empty($item['aiInsight']['key_themes']))
                            <p style="font-size: 12px; font-weight: bold; color: #111827; margin-bottom: 5px;">Key themes identified in responses:</p>
                            <ul style="margin: 0; padding-left: 18px; font-size: 12px; color: #374151; line-height: 1.7;">
                                @foreach($item['aiInsight']['key_themes'] as $theme)
                                    <li><strong>{{ $theme['theme'] ?? 'Theme' }}:</strong> {{ $theme['explanation'] ?? '' }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    @endforeach

    @if(isset($savedInferentialTests) && $savedInferentialTests->count() > 0)
        <div style="page-break-before: always;">
            <h2 style="text-transform: uppercase; color: {{ $branding['brandColor'] ?? '#111827' }}; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px;">Inferential Analysis</h2>
            <p style="font-size: 12px; color: #6b7280; font-style: italic; margin-bottom: 20px;">Significance tests, correlations, and regressions saved to the report.</p>

            @foreach($savedInferentialTests as $test)
                <div style="margin-bottom: 40px; page-break-inside: avoid;">
                    <h3 style="color: #111827; margin-bottom: 5px;">{{ $test->title }}</h3>
                    <div style="font-size: 11px; color: #4b5563; margin-bottom: 15px;">
                        <strong>Method:</strong> {{ strtoupper($test->method) }} | 
                        <strong>Variables:</strong> {{ $test->variables }}
                    </div>

                    @php
                        $data = $test->payload['data'] ?? null;
                        $method = strtolower($test->method);
                    @endphp

                    @if($data)
                        @if($method === 'crosstab' || $method === 'chisquare')
                            @php
                                $rows = $data['rows'] ?? [];
                                $cols = $data['columns'] ?? ($data['cols'] ?? []);
                                $matrix = $data['matrix'] ?? [];
                                $rowTotals = $data['rowTotals'] ?? ($data['row_totals'] ?? []);
                                $colTotals = $data['colTotals'] ?? ($data['col_totals'] ?? []);
                                $grandTotal = $data['grandTotal'] ?? ($data['grand_total'] ?? 0);
                            @endphp
                            <table style="font-size: 11px; margin-bottom: 15px;">
                                <thead>
                                    <tr style="background-color: #f9fafb;">
                                        <th>Row \ Column</th>
                                        @foreach($cols as $col)
                                            <th style="text-align: center;">{{ $col }}</th>
                                        @endforeach
                                        <th style="text-align: center;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rows as $rowVal)
                                        <tr>
                                            <td style="font-weight: bold;">{{ $rowVal }}</td>
                                            @foreach($cols as $colVal)
                                                <td style="text-align: center;">{{ number_format($matrix[$rowVal][$colVal] ?? 0) }}</td>
                                            @endforeach
                                            <td style="text-align: center; font-weight: bold;">{{ number_format($rowTotals[$rowVal] ?? 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr style="font-weight: bold; background-color: #f9fafb;">
                                        <td>Total</td>
                                        @foreach($cols as $colVal)
                                            <td style="text-align: center;">{{ number_format($colTotals[$colVal] ?? 0) }}</td>
                                        @endforeach
                                        <td style="text-align: center;">{{ number_format($grandTotal) }}</td>
                                    </tr>
                                </tfoot>
                            </table>

                            @php
                                $chiSqVal = $data['chiSquare'] ?? ($data['chi_square'] ?? null);
                            @endphp
                            @if($method === 'chisquare' && $chiSqVal !== null)
                                <div style="font-size: 11px; margin-bottom: 15px; background: #f9fafb; padding: 10px; border-radius: 6px; border: 1px solid #e5e7eb;">
                                    <strong>Chi-Square Statistic (χ²):</strong> {{ number_format($chiSqVal, 4) }} | 
                                    <strong>df:</strong> {{ $data['df'] ?? 0 }} | 
                                    <strong>p-value:</strong> {{ number_format($data['pValue'] ?? ($data['p_value'] ?? 0), 4) }}
                                    @if(isset($data['cramersV']))
                                        | <strong>Cramer's V:</strong> {{ number_format($data['cramersV'], 4) }}
                                    @endif
                                    <br>
                                    <strong>Result:</strong> {{ ($data['significant'] ?? false) ? 'Statistically Significant' : 'Not Statistically Significant' }}
                                </div>
                            @endif

                        @elseif($method === 'cronbach')
                           @php
                               $alpha = $data['alpha'] ?? 0;
                               $itemsCount = $data['k_items'] ?? ($data['items_count'] ?? 0);
                               $interp = $data['interpretation'] ?? ($data['internal_consistency'] ?? 'Unknown');
                           @endphp
                           <div style="font-size: 12px; background: #f9fafb; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 15px;">
                               <strong>Cronbach's Alpha (α):</strong> {{ number_format($alpha, 3) }} <br>
                               <strong>Number of Items Analyzed:</strong> {{ $itemsCount }} <br>
                               <strong>Internal Consistency:</strong> {{ $interp }}
                           </div>

                        @elseif($method === 'ttest')
                           @if(!empty($data['groups']))
                               <table style="font-size: 11px; margin-bottom: 15px;">
                                   <thead>
                                       <tr style="background-color: #f9fafb;">
                                           <th>Group</th>
                                           <th style="text-align: center;">N</th>
                                           <th style="text-align: right;">Mean</th>
                                           <th style="text-align: right;">Std. Deviation</th>
                                           <th style="text-align: right;">Std. Error</th>
                                       </tr>
                                   </thead>
                                   <tbody>
                                       @foreach($data['groups'] as $g)
                                           <tr>
                                               <td style="font-weight: bold;">{{ $g['name'] ?? '' }}</td>
                                               <td style="text-align: center;">{{ number_format($g['n'] ?? 0) }}</td>
                                               <td style="text-align: right;">{{ number_format($g['mean'] ?? 0, 4) }}</td>
                                               <td style="text-align: right;">{{ number_format($g['stdDev'] ?? 0, 4) }}</td>
                                               <td style="text-align: right;">{{ number_format($g['stdError'] ?? 0, 4) }}</td>
                                           </tr>
                                       @endforeach
                                   </tbody>
                               </table>
                           @endif

                           <table style="font-size: 11px; margin-bottom: 15px;">
                               <thead>
                                   <tr style="background-color: #f9fafb;">
                                       <th>Metric</th>
                                       <th>Value</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   <tr><td>t-Statistic</td><td>{{ number_format($data['tValue'] ?? ($data['t_stat'] ?? 0), 4) }}</td></tr>
                                   <tr><td>Degrees of Freedom (df)</td><td>{{ $data['df'] ?? 0 }}</td></tr>
                                   <tr><td>p-value</td><td>{{ number_format($data['pValue'] ?? ($data['p_value'] ?? 0), 4) }}</td></tr>
                                   <tr><td>Mean Difference</td><td>{{ number_format($data['meanDiff'] ?? ($data['mean_diff'] ?? 0), 4) }}</td></tr>
                                   <tr style="font-weight: bold;"><td>Significant</td><td>{{ ($data['significant'] ?? false) ? 'Yes' : 'No' }}</td></tr>
                               </tbody>
                           </table>

                        @elseif($method === 'anova')
                           @if(!empty($data['groupStats']))
                               <p style="font-size: 11px; font-weight: bold; margin-bottom: 5px;">Group Descriptives:</p>
                               <table style="font-size: 11px; margin-bottom: 15px;">
                                   <thead>
                                       <tr style="background-color: #f9fafb;">
                                           <th>Group</th>
                                           <th style="text-align: center;">N</th>
                                           <th style="text-align: right;">Mean</th>
                                           <th style="text-align: right;">Std. Dev</th>
                                           <th style="text-align: right;">95% CI Lower</th>
                                           <th style="text-align: right;">95% CI Upper</th>
                                       </tr>
                                   </thead>
                                   <tbody>
                                       @foreach($data['groupStats'] as $gs)
                                           <tr>
                                               <td style="font-weight: bold;">{{ $gs['name'] ?? '' }}</td>
                                               <td style="text-align: center;">{{ number_format($gs['n'] ?? 0) }}</td>
                                               <td style="text-align: right;">{{ number_format($gs['mean'] ?? 0, 4) }}</td>
                                               <td style="text-align: right;">{{ number_format($gs['stdDev'] ?? 0, 4) }}</td>
                                               <td style="text-align: right;">{{ number_format($gs['ciLower'] ?? 0, 4) }}</td>
                                               <td style="text-align: right;">{{ number_format($gs['ciUpper'] ?? 0, 4) }}</td>
                                           </tr>
                                       @endforeach
                                   </tbody>
                               </table>
                           @endif

                           <table style="font-size: 11px; margin-bottom: 15px;">
                               <thead>
                                   <tr style="background-color: #f9fafb;">
                                       <th>Source of Variation</th>
                                       <th>SS</th>
                                       <th>df</th>
                                       <th>MS</th>
                                       <th>F</th>
                                       <th>p-value</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   <tr>
                                       <td>Between Groups</td>
                                       <td>{{ number_format($data['ssb'] ?? ($data['between_ss'] ?? 0), 4) }}</td>
                                       <td>{{ $data['dfBetween'] ?? ($data['df_between'] ?? 0) }}</td>
                                       <td>{{ number_format($data['msb'] ?? ($data['ms_between'] ?? 0), 4) }}</td>
                                       <td rowspan="2" style="vertical-align: middle; text-align: center; font-weight: bold;">{{ number_format($data['fValue'] ?? ($data['f_stat'] ?? 0), 4) }}</td>
                                       <td rowspan="2" style="vertical-align: middle; text-align: center; font-weight: bold;">{{ number_format($data['pValue'] ?? ($data['p_value'] ?? 0), 4) }}</td>
                                   </tr>
                                   <tr>
                                       <td>Within Groups</td>
                                       <td>{{ number_format($data['ssw'] ?? ($data['within_ss'] ?? 0), 4) }}</td>
                                       <td>{{ $data['dfWithin'] ?? ($data['df_within'] ?? 0) }}</td>
                                       <td>{{ number_format($data['msw'] ?? ($data['ms_within'] ?? 0), 4) }}</td>
                                   </tr>
                               </tbody>
                               <tfoot>
                                   <tr style="font-weight: bold; background-color: #f9fafb;">
                                       <td>Total</td>
                                       <td>{{ number_format($data['sst'] ?? ($data['total_ss'] ?? 0), 4) }}</td>
                                       <td>{{ $data['dfTotal'] ?? ($data['df_total'] ?? 0) }}</td>
                                       <td colspan="3"></td>
                                   </tr>
                               </tfoot>
                           </table>

                        @elseif($method === 'correlation')
                           <table style="font-size: 11px; margin-bottom: 15px;">
                               <thead>
                                   <tr style="background-color: #f9fafb;">
                                       <th>Metric</th>
                                       <th>Value</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   <tr><td>Variables</td><td>{{ ($data['labelX'] ?? 'X') . ' vs ' . ($data['labelY'] ?? 'Y') }}</td></tr>
                                   <tr><td>Pearson Correlation Coefficient (r)</td><td>{{ number_format($data['r'] ?? 0, 4) }}</td></tr>
                                   <tr><td>p-value</td><td>{{ number_format($data['pValue'] ?? ($data['p_value'] ?? 0), 4) }}</td></tr>
                                   <tr><td>Sample Size (N)</td><td>{{ $data['n'] ?? 0 }}</td></tr>
                                   <tr><td>Direction</td><td>{{ $data['direction'] ?? 'None' }}</td></tr>
                                   <tr><td>Strength</td><td>{{ $data['strength'] ?? 'None' }}</td></tr>
                                   <tr style="font-weight: bold;"><td>Significant</td><td>{{ ($data['significant'] ?? false) ? 'Yes' : 'No' }}</td></tr>
                               </tbody>
                           </table>

                        @elseif($method === 'regression' || $method === 'regression_multiple')
                           <div style="font-size: 11px; margin-bottom: 12px; background: #f9fafb; padding: 10px; border-radius: 6px; border: 1px solid #e5e7eb;">
                               <strong>R-Square (R²):</strong> {{ number_format($data['r2'] ?? 0, 4) }} | 
                               <strong>Adjusted R-Square:</strong> {{ number_format($data['adjR2'] ?? ($data['adj_r2'] ?? 0), 4) }} | 
                               <strong>Overall F-Stat:</strong> {{ number_format($data['fValue'] ?? ($data['f_stat'] ?? 0), 4) }} | 
                               <strong>p-value:</strong> {{ number_format($data['pValue'] ?? ($data['p_value'] ?? 0), 4) }}
                           </div>
                           <table style="font-size: 11px; margin-bottom: 15px;">
                               <thead>
                                   <tr style="background-color: #f9fafb;">
                                       <th>Variable</th>
                                       <th>Coefficient</th>
                                       <th>t-Stat</th>
                                       <th>p-value</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   <tr>
                                       <td>Intercept (Constant)</td>
                                       <td>{{ number_format($data['intercept'] ?? 0, 4) }}</td>
                                       <td>—</td>
                                       <td>—</td>
                                   </tr>
                                   @foreach(($data['coefficients'] ?? []) as $var => $details)
                                       <tr>
                                           <td style="font-weight: bold;">{{ is_string($var) ? $var : ($details['name'] ?? 'Variable') }}</td>
                                           <td>{{ number_format($details['coef'] ?? 0, 4) }}</td>
                                           <td>{{ number_format($details['t_stat'] ?? ($details['tStat'] ?? 0), 4) }}</td>
                                           <td>{{ number_format($details['p_value'] ?? ($details['pValue'] ?? 0), 4) }}</td>
                                       </tr>
                                   @endforeach
                               </tbody>
                           </table>
                        @endif
                    @endif

                    @if(!empty($test->ai_summary))
                       <div style="margin-top: 10px;">
                           @foreach(array_filter(preg_split('/\n+/', trim($test->ai_summary))) as $aiPara)
                               <p style="font-size: 12px; color: #374151; margin: 0 0 6px 0; line-height: 1.5;">{{ preg_replace('/\*\*(.*?)\*\*/', '$1', trim($aiPara)) }}</p>
                           @endforeach
                       </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="disclaimer-box">
        <span class="disclaimer-title">Data Integrity & Validation Disclaimer</span>
        <p>This report has been automatically generated by KDAnalytiks. The statistics and AI insights provided are
            based on raw data collected from survey respondents. While we employ rigorous data validation protocols,
            PRC™ Consulting does not guarantee the absolute accuracy of individual qualitative interpretations provided
            by the AI engine. This report should be used as a strategic guide and cross-referenced with the raw dataset
            for critical decision-making.</p>
    </div>

    @if(!isset($branding) || $branding['showKdBranding'])
        <div class="footer">
            Powered by KDAnalytiks™ | Executive Analytics Export
        </div>
    @endif

</body>

</html>