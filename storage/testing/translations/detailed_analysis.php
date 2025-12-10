<?php

/**
 * DevFlow Pro - Detailed Translation Analysis
 * Categorizes hardcoded text and creates sample translation files
 */

$baseDir = dirname(__DIR__, 3);
$reportPath = __DIR__ . '/audit_report.json';
$report = json_decode(file_get_contents($reportPath), true);

// Categories for translation organization
$categories = [
    'common' => [],
    'auth' => [],
    'projects' => [],
    'servers' => [],
    'deployments' => [],
    'navigation' => [],
    'validation' => [],
    'buttons' => [],
    'messages' => [],
    'errors' => [],
];

// Keywords for categorization
$categoryKeywords = [
    'auth' => ['login', 'logout', 'password', 'register', 'sign in', 'sign up', 'authentication', 'forgot', 'reset'],
    'projects' => ['project', 'repository', 'deployment', 'environment', 'docker', 'container'],
    'servers' => ['server', 'ssh', 'ssl', 'certificate', 'firewall', 'security'],
    'deployments' => ['deploy', 'pipeline', 'build', 'release', 'rollback'],
    'navigation' => ['dashboard', 'home', 'settings', 'profile', 'menu', 'navigation'],
    'buttons' => ['create', 'update', 'delete', 'save', 'cancel', 'submit', 'add', 'remove'],
    'messages' => ['success', 'info', 'warning', 'notification'],
    'errors' => ['error', 'failed', 'invalid', 'missing', 'not found'],
];

// Categorize hardcoded text
foreach ($report['hardcoded_text_sample'] as $item) {
    $text = strtolower($item['text']);
    $categorized = false;

    foreach ($categoryKeywords as $category => $keywords) {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $categories[$category][] = $item;
                $categorized = true;
                break 2;
            }
        }
    }

    if (!$categorized) {
        $categories['common'][] = $item;
    }
}

// Generate translation keys
function generateTranslationKey($text, $file) {
    // Extract component/page name from file path
    $parts = explode('/', $file);
    $filename = end($parts);
    $filename = str_replace('.blade.php', '', $filename);
    $filename = str_replace('-', '_', $filename);

    // Create key from text
    $key = strtolower($text);
    $key = preg_replace('/[^a-z0-9]+/', '_', $key);
    $key = trim($key, '_');

    return $filename . '.' . $key;
}

// Create sample translation files
$enTranslations = [];
$arTranslations = [];

foreach ($categories as $category => $items) {
    $enTranslations[$category] = [];
    $arTranslations[$category] = [];

    foreach ($items as $item) {
        $key = generateTranslationKey($item['text'], $item['file']);
        $enTranslations[$category][$key] = $item['text'];
        $arTranslations[$category][$key] = '[AR] ' . $item['text']; // Placeholder for Arabic
    }
}

// Create detailed analysis report
$detailedReport = [
    'analysis_date' => date('Y-m-d H:i:s'),
    'summary' => [
        'total_hardcoded_strings' => $report['statistics']['hardcoded_text_patterns'],
        'categorized_strings' => array_sum(array_map('count', $categories)),
        'files_scanned' => $report['statistics']['files_scanned'],
        'blade_files' => $report['statistics']['blade_files'],
        'php_files' => $report['statistics']['php_files'],
    ],
    'categories_breakdown' => array_map('count', $categories),
    'sample_translations' => [
        'en' => array_map(fn($cat) => array_slice($cat, 0, 10), $enTranslations),
        'ar' => array_map(fn($cat) => array_slice($cat, 0, 10), $arTranslations),
    ],
    'priority_areas' => [],
    'implementation_steps' => [
        '1. Create language files' => [
            'Create lang/en directory structure',
            'Create lang/ar directory structure',
            'Organize translations by feature/module',
        ],
        '2. Replace hardcoded text' => [
            'Start with high-traffic pages (auth, dashboard)',
            'Replace text with __() function calls',
            'Test each page after replacement',
        ],
        '3. Add Arabic translations' => [
            'Hire professional translator for accuracy',
            'Review UI layout with RTL support',
            'Test Arabic version thoroughly',
        ],
        '4. Implement language switcher' => [
            'Add language toggle in UI',
            'Store user language preference',
            'Apply language to all pages',
        ],
    ],
    'estimated_effort' => [
        'translation_file_creation' => '2-4 hours',
        'code_refactoring' => '20-30 hours',
        'arabic_translation' => '10-15 hours',
        'testing' => '8-10 hours',
        'total' => '40-59 hours',
    ],
];

// Identify priority areas (most used components)
$fileFrequency = [];
foreach ($report['hardcoded_text_sample'] as $item) {
    $file = $item['file'];
    if (!isset($fileFrequency[$file])) {
        $fileFrequency[$file] = 0;
    }
    $fileFrequency[$file]++;
}
arsort($fileFrequency);
$detailedReport['priority_areas'] = array_slice($fileFrequency, 0, 10, true);

// Save detailed report
file_put_contents(
    __DIR__ . '/detailed_analysis_report.json',
    json_encode($detailedReport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

// Create sample EN translation file
$sampleEnContent = "<?php\n\nreturn [\n";
foreach (array_slice($enTranslations['common'], 0, 20, true) as $key => $value) {
    $sampleEnContent .= "    '{$key}' => '{$value}',\n";
}
$sampleEnContent .= "];\n";
file_put_contents(__DIR__ . '/sample_en_common.php', $sampleEnContent);

// Create sample AR translation file
$sampleArContent = "<?php\n\nreturn [\n";
foreach (array_slice($arTranslations['common'], 0, 20, true) as $key => $value) {
    $sampleArContent .= "    '{$key}' => '{$value}',\n";
}
$sampleArContent .= "];\n";
file_put_contents(__DIR__ . '/sample_ar_common.php', $sampleArContent);

echo "Detailed analysis completed!\n\n";
echo "Category breakdown:\n";
foreach ($categories as $category => $items) {
    echo "  {$category}: " . count($items) . " strings\n";
}
echo "\nTop 10 priority files for translation:\n";
$i = 1;
foreach ($detailedReport['priority_areas'] as $file => $count) {
    echo "  {$i}. {$file}: {$count} strings\n";
    $i++;
}
echo "\nEstimated effort: " . $detailedReport['estimated_effort']['total'] . "\n";
echo "\nReports saved:\n";
echo "  - detailed_analysis_report.json\n";
echo "  - sample_en_common.php\n";
echo "  - sample_ar_common.php\n";
