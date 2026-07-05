<?php

class LayoutCollector extends AbstractCollector
{
    public function getCode() { return 'layouts'; }
    public function getTitle() { return 'Layouts'; }
    public function getCategory() { return 'Architecture'; }
    public function getDescription() { return 'Reports Magento layout XML files and layout update declarations.'; }
    public function getSince() { return '0.7.0'; }
    public function getDependencies() { return array('magento_bootstrap', 'themes'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Reports layout XML update files declared by modules and layout files found in frontend themes.',
            'Mage merged configuration and filesystem scan',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so layout information is unavailable.');
            return;
        }

        $moduleLayoutFiles = $this->getDeclaredLayoutFiles();
        $themeLayoutFiles = $this->getThemeLayoutFiles();

        $section->addItem('Summary / declared module layout files', count($moduleLayoutFiles));
        $section->addItem('Summary / frontend theme layout files', count($themeLayoutFiles));
        $section->addItem('Summary / total layout files', count($moduleLayoutFiles) + count($themeLayoutFiles));

        foreach ($moduleLayoutFiles as $row) {
            $section->addItem('Declared layout file', $row['area'] . ' / ' . $row['module'] . ' / ' . $row['file']);
            $section->addItem('  Area', $row['area']);
            $section->addItem('  Module', $row['module']);
            $section->addItem('  File', $row['file']);
            $section->addItem('  Path', $row['path']);
            $section->addItem('  Exists', $row['exists']);
        }

        foreach ($themeLayoutFiles as $row) {
            $section->addItem('Theme layout file', $row['package'] . '/' . $row['theme'] . ' / ' . $row['file']);
            $section->addItem('  Package', $row['package']);
            $section->addItem('  Theme', $row['theme']);
            $section->addItem('  File', $row['file']);
            $section->addItem('  Path', $row['path']);
        }
    }

    protected function getDeclaredLayoutFiles()
    {
        $rows = array();
        $areas = array('frontend', 'adminhtml');

        foreach ($areas as $area) {
            $updatesNode = Mage::getConfig()->getNode($area . '/layout/updates');

            if (!$updatesNode) {
                continue;
            }

            foreach ($updatesNode->children() as $module => $node) {
                if (!$node->file) {
                    continue;
                }

                $file = trim((string)$node->file);
                $path = Mage::getBaseDir('design') . DS . $area . DS . 'base' . DS . 'default' . DS . 'layout' . DS . $file;

                $rows[] = array(
                    'area' => $area,
                    'module' => (string)$module,
                    'file' => $file,
                    'path' => $path,
                    'exists' => file_exists($path) ? 'yes' : 'no',
                );
            }
        }

        return $rows;
    }

    protected function getThemeLayoutFiles()
    {
        $rows = array();
        $base = Mage::getBaseDir('design') . DS . 'frontend';

        if (!is_dir($base)) {
            return $rows;
        }

        foreach (glob($base . DS . '*', GLOB_ONLYDIR) as $packagePath) {
            $package = basename($packagePath);

            foreach (glob($packagePath . DS . '*', GLOB_ONLYDIR) as $themePath) {
                $theme = basename($themePath);
                $layoutPath = $themePath . DS . 'layout';

                if (!is_dir($layoutPath)) {
                    continue;
                }

                foreach (glob($layoutPath . DS . '*.xml') as $filePath) {
                    $rows[] = array(
                        'package' => $package,
                        'theme' => $theme,
                        'file' => basename($filePath),
                        'path' => $filePath,
                    );
                }
            }
        }

        return $rows;
    }
}