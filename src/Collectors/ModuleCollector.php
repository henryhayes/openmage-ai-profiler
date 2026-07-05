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
            'Reports installed Magento/OpenMage modules, activation status, code pool, version and declared dependencies.',
            'Mage::getConfig()->getNode("modules")',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so module information is unavailable.');
            return;
        }

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

                $modules[] = array(
                    'name' => (string)$moduleName,
                    'active' => $isActive,
                    'code_pool' => $codePool,
                    'version' => $version,
                    'depends' => $depends,
                );
            }

            usort($modules, array($this, 'sortModulesByName'));

            $section->addItem('Summary / total modules', $total);
            $section->addItem('Summary / active modules', $active);
            $section->addItem('Summary / inactive modules', $inactive);

            foreach ($codePools as $codePool => $count) {
                $section->addItem('Summary / code pool / ' . $codePool, $count);
            }

            foreach ($modules as $module) {
                $line = 'active=' . ($module['active'] ? 'yes' : 'no');
                $line .= '; codePool=' . $module['code_pool'];
                $line .= '; version=' . ($module['version'] !== '' ? $module['version'] : '[none]');
                $line .= '; depends=' . (count($module['depends']) ? implode(',', $module['depends']) : '[none]');

                $section->addItem($module['name'], $line);
            }
        } catch (Exception $e) {
            $section->addError($e->getMessage());
        }
    }

    public function sortModulesByName($a, $b)
    {
        return strcmp($a['name'], $b['name']);
    }
}