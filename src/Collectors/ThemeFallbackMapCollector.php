<?php

class ThemeFallbackMapCollector extends AbstractCollector
{
    public function getCode() { return 'theme_fallback_map'; }
    public function getTitle() { return 'Theme Fallback Map'; }
    public function getCategory() { return 'Architecture'; }
    public function getDescription() { return 'Summarises frontend theme fallback and override depth by store design package.'; }
    public function getSince() { return '0.11.0'; }
    public function getDependencies() { return array('magento_bootstrap', 'stores', 'themes', 'theme_hierarchy'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Explains practical Magento 1 frontend fallback order and template/layout override depth for each active store package.',
            'Store design config and frontend design filesystem scan',
            'Medium'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so theme fallback data is unavailable.');
            return;
        }

        $root = $context->getMagentoRoot();
        $stores = Mage::app()->getStores(false);
        $packages = array();

        foreach ($stores as $store) {
            $package = Mage::getStoreConfig('design/package/name', $store);
            if ($package === '') {
                $package = 'default';
            }

            if (!isset($packages[$package])) {
                $packages[$package] = array();
            }

            $packages[$package][] = $store->getCode();
        }

        ksort($packages);

        foreach ($packages as $package => $storeCodes) {
            $theme = 'default';
            $themeDir = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'design' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . $package . DIRECTORY_SEPARATOR . $theme;
            $baseDir = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'design' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'base' . DIRECTORY_SEPARATOR . 'default';

            $section->addItem('Theme fallback', $package . '/' . $theme);
            $section->addItem('  Used by stores', implode(', ', $storeCodes));
            $section->addItem('  Practical fallback order', $package . '/' . $theme . ' -> default/default -> base/default');
            $section->addItem('  Template files', $this->countFiles($themeDir . DIRECTORY_SEPARATOR . 'template', 'phtml'));
            $section->addItem('  Layout XML files', $this->countFiles($themeDir . DIRECTORY_SEPARATOR . 'layout', 'xml'));
            $section->addItem('  Templates overriding base/default', $this->countOverrides($themeDir . DIRECTORY_SEPARATOR . 'template', $baseDir . DIRECTORY_SEPARATOR . 'template', 'phtml'));
            $section->addItem('  Layout files overriding base/default', $this->countOverrides($themeDir . DIRECTORY_SEPARATOR . 'layout', $baseDir . DIRECTORY_SEPARATOR . 'layout', 'xml'));
        }

        $section->addItem('Summary / packages used by stores', count($packages));
    }

    protected function countFiles($directory, $extension)
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $count = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === $extension) {
                $count++;
            }
        }
        return $count;
    }

    protected function countOverrides($themeDirectory, $baseDirectory, $extension)
    {
        if (!is_dir($themeDirectory) || !is_dir($baseDirectory)) {
            return 0;
        }

        $count = 0;
        $baseDirectory = rtrim($baseDirectory, '/\\') . DIRECTORY_SEPARATOR;
        $themeDirectory = rtrim($themeDirectory, '/\\') . DIRECTORY_SEPARATOR;

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($themeDirectory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile() || strtolower($fileInfo->getExtension()) !== $extension) {
                continue;
            }

            $relative = substr($fileInfo->getPathname(), strlen($themeDirectory));
            if (is_file($baseDirectory . $relative)) {
                $count++;
            }
        }

        return $count;
    }
}
