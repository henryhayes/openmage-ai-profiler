<?php

class XmlMergeAnalysisCollector extends AbstractCollector
{
    public function getCode() { return 'xml_merge_analysis'; }
    public function getTitle() { return 'XML Merge Analysis'; }
    public function getCategory() { return 'Architecture'; }
    public function getDescription() { return 'Summarises Magento XML merge surfaces across config, layout, system and adminhtml XML files.'; }
    public function getSince() { return '0.11.0'; }
    public function getDependencies() { return array('magento_bootstrap', 'modules', 'layouts'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Identifies XML files that participate in Magento configuration, admin, system and layout merging.',
            'Filesystem scan of module etc XML and theme layout XML',
            'Medium'
        );

        $root = $context->getMagentoRoot();
        $groups = array(
            'module config.xml' => $this->findFiles($root . '/app/code', 'config.xml'),
            'module system.xml' => $this->findFiles($root . '/app/code', 'system.xml'),
            'module adminhtml.xml' => $this->findFiles($root . '/app/code', 'adminhtml.xml'),
            'frontend layout XML' => $this->findLayoutFiles($root . '/app/design/frontend'),
        );

        $totalFiles = 0;
        $parseErrors = 0;

        foreach ($groups as $label => $files) {
            $section->addItem('XML merge group', $label);
            $section->addItem('  Files', count($files));
            $shown = 0;

            foreach ($files as $file) {
                $totalFiles++;
                $parsed = $this->isXmlParseable($file);
                if (!$parsed) {
                    $parseErrors++;
                }

                if ($shown < 60) {
                    $section->addItem('  XML file', $this->relative($root, $file));
                    $section->addItem('    Parseable', $parsed ? 'yes' : 'no');
                    $section->addItem('    Top nodes', $this->topNodes($file));
                }

                $shown++;
            }

            if (count($files) > 60) {
                $section->addItem('  Truncated', 'Only the first 60 XML files are shown for this merge group.');
            }
        }

        $section->addItem('Summary / XML merge files', $totalFiles);
        $section->addItem('Summary / XML parse errors', $parseErrors);
    }

    protected function findFiles($directory, $filename)
    {
        $files = array();
        if (!is_dir($directory)) {
            return $files;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getFilename() === $filename) {
                $files[] = $fileInfo->getPathname();
            }
        }
        sort($files);
        return $files;
    }

    protected function findLayoutFiles($directory)
    {
        $files = array();
        if (!is_dir($directory)) {
            return $files;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'xml' && strpos($fileInfo->getPathname(), DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR) !== false) {
                $files[] = $fileInfo->getPathname();
            }
        }
        sort($files);
        return $files;
    }

    protected function isXmlParseable($file)
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file);
        libxml_clear_errors();
        return $xml !== false;
    }

    protected function topNodes($file)
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file);
        libxml_clear_errors();
        if ($xml === false) {
            return '[parse error]';
        }
        $nodes = array();
        foreach ($xml->children() as $name => $node) {
            $nodes[] = (string)$name;
            if (count($nodes) >= 20) {
                $nodes[] = '[truncated]';
                break;
            }
        }
        return count($nodes) ? implode(', ', array_unique($nodes)) : '[none]';
    }

    protected function relative($root, $path)
    {
        $root = rtrim($root, '/\\') . DIRECTORY_SEPARATOR;
        return strpos($path, $root) === 0 ? substr($path, strlen($root)) : $path;
    }
}
