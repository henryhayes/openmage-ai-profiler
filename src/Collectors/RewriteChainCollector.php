<?php

class RewriteChainCollector extends AbstractCollector
{
    public function getCode() { return 'rewrite_chains'; }
    public function getTitle() { return 'Rewrite Chains'; }
    public function getCategory() { return 'Architecture'; }
    public function getDescription() { return 'Reports rewrite declarations by alias, potential conflicts and resolved winning classes.'; }
    public function getSince() { return '0.10.0'; }
    public function getDependencies() { return array('magento_bootstrap', 'modules', 'rewrites', 'rewrite_map'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Builds a rewrite chain view from module config.xml files and compares declared rewrites with Magento resolved winners.',
            'Module config.xml files, Mage merged configuration and class filesystem resolution',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so rewrite chain information is unavailable.');
            return;
        }

        $declarations = $this->collectDeclaredRewrites($context);
        $resolved = $this->collectResolvedRewrites();

        $totalAliases = 0;
        $totalDeclarations = 0;
        $conflicts = 0;
        $missingDeclaredFiles = 0;
        $missingWinningFiles = 0;

        foreach ($declarations as $type => $aliases) {
            ksort($aliases);

            foreach ($aliases as $alias => $rows) {
                $totalAliases++;
                $totalDeclarations += count($rows);

                if (count($rows) > 1) {
                    $conflicts++;
                }

                $winner = isset($resolved[$type][$alias]) ? $resolved[$type][$alias] : '[unknown]';
                $winnerFile = $this->classToFile($winner);

                if ($winner !== '[unknown]' && ($winnerFile === '' || !file_exists($winnerFile))) {
                    $missingWinningFiles++;
                }

                $section->addItem('Rewrite chain', $type . ' / ' . $alias);
                $section->addItem('  Declarations', count($rows));
                $section->addItem('  Conflict', count($rows) > 1 ? 'yes' : 'no');
                $section->addItem('  Resolved winning class', $winner);
                $section->addItem('  Winning class file exists', ($winnerFile !== '' && file_exists($winnerFile)) ? 'yes' : 'no');
                $section->addItem('  Winning class file', $winnerFile !== '' ? $winnerFile : '[unknown]');

                foreach ($rows as $row) {
                    $classFile = $this->classToFile($row['class']);

                    if ($classFile === '' || !file_exists($classFile)) {
                        $missingDeclaredFiles++;
                    }

                    $section->addItem(
                        '  Declared by',
                        $row['module'] . ' => ' . $row['class']
                    );
                    $section->addItem('    Config file', $row['config_file']);
                    $section->addItem('    Class file exists', ($classFile !== '' && file_exists($classFile)) ? 'yes' : 'no');
                    $section->addItem('    Class file', $classFile !== '' ? $classFile : '[unknown]');
                }
            }
        }

        $section->addItem('Summary / rewrite aliases', $totalAliases);
        $section->addItem('Summary / rewrite declarations', $totalDeclarations);
        $section->addItem('Summary / conflicting aliases', $conflicts);
        $section->addItem('Summary / missing declared class files', $missingDeclaredFiles);
        $section->addItem('Summary / missing winning class files', $missingWinningFiles);
    }

    protected function collectDeclaredRewrites(ProfilerContext $context)
    {
        $result = array(
            'models' => array(),
            'blocks' => array(),
            'helpers' => array(),
        );

        $root = $context->getMagentoRoot();

        foreach (array('core', 'community', 'local') as $pool) {
            $poolPath = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . $pool;

            if (!is_dir($poolPath)) {
                continue;
            }

            $files = glob($poolPath . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'config.xml');

            foreach ($files as $file) {
                $module = $this->moduleFromConfigFile($poolPath, $file);
                $xml = $this->loadXml($file);

                if (!$xml || !$xml->global) {
                    continue;
                }

                foreach (array('models', 'blocks', 'helpers') as $type) {
                    if (!$xml->global->{$type}) {
                        continue;
                    }

                    foreach ($xml->global->{$type}->children() as $group => $groupNode) {
                        if (!$groupNode->rewrite) {
                            continue;
                        }

                        foreach ($groupNode->rewrite->children() as $alias => $classNode) {
                            $aliasPath = (string)$group . '/' . (string)$alias;
                            $class = trim((string)$classNode);

                            if ($class === '') {
                                continue;
                            }

                            if (!isset($result[$type][$aliasPath])) {
                                $result[$type][$aliasPath] = array();
                            }

                            $result[$type][$aliasPath][] = array(
                                'module' => $module,
                                'class' => $class,
                                'config_file' => $file,
                            );
                        }
                    }
                }
            }
        }

        return $result;
    }

    protected function collectResolvedRewrites()
    {
        $result = array(
            'models' => array(),
            'blocks' => array(),
            'helpers' => array(),
        );

        foreach (array('models', 'blocks', 'helpers') as $type) {
            $nodes = Mage::getConfig()->getNode('global/' . $type);

            if (!$nodes) {
                continue;
            }

            foreach ($nodes->children() as $group => $groupNode) {
                if (!$groupNode->rewrite) {
                    continue;
                }

                foreach ($groupNode->rewrite->children() as $alias => $classNode) {
                    $result[$type][(string)$group . '/' . (string)$alias] = trim((string)$classNode);
                }
            }
        }

        return $result;
    }

    protected function moduleFromConfigFile($poolPath, $file)
    {
        $relative = str_replace(rtrim($poolPath, '/\\') . DIRECTORY_SEPARATOR, '', $file);
        $parts = explode(DIRECTORY_SEPARATOR, $relative);

        if (count($parts) < 2) {
            return '[unknown]';
        }

        return $parts[0] . '_' . $parts[1];
    }

    protected function loadXml($file)
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file);
        libxml_clear_errors();

        return $xml === false ? null : $xml;
    }

    protected function classToFile($class)
    {
        if ($class === '' || $class === '[unknown]') {
            return '';
        }

        try {
            $file = Mage::getConfig()->getOptions()->getCodeDir() . DIRECTORY_SEPARATOR
                . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';

            if (file_exists($file)) {
                return $file;
            }

            foreach (explode(PATH_SEPARATOR, get_include_path()) as $path) {
                $candidate = rtrim($path, '/\\') . DIRECTORY_SEPARATOR
                    . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';

                if (file_exists($candidate)) {
                    return $candidate;
                }
            }

            return $file;
        } catch (Exception $e) {
            return '';
        }
    }
}
