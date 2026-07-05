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

$cli = new CommandLine($argv);

$versionFile = $root . '/VERSION';
$version = file_exists($versionFile) ? trim(file_get_contents($versionFile)) : 'unknown';

$outputDir = $root . '/output';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

if ($cli->has('version')) {
    echo "OpenMage AI Profiler " . $version . PHP_EOL;
    exit(0);
}

if ($cli->has('help')) {

    echo "OpenMage AI Profiler\n\n";

    echo "Usage:\n";
    echo "  php dump_project_profile_ai.php [options]\n\n";

    echo "Options:\n";
    echo "  --root=/path        Magento/OpenMage root\n";
    echo "  --help             Show this help\n";
    echo "  --version          Show profiler version\n";

    exit(0);
}


$report = new Report();
$report->setMetadata('Tool', 'OpenMage AI Profiler');
$report->setMetadata('Tool Version', $version);
$report->setMetadata('Report Schema', '1.0');
$report->setMetadata('Report ID', date('Ymd-His') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)));
$report->setMetadata('Generated', date('c'));

$registry = new CollectorRegistry();
$registry->register(new EnvironmentCollector());

$projectRoot = $cli->get('root', $root);

$projectRoot = realpath($projectRoot);

if ($projectRoot === false) {
    fwrite(STDERR, "Invalid project root.\n");
    exit(1);
}

$context = new Context($projectRoot);

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