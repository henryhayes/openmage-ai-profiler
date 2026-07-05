<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors', '1');

$root = dirname(__FILE__);

spl_autoload_register(function ($class) use ($root) {
    $locations = array(
        $root . '/src/' . $class . '.php',
        $root . '/src/Application/' . $class . '.php',
        $root . '/src/Collectors/' . $class . '.php',
        $root . '/src/Context/' . $class . '.php',
        $root . '/src/Services/' . $class . '.php',
        $root . '/src/Writers/' . $class . '.php',
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

if ($cli->has('version')) {
    echo 'OpenMage AI Profiler ' . $version . PHP_EOL;
    exit(0);
}

if ($cli->has('help')) {
    echo "OpenMage AI Profiler\n\n";
    echo "Usage:\n";
    echo "  php profiler.php [options]\n";
    echo "  php dump_project_profile_ai.php [options]\n\n";
    echo "Options:\n";
    echo "  --root=/path        Magento/OpenMage root\n";
    echo "  --output=/path      Report output directory\n";
    echo "  --markdown          Also write ai-project-profile.md\n";
    echo "  --help              Show this help\n";
    echo "  --version           Show profiler version\n";
    exit(0);
}

$projectRoot = $cli->get('root', $root);
$projectRoot = realpath($projectRoot);

if ($projectRoot === false) {
    fwrite(STDERR, "Invalid project root.\n");
    exit(1);
}

$outputDir = $cli->get(
    'output',
    $root . DIRECTORY_SEPARATOR . 'output'
);

if (!preg_match('#^([A-Za-z]:)?[/\\\\]#', $outputDir)) {
    $outputDir = $projectRoot . DIRECTORY_SEPARATOR . $outputDir;
}

$outputDir = rtrim($outputDir, '/\\');

$reportManager = new ReportManager($outputDir);

if (!$reportManager->ensureOutputDirectory()) {
    fwrite(STDERR, "Unable to create output directory: {$outputDir}\n");
    exit(1);
}

$report = new Report();
$report->setMetadata('Tool', 'OpenMage AI Profiler');
$report->setMetadata('Tool Version', $version);
$report->setMetadata('Report Schema', '1.0');
$report->setMetadata(
    'Report ID',
    date('Ymd-His') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 8))
);
$report->setMetadata('Generated', date('c'));

$context = new ProfilerContext($projectRoot);

$registry = new CollectorRegistry();
$registry->register(new EnvironmentCollector());
$registry->register(new PhpCollector());
$registry->register(new MagentoBootstrapCollector());
$registry->register(new MagentoCollector());
$registry->register(new StoreCollector());
$registry->register(new ModuleCollector());
$registry->register(new ThemeCollector());
$registry->register(new ThemeHierarchyCollector());
$registry->register(new RewriteCollector());
$registry->register(new RewriteMapCollector());
$registry->register(new CronCollector());
$registry->register(new IndexCollector());
$registry->register(new CacheCollector());
$registry->register(new DatabaseCollector());
$registry->register(new LayoutCollector());
$registry->register(new RouterCollector());
$registry->register(new ControllerCollector());
$registry->register(new ObserverCollector());

$profiler = new ProfilerApplication($registry, $report, $context);
$profiler->run();

$writtenFiles = $reportManager->writeAll($report, array(
    'markdown' => $cli->has('markdown'),
));

echo "OpenMage AI Profiler completed.\n";
echo "Reports written to:\n";
echo $reportManager->getOutputDir() . "\n\n";

foreach ($writtenFiles as $file) {
    echo '- ' . $file . "\n";
}