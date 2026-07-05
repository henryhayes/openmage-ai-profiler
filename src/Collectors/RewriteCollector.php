<?php

class RewriteCollector extends AbstractCollector
{
    public function getCode()
    {
        return 'rewrites';
    }

    public function getTitle()
    {
        return 'Rewrites';
    }

    public function getCategory()
    {
        return 'Architecture';
    }

    public function getDescription()
    {
        return 'Magento model, block and helper rewrite declarations, winners and conflicts.';
    }

    public function getSince()
    {
        return '0.4.0';
    }

    public function getDependencies()
    {
        return array('magento_bootstrap', 'modules');
    }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Reports Magento class rewrite declarations, winning resolved classes, rewrite conflicts and missing class files.',
            'Module config.xml files and Mage merged configuration',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so rewrite information is unavailable.');
            return;
        }

        $filesystem = $context->getFilesystem();
        $locator = $context->getResourceLocator();
        $xmlHelper = $context->getXmlHelper();

        $declarations = array();

        try {
            $modulesNode = Mage::getConfig()->getNode('modules');

            if (!$modulesNode) {
                $section->addError('No modules node found in Magento configuration.');
                return;
            }

            foreach ($modulesNode->children() as $moduleName => $moduleNode) {
                $codePool = (string)$moduleNode->codePool;

                if ($codePool === '') {
                    $codePool = 'unknown';
                }

                $modulePath = $this->getModulePath($locator, (string)$moduleName, $codePool);
                $configXml = $modulePath . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'config.xml';

                if (!$filesystem->fileExists($configXml)) {
                    continue;
                }

                $config = $xmlHelper->loadFile($configXml);

                if (!$config) {
                    continue;
                }

                $this->collectTypeDeclarations(
                    $xmlHelper,
                    $config,
                    'model',
                    '//global/models/*/rewrite/*',
                    (string)$moduleName,
                    $configXml,
                    $declarations
                );

                $this->collectTypeDeclarations(
                    $xmlHelper,
                    $config,
                    'block',
                    '//global/blocks/*/rewrite/*',
                    (string)$moduleName,
                    $configXml,
                    $declarations
                );

                $this->collectTypeDeclarations(
                    $xmlHelper,
                    $config,
                    'helper',
                    '//global/helpers/*/rewrite/*',
                    (string)$moduleName,
                    $configXml,
                    $declarations
                );
            }

            ksort($declarations);

            $totalDeclarations = 0;
            $modelDeclarations = 0;
            $blockDeclarations = 0;
            $helperDeclarations = 0;
            $conflictAliases = 0;
            $missingWinningClasses = 0;
            $missingDeclaredClasses = 0;

            foreach ($declarations as $key => $declarationSet) {
                $totalDeclarations += count($declarationSet);

                if (count($declarationSet) > 1) {
                    $conflictAliases++;
                }

                $first = reset($declarationSet);

                if ($first['type'] === 'model') {
                    $modelDeclarations += count($declarationSet);
                } elseif ($first['type'] === 'block') {
                    $blockDeclarations += count($declarationSet);
                } elseif ($first['type'] === 'helper') {
                    $helperDeclarations += count($declarationSet);
                }

                foreach ($declarationSet as $declaration) {
                    if (!$this->classFileExists($context, $declaration['class'])) {
                        $missingDeclaredClasses++;
                    }
                }

                $winner = $this->getWinningClass($first['type'], $first['alias']);

                if ($winner !== '' && !$this->classFileExists($context, $winner)) {
                    $missingWinningClasses++;
                }
            }

            $section->addItem('Summary / aliases with rewrites', count($declarations));
            $section->addItem('Summary / total rewrite declarations', $totalDeclarations);
            $section->addItem('Summary / model rewrite declarations', $modelDeclarations);
            $section->addItem('Summary / block rewrite declarations', $blockDeclarations);
            $section->addItem('Summary / helper rewrite declarations', $helperDeclarations);
            $section->addItem('Summary / aliases with conflicts', $conflictAliases);
            $section->addItem('Summary / missing declared class files', $missingDeclaredClasses);
            $section->addItem('Summary / missing winning class files', $missingWinningClasses);

            foreach ($declarations as $key => $declarationSet) {
                $first = reset($declarationSet);

                $winner = $this->getWinningClass($first['type'], $first['alias']);
                $original = $this->getOriginalClass($first['type'], $first['group'], $first['rewrite_alias']);

                $section->addItem('Rewrite', $key);
                $section->addItem('  Type', $first['type']);
                $section->addItem('  Alias', $first['alias']);
                $section->addItem('  Original class', $original !== '' ? $original : '[unknown]');
                $section->addItem('  Winning class', $winner !== '' ? $winner : '[unknown]');
                $section->addItem('  Winning class file exists', $winner !== '' && $this->classFileExists($context, $winner) ? 'yes' : 'no');
                $section->addItem('  Declaration count', count($declarationSet));
                $section->addItem('  Conflict', count($declarationSet) > 1 ? 'yes' : 'no');

                $position = 1;

                foreach ($declarationSet as $declaration) {
                    $section->addItem(
                        '  Declaration / ' . $position,
                        'module=' . $declaration['module']
                        . '; class=' . $declaration['class']
                        . '; classFileExists=' . ($this->classFileExists($context, $declaration['class']) ? 'yes' : 'no')
                        . '; config=' . $declaration['config_xml']
                    );

                    $position++;
                }
            }
        } catch (Exception $e) {
            $section->addError($e->getMessage());
        }
    }

    protected function collectTypeDeclarations(
        XmlHelper $xmlHelper,
        $config,
        $type,
        $xpath,
        $moduleName,
        $configXml,
        array &$declarations
    ) {
        $nodes = $xmlHelper->xpath($config, $xpath);

        foreach ($nodes as $node) {
            $rewriteAlias = $node->getName();
            $class = trim((string)$node);

            if ($class === '') {
                continue;
            }

            $parent = $node->xpath('..');

            if (!is_array($parent) || count($parent) === 0) {
                continue;
            }

            $groupParent = $parent[0]->xpath('..');

            if (!is_array($groupParent) || count($groupParent) === 0) {
                continue;
            }

            $group = $groupParent[0]->getName();
            $alias = $group . '/' . $rewriteAlias;
            $key = $type . ':' . $alias;

            if (!isset($declarations[$key])) {
                $declarations[$key] = array();
            }

            $declarations[$key][] = array(
                'type' => $type,
                'group' => $group,
                'rewrite_alias' => $rewriteAlias,
                'alias' => $alias,
                'class' => $class,
                'module' => $moduleName,
                'config_xml' => $configXml,
            );
        }
    }

    protected function getWinningClass($type, $alias)
    {
        try {
            if ($type === 'model') {
                return Mage::getConfig()->getModelClassName($alias);
            }

            if ($type === 'block') {
                return Mage::getConfig()->getBlockClassName($alias);
            }

            if ($type === 'helper') {
                return Mage::getConfig()->getHelperClassName($alias);
            }
        } catch (Exception $e) {
            return '';
        }

        return '';
    }

    protected function getOriginalClass($type, $group, $rewriteAlias)
    {
        try {
            $nodePath = '';

            if ($type === 'model') {
                $nodePath = 'global/models/' . $group . '/class';
            } elseif ($type === 'block') {
                $nodePath = 'global/blocks/' . $group . '/class';
            } elseif ($type === 'helper') {
                $nodePath = 'global/helpers/' . $group . '/class';
            }

            if ($nodePath === '') {
                return '';
            }

            $classPrefix = (string)Mage::getConfig()->getNode($nodePath);

            if ($classPrefix === '') {
                return '';
            }

            return $classPrefix . '_' . $this->aliasPartToClassPart($rewriteAlias);
        } catch (Exception $e) {
            return '';
        }
    }

    protected function aliasPartToClassPart($value)
    {
        $parts = explode('_', $value);
        $result = array();

        foreach ($parts as $part) {
            $result[] = ucfirst($part);
        }

        return implode('_', $result);
    }

    protected function classFileExists(ProfilerContext $context, $class)
    {
        $relative = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';

        $root = $context->getMagentoRoot();

        $paths = array(
            $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . $relative,
            $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'community' . DIRECTORY_SEPARATOR . $relative,
            $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . $relative,
            $root . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $relative,
        );

        foreach ($paths as $path) {
            if (is_file($path)) {
                return true;
            }
        }

        return false;
    }

    protected function getModulePath(ResourceLocator $locator, $moduleName, $codePool)
    {
        if ($codePool === 'core') {
            $base = $locator->codeCore();
        } elseif ($codePool === 'community') {
            $base = $locator->codeCommunity();
        } elseif ($codePool === 'local') {
            $base = $locator->codeLocal();
        } else {
            $base = $locator->code();
        }

        $parts = explode('_', $moduleName);

        return $base . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
    }
}