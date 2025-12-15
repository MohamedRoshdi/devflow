<?php

/**
 * DevFlow Pro - Comprehensive Text Extractor
 * Extracts all user-facing text from blade files for translation
 */

$baseDir = dirname(__DIR__, 3);
$viewsDir = $baseDir . '/resources/views';

$allText = [];
$statistics = [
    'files_processed' => 0,
    'total_strings_extracted' => 0,
    'unique_strings' => 0,
    'by_type' => [],
];

// Enhanced patterns for text extraction
$extractionPatterns = [
    'headings' => '/<h[1-6][^>]*>([^<{@$]+)<\/h[1-6]>/i',
    'labels' => '/<label[^>]*>([^<{@$]+)<\/label>/i',
    'buttons' => '/<button[^>]*>([^<{@$]+)<\/button>/i',
    'spans' => '/<span[^>]*>([^<{@$]+)<\/span>/i',
    'paragraphs' => '/<p[^>]*>([^<{@$]+)<\/p>/i',
    'divs' => '/<div[^>]*>([^<{@$]+)<\/div>/i',
    'links' => '/<a[^>]*>([^<{@$]+)<\/a>/i',
    'placeholders' => '/placeholder=["\']([^"\']+)["\']/i',
    'titles' => '/title=["\']([^"\']+)["\']/i',
    'alt_text' => '/alt=["\']([^"\']+)["\']/i',
    'value' => '/value=["\']([^"\'@{$]+)["\']/i',
];

function shouldIncludeText($text) {
    $text = trim($text);

    // Filters
    if (empty($text)) return false;
    if (strlen($text) < 2) return false;
    if (preg_match('/^[0-9\s\-\.\,\:\;]+$/', $text)) return false; // Only numbers/punctuation
    if (str_contains($text, '$')) return false; // PHP variables
    if (str_contains($text, '{{')) return false; // Blade variables
    if (str_contains($text, '{!!')) return false; // Blade raw
    if (str_contains($text, '@')) return false; // Blade directives
    if (preg_match('/^[A-Z_]+$/', $text)) return false; // Constants
    if (strlen($text) > 200) return false; // Too long, likely not translatable

    return true;
}

function extractTextFromFile($filePath, $patterns, &$statistics) {
    $content = file_get_contents($filePath);
    $relativePath = str_replace($GLOBALS['viewsDir'] . '/', '', $filePath);
    $extracted = [];

    foreach ($patterns as $type => $pattern) {
        if (preg_match_all($pattern, $content, $matches)) {
            if (isset($matches[1])) {
                foreach ($matches[1] as $text) {
                    if (shouldIncludeText($text)) {
                        $cleanText = html_entity_decode(trim($text));
                        $extracted[] = [
                            'text' => $cleanText,
                            'file' => $relativePath,
                            'type' => $type,
                            'length' => strlen($cleanText),
                        ];

                        if (!isset($statistics['by_type'][$type])) {
                            $statistics['by_type'][$type] = 0;
                        }
                        $statistics['by_type'][$type]++;
                    }
                }
            }
        }
    }

    return $extracted;
}

echo "Extracting text from blade files...\n";

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($viewsDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $statistics['files_processed']++;
        $extracted = extractTextFromFile($file->getRealPath(), $extractionPatterns, $statistics);
        $allText = array_merge($allText, $extracted);
    }
}

$statistics['total_strings_extracted'] = count($allText);

// Get unique strings
$uniqueTexts = [];
foreach ($allText as $item) {
    $key = $item['text'];
    if (!isset($uniqueTexts[$key])) {
        $uniqueTexts[$key] = $item;
        $uniqueTexts[$key]['occurrences'] = 1;
        $uniqueTexts[$key]['files'] = [$item['file']];
    } else {
        $uniqueTexts[$key]['occurrences']++;
        if (!in_array($item['file'], $uniqueTexts[$key]['files'])) {
            $uniqueTexts[$key]['files'][] = $item['file'];
        }
    }
}

