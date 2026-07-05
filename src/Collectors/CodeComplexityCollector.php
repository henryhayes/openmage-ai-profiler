<?php

class CodeComplexityCollector extends AbstractCollector
{
    public function getCode() { return 'code_complexity'; }
    public function getTitle() { return 'Code Complexity'; }
    public function getCategory() { return 'Architecture'; }
    public function getDescription() { return 'Reports largest PHP files and approximate method complexity hotspots.'; }
    public function getSince() { return '0.11.0'; }
    public function getDependencies() { return array('magento_bootstrap', 'modules'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Identifies large and complex custom PHP files likely to contain business logic or technical debt.',
            'Static PHP filesystem scan under app/code/local and app/code/community',
            'Medium'
        );

        $root = $context->getMagentoRoot();
        $directories = array(
            $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'local',
            $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'community',
        );

        $files = $this->collectPhpFiles($directories);
        $analysed = array();
        $totalLines = 0;
        $totalMethods = 0;
        $complexMethods = array();

        foreach ($files as $file) {
            $content = @file_get_contents($file);

            if ($content === false) {
                continue;
            }

            $lines = substr_count($content, "\n") + 1;
            $methods = $this->methodComplexities($content);
            $maxComplexity = 0;
            $maxMethod = '[none]';

            foreach ($methods as $method) {
                if ($method['complexity'] > $maxComplexity) {
                    $maxComplexity = $method['complexity'];
                    $maxMethod = $method['method'];
                }

                if ($method['complexity'] >= 20) {
                    $complexMethods[] = array(
                        'file' => $file,
                        'method' => $method['method'],
                        'complexity' => $method['complexity'],
                    );
                }
            }

            $analysed[] = array(
                'file' => $file,
                'relative' => $this->relative($root, $file),
                'lines' => $lines,
                'methods' => count($methods),
                'max_complexity' => $maxComplexity,
                'max_method' => $maxMethod,
            );

            $totalLines += $lines;
            $totalMethods += count($methods);
        }

        usort($analysed, array($this, 'sortByLinesDesc'));
        usort($complexMethods, array($this, 'sortByComplexityDesc'));

        $shown = 0;
        foreach ($analysed as $row) {
            if ($shown >= 80) {
                break;
            }

            $section->addItem('Large PHP file', $row['relative']);
            $section->addItem('  Lines', $row['lines']);
            $section->addItem('  Methods', $row['methods']);
            $section->addItem('  Highest approximate method complexity', $row['max_complexity']);
            $section->addItem('  Highest complexity method', $row['max_method']);
            $shown++;
        }

        $shown = 0;
        foreach ($complexMethods as $row) {
            if ($shown >= 80) {
                break;
            }

            $section->addItem('Complex method', $this->relative($root, $row['file']) . '::' . $row['method']);
            $section->addItem('  Approximate complexity', $row['complexity']);
            $shown++;
        }

        $section->addItem('Summary / PHP files scanned', count($analysed));
        $section->addItem('Summary / PHP lines scanned', $totalLines);
        $section->addItem('Summary / methods found', $totalMethods);
        $section->addItem('Summary / complex methods >=20', count($complexMethods));
    }

    protected function collectPhpFiles(array $directories)
    {
        $files = array();

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'php') {
                    $files[] = $fileInfo->getPathname();
                }
            }
        }

        sort($files);
        return $files;
    }

    protected function methodComplexities($content)
    {
        $methods = array();

        if (!preg_match_all('#function\s+([A-Za-z0-9_]+)\s*\([^\)]*\)\s*\{#', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return $methods;
        }

        $count = count($matches[0]);

        for ($i = 0; $i < $count; $i++) {
            $start = $matches[0][$i][1];
            $end = ($i + 1 < $count) ? $matches[0][$i + 1][1] : strlen($content);
            $body = substr($content, $start, $end - $start);

            $complexity = 1;
            $complexity += preg_match_all('#\b(if|elseif|for|foreach|while|case|catch)\b#', $body, $unused);
            $complexity += preg_match_all('#(\&\&|\|\||\?)#', $body, $unused);

            $methods[] = array(
                'method' => $matches[1][$i][0],
                'complexity' => $complexity,
            );
        }

        return $methods;
    }

    public function sortByLinesDesc($left, $right)
    {
        if ($left['lines'] === $right['lines']) {
            return strcmp($left['relative'], $right['relative']);
        }

        return ($left['lines'] > $right['lines']) ? -1 : 1;
    }

    public function sortByComplexityDesc($left, $right)
    {
        if ($left['complexity'] === $right['complexity']) {
            return strcmp($left['file'], $right['file']);
        }

        return ($left['complexity'] > $right['complexity']) ? -1 : 1;
    }

    protected function relative($root, $path)
    {
        $root = rtrim($root, '/\\') . DIRECTORY_SEPARATOR;
        return strpos($path, $root) === 0 ? substr($path, strlen($root)) : $path;
    }
}
