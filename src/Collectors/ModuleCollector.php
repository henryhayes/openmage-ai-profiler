<?php

class ModuleCollector extends AbstractCollector
{
    public function getCode()
    {
        return 'modules';
    }

    public function getTitle()
    {
        return 'Modules';
    }

    public function getCategory()
    {
        return 'Architecture';
    }
    
    public function getDescription()
    {
        return 'Installed Magento/OpenMage module inventory.';
    }

    public function getSince()
    {
        return '0.3.0';
    }

    public function getDependencies()
    {
        return array('magento_bootstrap');
    }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Reports installed Magento/OpenMage modules, activation status, code pool, version, dependencies, filesystem presence and basic XML/code metrics.',
            'Mage::getConfig()->getNode("modules"), app/code and module XML files',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so module information is unavailable.');
            return;
        }

        $filesystem = $context->getFilesystem();
        $locator = $context->getResourceLocator();
        $xmlHelper = $context->getXmlHelper();

        try {
            $modulesNode = Mage::getConfig()->getNode('modules');

            if (!$modulesNode) {
                $section->addError('No modules node found in Magento configuration.');
                return;
            }

            $modules = array();

            $total = 0;
            $active = 0;
            $inactive = 0;

            $codePools = array(
                'core' => 0,
                'community' => 0,
                'local' => 0,
                'unknown' => 0,
            );

            $configXmlCount = 0;
            $systemXmlCount = 0;
            $adminhtmlXmlCount = 0;

            $totalControllers = 0;
            $totalModels = 0;
            $totalBlocks = 0;
            $totalHelpers = 0;
            $totalSetupScripts = 0;
            $totalRewrites = 0;
            $totalObservers = 0;
            $totalCronJobs = 0;
            $totalRouters = 0;

            foreach ($modulesNode->children() as $moduleName => $moduleNode) {
                $total++;

                $isActive = ((string)$moduleNode->active === 'true');
                $codePool = (string)$moduleNode->codePool;
                $version = (string)$moduleNode->version;

                if ($codePool === '') {
                    $codePool = 'unknown';
                }

                if (!isset($codePools[$codePool])) {
                    $codePools[$codePool] = 0;
                }

                $codePools[$codePool]++;

                if ($isActive) {
                    $active++;
                } else {
                    $inactive++;
                }

                $depends = array();

                if ($moduleNode->depends) {
                    foreach ($moduleNode->depends->children() as $dependencyName => $dependencyNode) {
                        $depends[] = (string)$dependencyName;
                    }
                }

                $modulePath = $this->getModulePath($locator, $moduleName, $codePool);

                $configXml = $modulePath . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'config.xml';
                $systemXml = $modulePath . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'system.xml';
                $adminhtmlXml = $modulePath . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'adminhtml.xml';

                $configXmlExists = $filesystem->fileExists($configXml);
                $systemXmlExists = $filesystem->fileExists($systemXml);
                $adminhtmlXmlExists = $filesystem->fileExists($adminhtmlXml);

                if ($configXmlExists) {
                    $configXmlCount++;
                }

                if ($systemXmlExists) {
                    $systemXmlCount++;
                }

                if ($adminhtmlXmlExists) {
                    $adminhtmlXmlCount++;
                }

                $config = $configXmlExists ? $xmlHelper->loadFile($configXml) : null;

                $controllers = $this->countModuleFiles($filesystem, $modulePath, 'controllers', array('php'));
                $models = $this->countModuleFiles($filesystem, $modulePath, 'Model', array('php'));
                $blocks = $this->countModuleFiles($filesystem, $modulePath, 'Block', array('php'));
                $helpers = $this->countModuleFiles($filesystem, $modulePath, 'Helper', array('php'));
                $setupScripts = $this->countSetupScripts($filesystem, $modulePath);

                $rewrites = $this->countRewrites($xmlHelper, $config);
                $observers = $this->countObservers($xmlHelper, $config);
                $cronJobs = $this->countCronJobs($xmlHelper, $config);
                $routers = $this->countRouters($xmlHelper, $config);

                $totalControllers += $controllers;
                $totalModels += $models;
                $totalBlocks += $blocks;
                $totalHelpers += $helpers;
                $totalSetupScripts += $setupScripts;
                $totalRewrites += $rewrites;
                $totalObservers += $observers;
                $totalCronJobs += $cronJobs;
                $totalRouters += $routers;

                $modules[] = array(
                    'name' => (string)$moduleName,
                    'active' => $isActive,
                    'code_pool' => $codePool,
                    'version' => $version,
                    'depends' => $depends,
                    'path' => $modulePath,
                    'path_exists' => is_dir($modulePath),
                    'config_xml' => $configXmlExists,
                    'system_xml' => $systemXmlExists,
                    'adminhtml_xml' => $adminhtmlXmlExists,
                    'controllers' => $controllers,
                    'models' => $models,
                    'blocks' => $blocks,
                    'helpers' => $helpers,
                    'setup_scripts' => $setupScripts,
                    'rewrites' => $rewrites,
                    'observers' => $observers,
                    'cron_jobs' => $cronJobs,
                    'routers' => $routers,
                );
            }

            usort($modules, array($this, 'sortModulesByName'));

            $section->addItem('Summary / total modules', $total);
            $section->addItem('Summary / active modules', $active);
            $section->addItem('Summary / inactive modules', $inactive);

            foreach ($codePools as $codePool => $count) {
                $section->addItem('Summary / code pool / ' . $codePool, $count);
            }

            $section->addItem('Summary / modules with config.xml', $configXmlCount);
            $section->addItem('Summary / modules with system.xml', $systemXmlCount);
            $section->addItem('Summary / modules with adminhtml.xml', $adminhtmlXmlCount);
            $section->addItem('Summary / controllers', $totalControllers);
            $section->addItem('Summary / models', $totalModels);
            $section->addItem('Summary / blocks', $totalBlocks);
            $section->addItem('Summary / helpers', $totalHelpers);
            $section->addItem('Summary / setup scripts', $totalSetupScripts);
            $section->addItem('Summary / rewrites declared in config.xml', $totalRewrites);
            $section->addItem('Summary / observers declared in config.xml', $totalObservers);
            $section->addItem('Summary / cron jobs declared in config.xml', $totalCronJobs);
            $section->addItem('Summary / routers declared in config.xml', $totalRouters);

            foreach ($modules as $module) {
                $line = 'active=' . ($module['active'] ? 'yes' : 'no');
                $line .= '; codePool=' . $module['code_pool'];
                $line .= '; version=' . ($module['version'] !== '' ? $module['version'] : '[none]');
                $line .= '; depends=' . (count($module['depends']) ? implode(',', $module['depends']) : '[none]');
                $line .= '; pathExists=' . ($module['path_exists'] ? 'yes' : 'no');
                $line .= '; config.xml=' . ($module['config_xml'] ? 'yes' : 'no');
                $line .= '; system.xml=' . ($module['system_xml'] ? 'yes' : 'no');
                $line .= '; adminhtml.xml=' . ($module['adminhtml_xml'] ? 'yes' : 'no');
                $line .= '; controllers=' . $module['controllers'];
                $line .= '; models=' . $module['models'];
                $line .= '; blocks=' . $module['blocks'];
                $line .= '; helpers=' . $module['helpers'];
                $line .= '; setupScripts=' . $module['setup_scripts'];
                $line .= '; rewrites=' . $module['rewrites'];
                $line .= '; observers=' . $module['observers'];
                $line .= '; cronJobs=' . $module['cron_jobs'];
                $line .= '; routers=' . $module['routers'];

                $section->addItem($module['name'], $line);
            }
        } catch (Exception $e) {
            $section->addError($e->getMessage());
        }
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

    protected function countModuleFiles(Filesystem $filesystem, $modulePath, $relativePath, array $extensions)
    {
        return $filesystem->countFiles(
            $modulePath . DIRECTORY_SEPARATOR . $relativePath,
            $extensions
        );
    }

    protected function countSetupScripts(Filesystem $filesystem, $modulePath)
    {
        $sqlPath = $modulePath . DIRECTORY_SEPARATOR . 'sql';
        $dataPath = $modulePath . DIRECTORY_SEPARATOR . 'data';

        return $filesystem->countFiles($sqlPath, array('php', 'sql'))
            + $filesystem->countFiles($dataPath, array('php', 'sql'));
    }

    protected function countRewrites(XmlHelper $xmlHelper, $config)
    {
        if (!$config) {
            return 0;
        }

        return $xmlHelper->countXpath($config, '//rewrite/*');
    }

    protected function countObservers(XmlHelper $xmlHelper, $config)
    {
        if (!$config) {
            return 0;
        }

        return $xmlHelper->countXpath($config, '//events/*/observers/*');
    }

    protected function countCronJobs(XmlHelper $xmlHelper, $config)
    {
        if (!$config) {
            return 0;
        }

        return $xmlHelper->countXpath($config, '//crontab/jobs/*');
    }

    protected function countRouters(XmlHelper $xmlHelper, $config)
    {
        if (!$config) {
            return 0;
        }

        return $xmlHelper->countXpath($config, '//routers/*');
    }

    public function sortModulesByName($a, $b)
    {
        return strcmp($a['name'], $b['name']);
    }
}