$statistics['unique_strings'] = count($uniqueTexts);

// Sort by occurrences
usort($uniqueTexts, fn($a, $b) => $b['occurrences'] <=> $a['occurrences']);

// Generate translation structure
$translationStructure = [];
foreach ($uniqueTexts as $item) {
    // Categorize by file path
    $parts = explode('/', $item['file']);
    $category = $parts[0] ?? 'common';

    if (!isset($translationStructure[$category])) {
        $translationStructure[$category] = [];
    }

    // Generate key
    $key = strtolower($item['text']);
    $key = preg_replace('/[^a-z0-9]+/', '_', $key);
    $key = trim($key, '_');
    $key = substr($key, 0, 50); // Limit key length

    $translationStructure[$category][$key] = [
        'en' => $item['text'],
        'ar' => '', // To be translated
        'occurrences' => $item['occurrences'],
        'files' => array_slice($item['files'], 0, 5), // Max 5 file references
    ];
}

// Create comprehensive report
$report = [
    'extraction_date' => date('Y-m-d H:i:s'),
    'statistics' => $statistics,
    'most_common_strings' => array_slice($uniqueTexts, 0, 50),
    'translation_structure' => $translationStructure,
    'categories' => array_map('count', $translationStructure),
    'recommendations' => [
        'Start by translating the most common strings (high occurrences)',
        'Create separate translation files for each major category',
        'Use professional translator for accurate Arabic translations',
        'Implement __() function in blade files progressively',
        'Add language switcher in the UI',
        'Test RTL layout for Arabic version',
    ],
];

// Save comprehensive report
file_put_contents(
    __DIR__ . '/comprehensive_extraction.json',
    json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

// Generate sample translation files for top categories
$topCategories = array_slice($translationStructure, 0, 5, true);
foreach ($topCategories as $category => $strings) {
    // EN file
    $enContent = "<?php\n\n// Category: {$category}\n// Auto-generated on " . date('Y-m-d H:i:s') . "\n\nreturn [\n";
    foreach (array_slice($strings, 0, 50, true) as $key => $data) {
        $value = str_replace("'", "\\'", $data['en']);
        $enContent .= "    '{$key}' => '{$value}',\n";
    }
    $enContent .= "];\n";
    file_put_contents(__DIR__ . "/generated_en_{$category}.php", $enContent);

    // AR file (template)
    $arContent = "<?php\n\n// Category: {$category}\n// Auto-generated on " . date('Y-m-d H:i:s') . "\n// TODO: Add Arabic translations\n\nreturn [\n";
    foreach (array_slice($strings, 0, 50, true) as $key => $data) {
        $arContent .= "    '{$key}' => '', // EN: {$data['en']}\n";
    }
    $arContent .= "];\n";
    file_put_contents(__DIR__ . "/generated_ar_{$category}.php", $arContent);
}

echo "\nExtraction completed!\n";
echo "Files processed: {$statistics['files_processed']}\n";
echo "Total strings extracted: {$statistics['total_strings_extracted']}\n";
echo "Unique strings: {$statistics['unique_strings']}\n\n";

echo "Strings by type:\n";
foreach ($statistics['by_type'] as $type => $count) {
    echo "  {$type}: {$count}\n";
}

echo "\nTop 10 most common strings:\n";
foreach (array_slice($uniqueTexts, 0, 10) as $i => $item) {
    echo "  " . ($i + 1) . ". \"{$item['text']}\" ({$item['occurrences']} times)\n";
}

echo "\nTranslation structure by category:\n";
foreach ($report['categories'] as $category => $count) {
    echo "  {$category}: {$count} strings\n";
}

echo "\nGenerated files:\n";
echo "  - comprehensive_extraction.json (full report)\n";
foreach ($topCategories as $category => $strings) {
    echo "  - generated_en_{$category}.php\n";
    echo "  - generated_ar_{$category}.php\n";
}
