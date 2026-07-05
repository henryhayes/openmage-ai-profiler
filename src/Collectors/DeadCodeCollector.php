<?php

class DeadCodeCollector extends AbstractCollector
{
    public function getCode() { return 'dead_code'; }
    public function getTitle() { return 'Dead Code Indicators'; }
    public function getCategory() { return 'Architecture'; }
    public function getDescription() { return 'Reports likely dead, orphaned or stale Magento artefacts.'; }
    public function getSince() { return '0.11.0'; }
    public function getDependencies() { return array('magento_bootstrap', 'modules', 'themes', 'layouts', 'templates'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Highlights likely dead code indicators such as inactive modules with code, active modules with missing paths and orphaned layout/template files.',
            'Merged module config and filesystem scans',
            'Medium'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so dead-code indicators are limited.');
        }

        $root = $context->getMagentoRoot();
        $modules = $context->isMageBootstrapped() ? $this->collectModules() : array();
        $inactiveWithCode = 0;
        $activeMissingPath = 0;
        $inactiveBehavioural = 0;

        foreach ($modules as $module) {
            if ($module['active'] === 'no' && $module['path_exists'] === 'yes') {
                $inactiveWithCode++;
                $section->addItem('Inactive module with code', $module['name'] . ' / ' . $module['codePool']);
            }

            if ($module['active'] === 'yes' && $module['path_exists'] === 'no') {
                $activeMissingPath++;
                $section->addItem('Active module with missing path', $module['name'] . ' / ' . $module['codePool']);
            }

            if ($module['active'] === 'no' && ($module['controllers'] > 0 || $module['models'] > 0 || $module['observers'] > 0 || $module['rewrites'] > 0)) {
                $inactiveBehavioural++;
                $section->addItem('Inactive behavioural module', $module['name'] . '; controllers=' . $module['controllers'] . '; models=' . $module['models'] . '; rewrites=' . $module['rewrites'] . '; observers=' . $module['observers']);
            }
        }

        $orphanLayouts = $this->orphanLayoutFiles($root);
        $shown = 0;
        foreach ($orphanLayouts as $file) {
            if ($shown >= 80) {
                break;
            }
            $section->addItem('Possible orphan layout file', $this->relative($root, $file));
            $shown++;
        }

        $section->addItem('Summary / inactive modules with code', $inactiveWithCode);
        $section->addItem('Summary / active modules with missing paths', $activeMissingPath);
        $section->addItem('Summary / inactive behavioural modules', $inactiveBehavioural);
        $section->addItem('Summary / possible orphan layout files', count($orphanLayouts));
    }

    protected function collectModules()
    {
        $modules = array();
        $nodes = Mage::getConfig()->getNode('modules');

        if (!$nodes) {
            return $modules;
        }

        foreach ($nodes->children() as $name => $node) {
            $codePool = $node->codePool ? (string)$node->codePool : 'unknown';
            $path = $this->modulePath((string)$name, $codePool);
            $modules[] = array(
                'name' => (string)$name,
                'codePool' => $codePool,
                'active' => ((string)$node->active === 'true') ? 'yes' : 'no',
                'path_exists' => is_dir($path) ? 'yes' : 'no',
                'controllers' => is_dir($path . DIRECTORY_SEPARATOR . 'controllers') ? $this->countFiles($path . DIRECTORY_SEPARATOR . 'controllers', 'php') : 0,
                'models' => is_dir($path . DIRECTORY_SEPARATOR . 'Model') ? $this->countFiles($path . DIRECTORY_SEPARATOR . 'Model', 'php') : 0,
                'rewrites' => $this->countConfigNodes($path . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'config.xml', 'rewrite'),
                'observers' => $this->countConfigNodes($path . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'config.xml', 'observers'),
            );
        }

        return $modules;
    }

    protected function modulePath($module, $codePool)
    {
        $parts = explode('_', $module);
        if (count($parts) < 2 || $codePool === 'unknown') {
            return '';
        }

        return Mage::getBaseDir('code') . DIRECTORY_SEPARATOR . $codePool . DIRECTORY_SEPARATOR . $parts[0] . DIRECTORY_SEPARATOR . $parts[1];
    }

    protected function countFiles($directory, $extension)
    {
        $count = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === $extension) {
                $count++;
            }
        }
        return $count;
    }

    protected function countConfigNodes($file, $nodeName)
    {
        if (!is_file($file)) {
            return 0;
        }
        $content = @file_get_contents($file);
        if ($content === false) {
            return 0;
        }
        return preg_match_all('#<' . preg_quote($nodeName, '#') . '\b#', $content, $unused);
    }

    protected function orphanLayoutFiles($root)
    {
        $files = array();
        $layoutRoot = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'design' . DIRECTORY_SEPARATOR . 'frontend';

        if (!is_dir($layoutRoot)) {
            return $files;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($layoutRoot, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile() || strtolower($fileInfo->getExtension()) !== 'xml') {
                continue;
            }

            if (strpos($fileInfo->getPathname(), DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR) === false) {
                continue;
            }

            $content = @file_get_contents($fileInfo->getPathname());
            if ($content !== false && trim($content) === '') {
                $files[] = $fileInfo->getPathname();
            }
        }

        sort($files);
        return $files;
    }

    protected function relative($root, $path)
    {
        $root = rtrim($root, '/\\') . DIRECTORY_SEPARATOR;
        return strpos($path, $root) === 0 ? substr($path, strlen($root)) : $path;
    }
}
