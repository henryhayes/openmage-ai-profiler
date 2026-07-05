<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors', '1');

$root = dirname(__FILE__);

require_once $root . '/lib/CollectorInterface.php';
require_once $root . '/lib/AbstractCollector.php';
require_once $root . '/lib/Section.php';
require_once $root . '/lib/Report.php';
require_once $root . '/lib/TextWriter.php';
require_once $root . '/lib/JsonWriter.php';
require_once $root . '/lib/MarkdownWriter.php';
require_once $root . '/lib/CollectorRegistry.php';
require_once $root . '/lib/Profiler.php';

require_once $root . '/collectors/EnvironmentCollector.php';

$versionFile = $root . '/VERSION';
$version = file_exists($versionFile) ? trim(file_get_contents($versionFile)) : 'unknown';

$outputDir = $root . '/output';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$report = new Report();
$report->setMetadata('Tool', 'OpenMage AI Profiler');
$report->setMetadata('Version', $version);
$report->setMetadata('Generated', date('c'));
$report->setMetadata('Schema', '0.1.0');

$registry = new CollectorRegistry();
$registry->register(new EnvironmentCollector());

$profiler = new Profiler($registry, $report);
$profiler->run();

$textWriter = new TextWriter();
$jsonWriter = new JsonWriter();
$markdownWriter = new MarkdownWriter();

$textWriter->write($report, $outputDir . '/ai-project-profile.txt');
$jsonWriter->write($report, $outputDir . '/ai-project-profile.json');
$markdownWriter->write($report, $outputDir . '/ai-project-profile.md');

echo "OpenMage AI Profiler completed.\n";
echo "Written:\n";
echo "- output/ai-project-profile.txt\n";
echo "- output/ai-project-profile.json\n";
echo "- output/ai-project-profile.md\n";