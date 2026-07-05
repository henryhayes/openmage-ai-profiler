<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors', '1');

$root = dirname(__FILE__);

spl_autoload_register(function ($class) use ($root) {
    
    $locations = array(
        $root . '/src/' . $class . '.php',
        $root . '/src/Collectors/' . $class . '.php',
    );

    foreach ($locations as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

$versionFile = $root . '/VERSION';
$version = file_exists($versionFile) ? trim(file_get_contents($versionFile)) : 'unknown';

$outputDir = $root . '/output';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$report = new Report();
$report->setMetadata('Tool', 'OpenMage AI Profiler');
$report->setMetadata('Tool Version', $version);
$report->setMetadata('Report Schema', '1.0');
$report->setMetadata('Report ID', date('Ymd-His') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)));
$report->setMetadata('Generated', date('c'));

$registry = new CollectorRegistry();
$registry->register(new EnvironmentCollector());

$context = new Context($root);

$profiler = new Profiler($registry, $report, $context);
$profiler->run();

$textWriter = new TxtReportWriter();
$jsonWriter = new JsonReportWriter();
$markdownWriter = new MarkdownReportWriter();

$textWriter->write($report, $outputDir . '/ai-project-profile.txt');
$jsonWriter->write($report, $outputDir . '/ai-project-profile.json');
$markdownWriter->write($report, $outputDir . '/ai-project-profile.md');

echo "OpenMage AI Profiler completed.\n";
echo "Written:\n";
echo "- output/ai-project-profile.txt\n";
echo "- output/ai-project-profile.json\n";
echo "- output/ai-project-profile.md\n";