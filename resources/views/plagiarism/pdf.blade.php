<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Originality Diagnostic Report — {{ $scan->title }}</title>
    <style>
        @page {
            margin: 35px 40px 45px 40px;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #1f2937;
        }

        .header {
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .brand-title {
            font-size: 18px;
            font-weight: bold;
            color: #111827;
            letter-spacing: -0.5px;
        }

        .report-badge {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6b7280;
        }

        .meta-table {
            width: 100%;
            margin-top: 12px;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 4px 0;
            font-size: 10px;
            vertical-align: top;
        }

        .meta-label {
            color: #6b7280;
            font-weight: bold;
            width: 18%;
        }

        .meta-val {
            color: #111827;
        }

        .score-box-container {
            width: 100%;
            margin: 20px 0;
        }

        .score-box {
            display: inline-block;
            width: 48%;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px 15px;
            box-sizing: border-box;
            background-color: #f9fafb;
        }

        .score-num {
            font-size: 22px;
            font-weight: bold;
            color: #111827;
            line-height: 1.1;
        }

        .score-desc {
            font-size: 10px;
            font-weight: bold;
            color: #4b5563;
            margin-top: 3px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #111827;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
            margin-top: 25px;
            margin-bottom: 10px;
        }

        .sources-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .sources-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: bold;
            font-size: 9px;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
        }

        .sources-table td {
            padding: 6px 8px;
            font-size: 9.5px;
            border: 1px solid #e5e7eb;
            color: #374151;
        }

        .manuscript-body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10.5px;
            line-height: 1.6;
            color: #111827;
            text-align: justify;
            white-space: pre-wrap;
            margin-top: 15px;
            padding: 12px;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }

        .highlight {
            background-color: #fef3c7;
            border-bottom: 1px solid #f59e0b;
            color: #78350f;
        }

        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            font-size: 8.5px;
            color: #9ca3af;
            text-align: center;
            border-top: 1px solid #f3f4f6;
            padding-top: 6px;
        }
    </style>
</head>

<body>

    <!-- Header Block -->
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="brand-title">KDAnalytiks</div>
                    <div class="report-badge">Official Academic Originality Certificate</div>
                </td>
                <td style="text-align: right;">
                    <div style="font-size: 9px; color: #6b7280;">Report Reference:
                        <strong>KD-PLG-{{ str_pad($scan->id, 6, '0', STR_PAD_LEFT) }}</strong>
                    </div>
                    <div style="font-size: 9px; color: #6b7280;">Generated: {{ now()->format('M d, Y · H:i T') }}</div>
                </td>
            </tr>
        </table>

        <!-- Document Metadata -->
        <table class="meta-table">
            <tr>
                <td class="meta-label">Document Title:</td>
                <td class="meta-val"><strong>{{ $scan->title }}</strong></td>
                <td class="meta-label">Total Word Count:</td>
                <td class="meta-val">{{ number_format($scan->word_count) }} Words</td>
            </tr>
            <tr>
                <td class="meta-label">Submitted By:</td>
                <td class="meta-val">{{ $scan->user->name ?? 'Researcher' }} ({{ $scan->user->email ?? 'N/A' }})</td>
                <td class="meta-label">Character Count:</td>
                <td class="meta-val">{{ number_format($scan->character_count) }} Characters</td>
            </tr>
            <tr>
                <td class="meta-label">Original File:</td>
                <td class="meta-val">{{ $scan->original_filename ?: 'Direct Submission' }}</td>
                <td class="meta-label">Filters Applied:</td>
                <td class="meta-val">
                    {{ $scan->exclude_references ? 'References Excluded' : 'References Included' }} ·
                    {{ $scan->exclude_quotes ? 'Quotes Excluded' : 'Quotes Included' }} ·
                    {{ $scan->exclude_citations ? 'Citations Excluded' : 'Citations Included' }}
                    @if(!empty($scan->excluded_domains))
                        · {{ count($scan->excluded_domains) }} Domain(s) Whitelisted
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Diagnostic Score Cards -->
    <table style="width: 100%; margin: 15px 0;">
        <tr>
            <td
                style="width: 49%; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; background-color: #f9fafb; vertical-align: top;">
                <div style="font-size: 9px; font-weight: bold; text-transform: uppercase; color: #6b7280;">Similarity
                    Index</div>
                <div class="score-num">{{ number_format($scan->similarity_percentage, 1) }}%</div>
                <div class="score-desc">{{ $scan->similarity_level }} ({{ $matches->count() }} Matched Segments)</div>
            </td>
            <td style="width: 2%;"></td>
            <td
                style="width: 49%; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; background-color: #f9fafb; vertical-align: top;">
                <div style="font-size: 9px; font-weight: bold; text-transform: uppercase; color: #6b7280;">AI Content
                    Probability</div>
                <div class="score-num">{{ number_format($scan->ai_percentage, 1) }}%</div>
                <div class="score-desc">{{ $scan->ai_level }}</div>
            </td>
        </tr>
    </table>

    <!-- Primary Identified Sources -->
    <div class="section-title">Primary Identified Sources Index</div>
    @if($matches->count() > 0)
        <table class="sources-table">
            <thead>
                <tr>
                    <th style="width: 6%;">#</th>
                    <th style="width: 44%;">Source Identification / Publication</th>
                    <th style="width: 32%;">Matched Domain / Scholarly Index</th>
                    <th style="width: 18%; text-align: right;">Match Weight</th>
                </tr>
            </thead>
            <tbody>
                @foreach($matches->take(10) as $idx => $match)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $match->source_title ?: 'Academic Literature Index' }}</strong>
                        </td>
                        <td>{{ $match->source_domain ?: 'Scholarly Database' }}</td>
                        <td style="text-align: right;"><strong>{{ number_format($match->similarity_score, 0) }}%</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="padding: 10px; border: 1px solid #e5e7eb; background-color: #f9fafb; font-size: 10px; color: #059669;">
            No significant overlapping literature or verbatim web sources detected.
        </div>
    @endif

    <!-- Manuscript Text Excerpt -->
    <div class="section-title">Manuscript Inspection Transcript</div>
    <div class="manuscript-body">
        @php
            $fullContent = $scan->content;
            $activeMatches = $matches->sortBy('start_offset');
            $rendered = '';
            $lastPos = 0;
        @endphp

        @if($activeMatches->count() === 0)
            {{ $fullContent }}
        @else
            @foreach($activeMatches as $m)
                @php
                    $start = max(0, $m->start_offset);
                    $end = min(mb_strlen($fullContent), $m->end_offset);
                    if ($start < $lastPos)
                        continue;
                    $rendered .= e(mb_substr($fullContent, $lastPos, $start - $lastPos));
                    $rendered .= '<span class="highlight">' . e(mb_substr($fullContent, $start, $end - $start)) . '</span>';
                    $lastPos = $end;
                @endphp
            @endforeach
            @php
                $rendered .= e(mb_substr($fullContent, $lastPos));
            @endphp
            {!! $rendered !!}
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        KDAnalytiks Research & Survey Systems · Confidential Academic Originality Audit · https://kdanalytiks.com
    </div>

</body>

</html>