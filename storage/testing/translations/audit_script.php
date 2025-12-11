<?php

/**
 * DevFlow Pro - Translation Audit Script
 * Comprehensive audit of English and Arabic translations
 */

$baseDir = dirname(__DIR__, 3);
$langDir = $baseDir . '/lang';
$resourcesDir = $baseDir . '/resources/views';
$appDir = $baseDir . '/app';

// Translation patterns to search for
$patterns = [
    '__\([\'"]([^\'"]+)[\'"]\)',
    'trans\([\'"]([^\'"]+)[\'"]\)',
    '@lang\([\'"]([^\'"]+)[\'"]\)',
    'trans_choice\([\'"]([^\'"]+)[\'"]\)',
];

// Initialize result arrays
$translationKeys = [
    'en' => [],
    'ar' => [],
];
$usedKeys = [];
$hardcodedText = [];
$statistics = [
    'total_files_scanned' => 0,
    'blade_files' => 0,
    'php_files' => 0,
    'translation_calls_found' => 0,
    'unique_keys_found' => 0,
];

/**
 * Recursively scan language files
 */
function scanLangFiles($langDir, $locale) {
    $keys = [];

    if (!is_dir($langDir . '/' . $locale)) {
        return $keys;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($langDir . '/' . $locale, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = include $file->getRealPath();
            if (is_array($content)) {
                $prefix = str_replace($langDir . '/' . $locale . '/', '', $file->getPath());
                $prefix = $prefix ? $prefix . '.' : '';
                $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);

                $keys = array_merge($keys, flattenArray($content, $prefix . $filename));
            }
        }
    }

    return $keys;
}

/**
 * Flatten nested array to dot notation
 */
function flattenArray($array, $prefix = '') {
    $result = [];
    foreach ($array as $key => $value) {
        $newKey = $prefix ? $prefix . '.' . $key : $key;
        if (is_array($value)) {
            $result = array_merge($result, flattenArray($value, $newKey));
        } else {
            $result[$newKey] = $value;
        }
    }
    return $result;
}

/**
 * Scan PHP and Blade files for translation function calls
 */
function scanForTranslationCalls($directory, $patterns, &$usedKeys, &$statistics) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && (
            $file->getExtension() === 'php' ||
            str_ends_with($file->getFilename(), '.blade.php')
        )) {
            $statistics['total_files_scanned']++;

            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $statistics['blade_files']++;
            } else {
                $statistics['php_files']++;
            }

            $content = file_get_contents($file->getRealPath());

            foreach ($patterns as $pattern) {
                if (preg_match_all('/' . $pattern . '/m', $content, $matches)) {
                    if (isset($matches[1])) {
                        foreach ($matches[1] as $key) {
                            if (!in_array($key, $usedKeys)) {
                                $usedKeys[] = $key;
                                $statistics['translation_calls_found']++;
                            }
                        }
                    }
                }
            }
        }
    }
}

/**
 * Extract common hardcoded English text patterns from blade files
 */
function extractHardcodedText($directory) {
    $hardcoded = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    $commonPatterns = [
        '/<h[1-6][^>]*>([^<]+)<\/h[1-6]>/',
        '/<label[^>]*>([^<]+)<\/label>/',
        '/<button[^>]*>([^<{@]+)<\/button>/',
        '/<span[^>]*>([^<{@]+)<\/span>/',
        '/placeholder=[\'"]([^\'"]+)[\'"]/',
        '/title=[\'"]([^\'"]+)[\'"]/',
    ];

    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
            $content = file_get_contents($file->getRealPath());
            $relativePath = str_replace($directory . '/', '', $file->getRealPath());

            foreach ($commonPatterns as $pattern) {
                if (preg_match_all($pattern, $content, $matches)) {
                    if (isset($matches[1])) {
                        foreach ($matches[1] as $text) {
                            $text = trim($text);
                            if (!empty($text) && strlen($text) > 2 && !str_contains($text, '$')) {
                                $hardcoded[] = [
                                    'text' => $text,
                                    'file' => $relativePath,
                                    'type' => 'hardcoded_english'
                                ];
                            }
                        }
                    }
                }
            }
        }
    }

    return $hardcoded;
}

echo "Starting DevFlow Pro Translation Audit...\n\n";

