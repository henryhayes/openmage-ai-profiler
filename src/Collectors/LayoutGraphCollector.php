<?php

class LayoutGraphCollector extends AbstractCollector
{
    public function getCode() { return 'layout_graph'; }
    public function getTitle() { return 'Layout Graph'; }
    public function getCategory() { return 'Architecture'; }
    public function getDescription() { return 'Parses layout XML files into handles, blocks, references, actions and template declarations.'; }
    public function getSince() { return '0.10.0'; }
    public function getDependencies() { return array('magento_bootstrap', 'layouts'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Builds an AI-oriented layout graph from declared module layout files and frontend theme layout files.',
            'Layout XML files declared in Mage configuration and discovered in frontend themes',
            'Medium'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so layout graph information is unavailable.');
            return;
        }

        $files = $this->getLayoutFiles($context);
        $handleStats = array();
        $totalHandles = 0;
        $totalBlocks = 0;
        $totalReferences = 0;
        $totalActions = 0;
        $totalTemplates = 0;
        $parseErrors = 0;

        foreach ($files as $fileRow) {
            $xml = $this->loadXml($fileRow['path']);

            if (!$xml) {
                $parseErrors++;
                $section->addItem('Layout file parse error', $fileRow['label']);
                $section->addItem('  Path', $fileRow['path']);
                continue;
            }

            $fileHandles = 0;
            $fileBlocks = 0;
            $fileReferences = 0;
            $fileActions = 0;
            $fileTemplates = 0;

            foreach ($xml->children() as $handleName => $handleNode) {
                $handleName = (string)$handleName;

                if ($handleName === '') {
                    continue;
                }

                $fileHandles++;
                $totalHandles++;

                if (!isset($handleStats[$handleName])) {
                    $handleStats[$handleName] = array(
                        'files' => 0,
                        'blocks' => 0,
                        'references' => 0,
                        'actions' => 0,
                        'templates' => 0,
                        'sample_files' => array(),
                    );
                }

                $handleStats[$handleName]['files']++;

                if (count($handleStats[$handleName]['sample_files']) < 5) {
                    $handleStats[$handleName]['sample_files'][] = $fileRow['label'];
                }

                $blocks = $handleNode->xpath('.//block');
                $references = $handleNode->xpath('.//reference');
                $actions = $handleNode->xpath('.//action');
                $templates = $handleNode->xpath('.//*[@template]');

                $blockCount = is_array($blocks) ? count($blocks) : 0;
                $referenceCount = is_array($references) ? count($references) : 0;
                $actionCount = is_array($actions) ? count($actions) : 0;
                $templateCount = is_array($templates) ? count($templates) : 0;

                $fileBlocks += $blockCount;
                $fileReferences += $referenceCount;
                $fileActions += $actionCount;
                $fileTemplates += $templateCount;

                $totalBlocks += $blockCount;
                $totalReferences += $referenceCount;
                $totalActions += $actionCount;
                $totalTemplates += $templateCount;

                $handleStats[$handleName]['blocks'] += $blockCount;
                $handleStats[$handleName]['references'] += $referenceCount;
                $handleStats[$handleName]['actions'] += $actionCount;
                $handleStats[$handleName]['templates'] += $templateCount;
            }

            $section->addItem('Layout graph file', $fileRow['label']);
            $section->addItem('  Type', $fileRow['type']);
            $section->addItem('  Path', $fileRow['path']);
            $section->addItem('  Handles', $fileHandles);
            $section->addItem('  Blocks', $fileBlocks);
            $section->addItem('  References', $fileReferences);
            $section->addItem('  Actions', $fileActions);
            $section->addItem('  Template declarations', $fileTemplates);
        }

        uasort($handleStats, array($this, 'sortHandles'));

        $shown = 0;

        foreach ($handleStats as $handleName => $stats) {
            if ($shown >= 80) {
                break;
            }

            $section->addItem('Layout handle', $handleName);
            $section->addItem('  Files', $stats['files']);
            $section->addItem('  Blocks', $stats['blocks']);
            $section->addItem('  References', $stats['references']);
            $section->addItem('  Actions', $stats['actions']);
            $section->addItem('  Template declarations', $stats['templates']);
            $section->addItem('  Sample files', implode(', ', $stats['sample_files']));
            $shown++;
        }

        if (count($handleStats) > 80) {
            $section->addItem('Layout handles truncated', 'Only the first 80 handles by impact are shown.');
        }

        $section->addItem('Summary / layout files parsed', count($files) - $parseErrors);
        $section->addItem('Summary / layout parse errors', $parseErrors);
        $section->addItem('Summary / layout handles', count($handleStats));
        $section->addItem('Summary / handle declarations', $totalHandles);
        $section->addItem('Summary / block declarations', $totalBlocks);
        $section->addItem('Summary / reference declarations', $totalReferences);
        $section->addItem('Summary / action declarations', $totalActions);
        $section->addItem('Summary / template declarations', $totalTemplates);
    }

    protected function sortHandles($left, $right)
    {
        $leftScore = $left['blocks'] + $left['references'] + $left['actions'] + $left['templates'];
        $rightScore = $right['blocks'] + $right['references'] + $right['actions'] + $right['templates'];

        if ($leftScore === $rightScore) {
            return 0;
        }

        return ($leftScore > $rightScore) ? -1 : 1;
    }

    protected function getLayoutFiles(ProfilerContext $context)
    {
        $files = array();

        $areas = array('frontend', 'adminhtml');

        foreach ($areas as $area) {
            $updatesNode = Mage::getConfig()->getNode($area . '/layout/updates');

            if ($updatesNode) {
                foreach ($updatesNode->children() as $module => $node) {
                    if (!$node->file) {
                        continue;
                    }

                    $file = trim((string)$node->file);
                    $path = Mage::getBaseDir('design') . DIRECTORY_SEPARATOR . $area . DIRECTORY_SEPARATOR
                        . 'base' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . $file;

                    if (is_file($path)) {
                        $files[] = array(
                            'type' => 'declared',
                            'label' => $area . ' / ' . (string)$module . ' / ' . $file,
                            'path' => $path,
                        );
                    }
                }
            }
        }

        $frontend = $context->getResourceLocator()->frontendDesign();

        if (is_dir($frontend)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($frontend, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile() || strtolower($fileInfo->getExtension()) !== 'xml') {
                    continue;
                }

                if (strpos($fileInfo->getPath(), DIRECTORY_SEPARATOR . 'layout') === false) {
                    continue;
                }

                $path = $fileInfo->getPathname();
                $relative = str_replace(rtrim($frontend, '/\\') . DIRECTORY_SEPARATOR, '', $path);

                $files[] = array(
                    'type' => 'theme',
                    'label' => $relative,
                    'path' => $path,
                );
            }
        }

        return $this->uniqueFiles($files);
    }

    protected function uniqueFiles(array $files)
    {
        $seen = array();
        $result = array();

        foreach ($files as $file) {
            if (isset($seen[$file['path']])) {
                continue;
            }

            $seen[$file['path']] = true;
            $result[] = $file;
        }

        return $result;
    }

    protected function loadXml($file)
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file);
        libxml_clear_errors();

        return $xml === false ? null : $xml;
    }
}
