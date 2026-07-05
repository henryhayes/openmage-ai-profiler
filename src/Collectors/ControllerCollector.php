<?php

class ControllerCollector extends AbstractCollector
{
    public function getCode() { return 'controllers'; }
    public function getTitle() { return 'Controllers'; }
    public function getCategory() { return 'Architecture'; }
    public function getDescription() { return 'Reports controller files found in local, community and core code pools.'; }
    public function getSince() { return '0.7.0'; }
    public function getDependencies() { return array('magento_bootstrap', 'modules'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Reports Magento controller files by code pool and module.',
            'Filesystem scan',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so controller information is unavailable.');
            return;
        }

        $rows = array();
        $codePools = array('local', 'community', 'core');

        foreach ($codePools as $pool) {
            $base = Mage::getBaseDir('code') . DS . $pool;

            if (!is_dir($base)) {
                continue;
            }

            $files = $this->findControllerFiles($base);

            foreach ($files as $file) {
                $rows[] = array(
                    'pool' => $pool,
                    'file' => $file,
                    'relative' => str_replace($base . DS, '', $file),
                    'module' => $this->detectModuleFromPath($base, $file),
                );
            }
        }

        $section->addItem('Summary / controller files', count($rows));

        foreach ($rows as $row) {
            $section->addItem('Controller file', $row['module'] . ' / ' . $row['relative']);
            $section->addItem('  Code pool', $row['pool']);
            $section->addItem('  Module', $row['module']);
            $section->addItem('  Path', $row['file']);
        }
    }

    protected function findControllerFiles($base)
    {
        $result = array();

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $path = $file->getPathname();

            if (strpos($path, DS . 'controllers' . DS) !== false && substr($path, -4) === '.php') {
                $result[] = $path;
            }
        }

        sort($result);

        return $result;
    }

    protected function detectModuleFromPath($base, $file)
    {
        $relative = str_replace($base . DS, '', $file);
        $parts = explode(DS, $relative);

        if (count($parts) >= 2) {
            return $parts[0] . '_' . $parts[1];
        }

        return '[unknown]';
    }
}