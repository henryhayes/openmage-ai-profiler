<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors', '1');

$root = dirname(__FILE__);

spl_autoload_register(function ($class) use ($root) {
    $locations = array(
        $root . '/src/' . $class . '.php',
        $root . '/src/Collectors/' . $class . '.php',
        $root . '/src/Context/' . $class . '.php',
        $root . '/src/Services/' . $class . '.php',
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
    echo "OpenMage AI Profiler " . $version . PHP_EOL;
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
    echo "  --help             Show this help\n";
    echo "  --version          Show profiler version\n";

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

if (!is_dir($outputDir)) {
    if (!mkdir($outputDir, 0755, true)) {
        fwrite(STDERR, "Unable to create output directory: {$outputDir}\n");
        exit(1);
    }
}

$report = new Report();
$report->setMetadata('Tool', 'OpenMage AI Profiler');
$report->setMetadata('Tool Version', $version);
$report->setMetadata('Report Schema', '1.0');
$report->setMetadata('Report ID', date('Ymd-His') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)));
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

$profiler = new ProfilerApplication($registry, $report, $context);
$profiler->run();

$txtFile = $outputDir . DIRECTORY_SEPARATOR . 'ai-project-profile.txt';
$jsonFile = $outputDir . DIRECTORY_SEPARATOR . 'ai-project-profile.json';
$markdownFile = $outputDir . DIRECTORY_SEPARATOR . 'ai-project-profile.md';
$contextFile = $outputDir . DIRECTORY_SEPARATOR . 'ai-project-context.txt';
$promptFile = $outputDir . DIRECTORY_SEPARATOR . 'ai-chatgpt-prompt.txt';

$aiContextBuilder = new AiContextBuilder();
$aiContext = $aiContextBuilder->build($report);
$aiContextWriter = new AiContextWriter();
$aiContextWriter->write($aiContext, $contextFile);
$aiPromptWriter = new AiPromptWriter();
$aiPromptWriter->write($aiContext, $promptFile);

$textWriter = new TxtReportWriter();
$jsonWriter = new JsonReportWriter();
$markdownWriter = new MarkdownReportWriter();

$textWriter->write($report, $txtFile);
$jsonWriter->write($report, $jsonFile);
$markdownWriter->write($report, $markdownFile);

echo "OpenMage AI Profiler completed.\n";
echo "Reports written to:\n";
echo $outputDir . "\n\n";
echo "- " . $txtFile . "\n";
echo "- " . $jsonFile . "\n";
echo "- " . $markdownFile . "\n";
echo "- " . $contextFile . "\n";
echo "- " . $promptFile . "\n";