// Scan language files
echo "Scanning language files...\n";
$translationKeys['en'] = scanLangFiles($langDir, 'en');
$translationKeys['ar'] = scanLangFiles($langDir, 'ar');

echo "EN keys found: " . count($translationKeys['en']) . "\n";
echo "AR keys found: " . count($translationKeys['ar']) . "\n\n";

// Scan for translation function calls
echo "Scanning code files for translation calls...\n";
scanForTranslationCalls($resourcesDir, $patterns, $usedKeys, $statistics);
scanForTranslationCalls($appDir, $patterns, $usedKeys, $statistics);
$statistics['unique_keys_found'] = count($usedKeys);

echo "Files scanned: " . $statistics['total_files_scanned'] . "\n";
echo "Translation calls found: " . $statistics['translation_calls_found'] . "\n\n";

// Extract hardcoded text
echo "Extracting hardcoded text from blade files...\n";
$hardcodedText = extractHardcodedText($resourcesDir);
echo "Hardcoded text patterns found: " . count($hardcodedText) . "\n\n";

// Calculate differences
$enKeys = array_keys($translationKeys['en']);
$arKeys = array_keys($translationKeys['ar']);

$missingInAr = array_diff($enKeys, $arKeys);
$missingInEn = array_diff($arKeys, $enKeys);
$unusedKeys = array_diff($enKeys, $usedKeys);
$missingFromFiles = array_diff($usedKeys, $enKeys);

// Calculate coverage
$totalKeys = count($enKeys);
$arHasKeys = count($arKeys);
$coveragePercentage = $totalKeys > 0 ? round(($arHasKeys / $totalKeys) * 100, 2) : 0;

// Sample hardcoded text (limit to 50)
$hardcodedSample = array_slice($hardcodedText, 0, 50);

// Prepare report
$report = [
    'audit_date' => date('Y-m-d H:i:s'),
    'missing_in_ar' => array_values($missingInAr),
    'missing_in_en' => array_values($missingInEn),
    'unused_keys' => array_values($unusedKeys),
    'missing_from_files' => array_values($missingFromFiles),
    'hardcoded_text_sample' => $hardcodedSample,
    'statistics' => [
        'total_en_keys' => count($enKeys),
        'total_ar_keys' => count($arKeys),
        'missing_ar_count' => count($missingInAr),
        'missing_en_count' => count($missingInEn),
        'unused_keys_count' => count($unusedKeys),
        'missing_from_files_count' => count($missingFromFiles),
        'coverage_percentage' => $coveragePercentage,
        'files_scanned' => $statistics['total_files_scanned'],
        'blade_files' => $statistics['blade_files'],
        'php_files' => $statistics['php_files'],
        'translation_calls_found' => $statistics['translation_calls_found'],
        'unique_keys_in_code' => $statistics['unique_keys_found'],
        'hardcoded_text_patterns' => count($hardcodedText),
    ],
    'recommendations' => []
];

// Add recommendations
if (count($missingInAr) > 0) {
    $report['recommendations'][] = "Add " . count($missingInAr) . " missing Arabic translations";
}
if (count($unusedKeys) > 0) {
    $report['recommendations'][] = "Remove or use " . count($unusedKeys) . " unused translation keys";
}
if (count($hardcodedText) > 0) {
    $report['recommendations'][] = "Replace " . count($hardcodedText) . " hardcoded text strings with translation functions";
}
if ($coveragePercentage < 100) {
    $report['recommendations'][] = "Improve Arabic translation coverage from " . $coveragePercentage . "% to 100%";
}

// Save report
$reportPath = dirname(__FILE__) . '/audit_report.json';
file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Audit completed!\n";
echo "Report saved to: $reportPath\n\n";

echo "Summary:\n";
echo "========\n";
echo "Total EN keys: " . $report['statistics']['total_en_keys'] . "\n";
echo "Total AR keys: " . $report['statistics']['total_ar_keys'] . "\n";
echo "Missing in AR: " . $report['statistics']['missing_ar_count'] . "\n";
echo "Unused keys: " . $report['statistics']['unused_keys_count'] . "\n";
echo "Coverage: " . $report['statistics']['coverage_percentage'] . "%\n";
echo "Hardcoded text: " . $report['statistics']['hardcoded_text_patterns'] . "\n";
echo "\nRecommendations:\n";
foreach ($report['recommendations'] as $recommendation) {
    echo "- " . $recommendation . "\n";
}
