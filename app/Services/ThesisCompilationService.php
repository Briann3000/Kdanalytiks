<?php

namespace App\Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\ListItem;

class ThesisCompilationService
{
    public function compileChapters(
        string $title,
        array $proofreadParagraphs, // Array of ['original' => '...', 'corrected' => '...'] or strings
        array $chapter4Items,       // Quantitative & qualitative question analysis structures
        array $chapter5Sections,    // Auto-generated Chapter 5 sections
        string $brandingColor = '4f46e5'
    ): PhpWord {
        $phpWord = new PhpWord();
        $brandHex = ltrim($brandingColor, '#');

        // Style setups
        $phpWord->addTitleStyle(1, ['size' => 24, 'bold' => true, 'color' => '1e1b4b', 'name' => 'Arial'], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);
        $phpWord->addTitleStyle(2, ['size' => 16, 'bold' => true, 'color' => $brandHex, 'name' => 'Arial'], ['spaceBefore' => 240, 'spaceAfter' => 120, 'borderBottomSize' => 6, 'borderBottomColor' => 'e5e7eb']);
        $phpWord->addTitleStyle(3, ['size' => 12, 'bold' => true, 'color' => '111827', 'name' => 'Arial'], ['spaceBefore' => 120, 'spaceAfter' => 60]);

        $phpWord->addFontStyle('Normal', ['size' => 11, 'name' => 'Arial', 'color' => '374151']);
        $phpWord->addFontStyle('Italic', ['size' => 11, 'name' => 'Arial', 'color' => '6b7280', 'italic' => true]);
        $phpWord->addFontStyle('Bold', ['size' => 11, 'name' => 'Arial', 'color' => '111827', 'bold' => true]);

        $phpWord->addTableStyle('StatsTable', [
            'borderSize' => 6,
            'borderColor' => 'cccccc',
            'cellMargin' => 80
        ], [
            'bgColor' => 'f9fafb'
        ]);

        $section = $phpWord->addSection([
            'marginTop' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440
        ]);

        $safeStr = function ($val): string {
            if (is_null($val))
                return '';
            $str = '';
            if (is_scalar($val)) {
                $str = (string) $val;
            } elseif (is_array($val)) {
                $flat = [];
                array_walk_recursive($val, function ($a) use (&$flat) {
                    if (is_scalar($a) && $a !== '')
                        $flat[] = (string) $a;
                });
                $str = implode(', ', $flat);
            }
            // Clean out raw markdown formatting (asterisks, hashtags, backticks)
            $str = preg_replace('/\*{1,3}/', '', $str);
            $str = preg_replace('/^#+\s+/m', '', $str);
            $str = str_replace('`', '', $str);
            return trim($str);
        };

        // Helper to parse ** to bold
        $addParsedText = function ($element, $text) use ($safeStr) {
            $text = $safeStr($text);
            if (empty($text))
                return;
            $parts = explode('**', $text);
            $isBold = false;
            $run = ($element instanceof \PhpOffice\PhpWord\Element\TextRun) ? $element : $element->addTextRun();
            foreach ($parts as $part) {
                if ($part === '') {
                    $isBold = !$isBold;
                    continue;
                }
                $run->addText($part, ['bold' => $isBold, 'name' => 'Arial', 'size' => 11, 'color' => '374151']);
                $isBold = !$isBold;
            }
        };

        // Cover Page
        $section->addTextBreak(4);
        $section->addTitle($safeStr($title), 1);
        $section->addText("Full Dissertation & Research Findings Report", ['size' => 14, 'color' => $brandHex, 'bold' => true], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);
        $section->addText("Date Compiled: " . now()->format('F d, Y'), ['italic' => true], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(2);

        $section->addText("This report compiles Chapters 1–3 (proofread), Chapter 4 (Findings and Analysis), and Chapter 5 (Conclusions and Recommendations) into a single cohesive document in compliance with academic standards.", 'Italic', ['alignment' => Jc::CENTER]);
        $section->addPageBreak();

        // Separate Chapters 1-3 from References & Appendices cleanly
        $ch1to3Paragraphs = [];
        $refAndAppParagraphs = [];
        $seenRefHashes = [];
        $isCurrentlyInRefSection = false;

        if (is_array($proofreadParagraphs)) {
            foreach ($proofreadParagraphs as $idx => $p) {
                $rawText = is_array($p)
                    ? ($p['status'] === 'rejected' ? ($p['original'] ?? '') : ($p['corrected'] ?? $p['original'] ?? ''))
                    : $p;
                $textStr = trim($safeStr($rawText));

                if (empty($textStr)) {
                    continue;
                }

                // Check if this paragraph starts a Chapter or main content section (turns ref mode OFF)
                $isChapterHeading = preg_match('/^(CHAPTER\s+\d+|[1-5]\.\d+|\[SECTION:\s*CHAPTER)/i', $textStr);

                // Check if this paragraph starts References or Appendices (turns ref mode ON)
                $isRefHeading = preg_match('/^(REFERENCES|BIBLIOGRAPHY|APPENDIX|APPENDICES|\[SECTION:\s*(REFERENCES|BIBLIOGRAPHY|APPENDIX|APPENDICES))/i', $textStr)
                    || (is_array($p) && !empty($p['isHeading']) && preg_match('/^(REFERENCES|BIBLIOGRAPHY)/i', $textStr));

                if ($isRefHeading) {
                    $isCurrentlyInRefSection = true;
                } elseif ($isChapterHeading) {
                    $isCurrentlyInRefSection = false;
                }

                if ($isCurrentlyInRefSection) {
                    $cleanHashStr = preg_replace('/^(REFERENCES|BIBLIOGRAPHY)\s*:\s*/i', '', $textStr);
                    $refHash = md5(mb_strtolower(trim($cleanHashStr)));

                    if (!isset($seenRefHashes[$refHash])) {
                        $seenRefHashes[$refHash] = true;
                        $refAndAppParagraphs[] = $p;
                    }
                } else {
                    $ch1to3Paragraphs[] = $p;
                }
            }
        }

        // --- 2. RENDER CHAPTERS 1–3 AT THE TOP ---
        if (!empty($ch1to3Paragraphs)) {
            $section->addTitle("Chapters 1–3: Introduction, Literature Review & Methodology", 2);
            foreach ($ch1to3Paragraphs as $p) {
                $rawText = is_array($p) ? ($p['corrected'] ?? $p['original'] ?? '') : $p;
                $text = trim($safeStr($rawText));
                if (!empty($text)) {
                    $isHeading = !empty($p['isHeading']) || preg_match('/^(CHAPTER\s+\d+|[1-5]\.\d+)/i', $text);
                    if ($isHeading) {
                        $section->addTitle($text, 3);
                    } else {
                        $section->addText($text, 'Normal');
                    }
                    $section->addTextBreak(1);
                }
            }
        }
        $section->addPageBreak();

        // Add Chapter 4
        $section->addTitle("Chapter 4: Findings and Analysis", 2);

        $qNumber = 1;
        foreach ($chapter4Items as $item) {
            $section->addTitle($item['label'] ?? $item['title'] ?? 'Survey Item', 2);
            \Illuminate\Support\Facades\Log::info("Raw Stats Payload for [{$item['label']}]:", ['stats' => $item['stats'] ?? null, 'answers' => $item['answers'] ?? null]);
            $categories = [];
            $values = [];

            $statData = $item['stats'] ?? [];

            // Extract Quantitative Data for Charts
            if (is_array($statData) && !empty($statData)) {
                foreach ($statData as $row) {
                    if (is_array($row)) {
                        // Category/Label extraction
                        $cat = $row['value'] ?? $row['label'] ?? $row['option'] ?? $row['category'] ?? null;
                        // Numeric count extraction
                        $val = $row['count'] ?? $row['frequency'] ?? $row['val'] ?? null;

                        if ($cat !== null && $val !== null && strtolower((string) $cat) !== 'total') {
                            $categories[] = (string) $cat;
                            $values[] = (int) $val;
                        }
                    }
                }
            }

            // Render inferential test results in DOCX.Removed tables
            if (!empty($item['isInferential'])) {
                if (!empty($item['aiInsight']) && is_string($item['aiInsight'])) {
                    $section->addTextBreak(1);
                    $addParsedText($section, $item['aiInsight']);
                }
                $section->addTextBreak(1);
                continue;
            }

            if (!empty($item['isChartable'])) {
                if (!empty($item['isLikertLike'])) {
                    // Likert matrix table
                    $table = $section->addTable('StatsTable');
                    $table->addRow();
                    $table->addCell(3000, ['vMerge' => 'restart'])->addText("Item", ['bold' => true]);
                    foreach ($item['stats'] as $stat) {
                        if (!isset($stat['is_missing']) || !$stat['is_missing']) {
                            $table->addCell(2000, ['gridSpan' => 2])->addText($safeStr($stat['value'] ?? ''), ['bold' => true], ['alignment' => 'center']);
                        }
                    }

                    $table->addRow();
                    $table->addCell(3000, ['vMerge' => 'continue']);
                    foreach ($item['stats'] as $stat) {
                        if (!isset($stat['is_missing']) || !$stat['is_missing']) {
                            $table->addCell(1000)->addText("Frequency", ['bold' => true], ['alignment' => 'center']);
                            $table->addCell(1000)->addText("%", ['bold' => true], ['alignment' => 'center']);
                        }
                    }

                    $table->addRow();
                    $table->addCell(3000)->addText($safeStr($item['label'] ?? ''), 'Normal');
                    $totalFreqLikert = array_sum(array_column(array_filter($item['stats'], fn($s) => !isset($s['is_missing']) || !$s['is_missing']), 'count'));
                    foreach ($item['stats'] as $stat) {
                        if (!isset($stat['is_missing']) || !$stat['is_missing']) {
                            $percentLikert = $totalFreqLikert > 0 ? ($stat['count'] / $totalFreqLikert) * 100 : 0;
                            $table->addCell(1000)->addText(number_format($stat['count']), 'Normal', ['alignment' => 'center']);
                            $table->addCell(1000)->addText(number_format($percentLikert, 1) . '%', 'Normal', ['alignment' => 'center']);
                        }
                    }
                } else {
                    // Standard Frequency Table (5 Columns)
                    $table = $section->addTable('StatsTable');
                    $table->addRow();
                    $table->addCell(3000)->addText("Value", ['bold' => true]);
                    $table->addCell(1500)->addText("Frequency", ['bold' => true], ['alignment' => 'right']);
                    $table->addCell(1500)->addText("Percent", ['bold' => true], ['alignment' => 'right']);
                    $table->addCell(1800)->addText("Valid Percent", ['bold' => true], ['alignment' => 'right']);
                    $table->addCell(1800)->addText("Cumulative Percent", ['bold' => true], ['alignment' => 'right']);

                    $totalFreq = 0;
                    $validFreq = 0;
                    foreach ($item['stats'] as $s) {
                        if (!isset($s['is_missing']) || !$s['is_missing']) {
                            $validFreq += $s['count'];
                        }
                        $totalFreq += $s['count'];
                    }
                    if ($validFreq === 0)
                        $validFreq = $totalFreq;

                    $cumulativePerc = 0;
                    foreach ($item['stats'] as $stat) {
                        $isMissing = isset($stat['is_missing']) && $stat['is_missing'];
                        if ($isMissing && $stat['count'] == 0)
                            continue;

                        $percent = $totalFreq > 0 ? ($stat['count'] / $totalFreq) * 100 : 0;
                        if ($isMissing) {
                            $validPercent = null;
                            $cumPercentDisplay = '-';
                        } else {
                            $validPercent = $validFreq > 0 ? ($stat['count'] / $validFreq) * 100 : 0;
                            $cumulativePerc += $validPercent;
                            $cumPercentDisplay = number_format($cumulativePerc, 1) . '%';
                        }

                        $table->addRow();
                        $table->addCell(3000)->addText($safeStr($stat['value'] ?? ''), 'Normal');
                        $table->addCell(1500)->addText(number_format($stat['count']), 'Normal', ['alignment' => 'right']);
                        $table->addCell(1500)->addText(number_format($percent, 1) . '%', 'Normal', ['alignment' => 'right']);
                        $table->addCell(1800)->addText($validPercent !== null ? number_format($validPercent, 1) . '%' : '-', 'Normal', ['alignment' => 'right']);
                        $table->addCell(1800)->addText($cumPercentDisplay, 'Normal', ['alignment' => 'right']);
                    }

                    $table->addRow();
                    $table->addCell(3000)->addText("Total", ['bold' => true]);
                    $table->addCell(1500)->addText(number_format($totalFreq), ['bold' => true], ['alignment' => 'right']);
                    $table->addCell(1500)->addText("100.0%", ['bold' => true], ['alignment' => 'right']);
                    $table->addCell(1800)->addText("100.0%", ['bold' => true], ['alignment' => 'right']);
                    $table->addCell(1800)->addText("", 'Normal');
                }


                // --- 2. NATIVE OPENXML CHART RENDERING ---
                if (!empty($categories) && !empty($values)) {
                    $rawType = strtolower($item['chartType'] ?? $item['chart_type'] ?? 'bar');
                    $chartType = 'bar';

                    if (in_array($rawType, ['pie', 'piechart'])) {
                        $chartType = 'pie';
                    } elseif (in_array($rawType, ['column', 'columnchart', 'verticalbar'])) {
                        $chartType = 'column';
                    } elseif (in_array($rawType, ['line', 'linechart'])) {
                        $chartType = 'line';
                    }

                    $section->addTextBreak(1);
                    $chartStyle = [
                        'width' => 600,  // Standard points
                        'height' => 400, // Standard points
                        'showLegend' => false
                    ];

                    // Pass exactly 4 arguments to fulfill PHPWord's signature and satisfy WPS's XML parser
                    $section->addChart(
                        $chartType,
                        array_values($categories),
                        array_values($values),
                        $chartStyle
                    );

                    $section->addTextBreak(1);
                    \Illuminate\Support\Facades\Log::info("WPS-compatible {$chartType} chart attached for: " . ($item['label'] ?? ''));
                }

                // Add AI trend analysis text
                if (!empty($item['aiInsight']) && is_string($item['aiInsight'])) {
                    $section->addTextBreak(1);
                    $addParsedText($section, $item['aiInsight']);
                }
            } else {
                // Qualitative analysis
                if (!empty($item['aiInsight']) && is_array($item['aiInsight'])) {
                    $pos = $item['aiInsight']['sentiment_breakdown']['Positive'] ?? 0;
                    $neu = $item['aiInsight']['sentiment_breakdown']['Neutral'] ?? 0;
                    $neg = $item['aiInsight']['sentiment_breakdown']['Negative'] ?? 0;

                    $sentNarr = "The sentiment analysis indicates {$pos}% positive, {$neu}% neutral, and {$neg}% negative distribution among comments.";
                    $section->addText($sentNarr, 'Normal');

                    $themes = $item['aiInsight']['key_themes'] ?? [];
                    if (!empty($themes)) {
                        $section->addTextBreak(1);
                        $section->addText("Themes identified:", ['bold' => true]);
                        foreach ($themes as $theme) {
                            $tName = $theme['theme'] ?? 'Theme';
                            $tExpl = $theme['explanation'] ?? '';
                            $section->addListItem($tName . ": " . $tExpl, 0, 'Normal', ListItem::TYPE_BULLET_FILLED);
                        }
                    }

                    $quotes = $item['aiInsight']['representative_quotes'] ?? [];
                    if (!empty($quotes)) {
                        $section->addTextBreak(1);
                        $section->addText("Representative Respondent Quotes:", ['bold' => true]);
                        foreach ($quotes as $q) {
                            $section->addText('"' . $safeStr($q) . '"', 'Italic');
                            $section->addTextBreak(1);
                        }
                    }
                } elseif (!empty($item['aiInsight']) && is_string($item['aiInsight'])) {
                    $section->addText($safeStr($item['aiInsight']), 'Normal');
                } elseif (!empty($item['insights']) && is_array($item['insights'])) {
                    $section->addText("Sample Qualitative Responses:", ['bold' => true]);
                    foreach (array_slice($item['insights'], 0, 5) as $sample) {
                        $section->addListItem('"' . $safeStr($sample) . '"', 0, 'Italic', ListItem::TYPE_BULLET_FILLED);
                    }
                }
            }
            $section->addTextBreak(2);
        }
        $section->addPageBreak();

        // Add Chapter 5
        $section->addTitle("Chapter 5: Conclusions and Recommendations", 2);
        foreach ($chapter5Sections as $titleSec => $contentSec) {
            $section->addTitle($titleSec, 3);
            $paras = preg_split('/\n+/', trim($contentSec));
            foreach ($paras as $para) {
                if (!empty($para)) {
                    $addParsedText($section, trim($para));
                    $section->addTextBreak(1);
                }
            }
        }

        // Add References and Appendices at the end of the document
        if (!empty($refAndAppParagraphs)) {
            $section->addPageBreak();
            $section->addTitle("References & Appendices", 2);

            foreach ($refAndAppParagraphs as $p) {
                $rawText = is_array($p) ? ($p['corrected'] ?? $p['original'] ?? '') : $p;
                $text = trim($safeStr($rawText));

                if (empty($text)) {
                    continue;
                }

                // Identify subheadings/headings versus regular reference paragraphs
                $isHeading = !empty($p['isHeading']) || preg_match('/^(REFERENCES|BIBLIOGRAPHY|APPENDIX|APPENDICES|\[SECTION:\s*(REFERENCES|BIBLIOGRAPHY|APPENDIX|APPENDICES))/i', $text);

                if ($isHeading) {
                    // Clean up tag markers like [SECTION: REFERENCES] for clean DOCX display
                    $cleanTitle = preg_replace('/^\[SECTION:\s*(.*?)\]$/i', '$1', $text);
                    $section->addTitle(ucwords(strtolower(trim($cleanTitle))), 3);
                } else {
                    // Render actual reference entry
                    $section->addText($text, 'Normal');
                }
                $section->addTextBreak(1);

            }
        }

        return $phpWord;
    }
}
