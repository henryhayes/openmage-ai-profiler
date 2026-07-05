<?php

class ModuleDependencyGraphCollector extends AbstractCollector
{
    public function getCode() { return 'module_dependency_graph'; }
    public function getTitle() { return 'Module Dependency Graph'; }
    public function getCategory() { return 'Architecture'; }
    public function getDescription() { return 'Builds dependency and reverse-dependency relationships between Magento modules.'; }
    public function getSince() { return '0.11.0'; }
    public function getDependencies() { return array('magento_bootstrap', 'modules'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Maps module dependencies and reverse dependencies so an AI can estimate impact before changing a module.',
            'Mage module config and app/etc/modules XML declarations',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so module dependencies are unavailable.');
            return;
        }

        $modules = $this->collectModules();
        $requiredBy = $this->buildReverseDependencies($modules);
        $missing = $this->findMissingDependencies($modules);
        $cycles = $this->findSimpleCycles($modules);

        $customCount = 0;
        $withDependencies = 0;
        $shown = 0;

        foreach ($modules as $name => $module) {
            if (count($module['depends'])) {
                $withDependencies++;
            }

            if ($module['codePool'] !== 'core') {
                $customCount++;
            }

            if ($shown < 160) {
                $section->addItem('Module dependency', $name);
                $section->addItem('  Active', $module['active']);
                $section->addItem('  Code pool', $module['codePool']);
                $section->addItem('  Depends on', $this->formatList($module['depends']));
                $section->addItem('  Required by', isset($requiredBy[$name]) ? $this->formatList($requiredBy[$name]) : '[none]');
                $section->addItem('  Dependency weight', count($module['depends']) + (isset($requiredBy[$name]) ? count($requiredBy[$name]) : 0));
            }

            $shown++;
        }

        foreach ($missing as $row) {
            $section->addItem('Missing dependency', $row['module'] . ' depends on ' . $row['dependency']);
        }

        foreach ($cycles as $cycle) {
            $section->addItem('Dependency cycle', implode(' -> ', $cycle));
        }

        if (count($modules) > 160) {
            $section->addItem('Truncated', 'Only the first 160 module dependency rows are shown.');
        }

        $section->addItem('Summary / modules', count($modules));
        $section->addItem('Summary / custom modules', $customCount);
        $section->addItem('Summary / modules with dependencies', $withDependencies);
        $section->addItem('Summary / missing dependencies', count($missing));
        $section->addItem('Summary / simple dependency cycles', count($cycles));
    }

    protected function collectModules()
    {
        $result = array();
        $nodes = Mage::getConfig()->getNode('modules');

        if (!$nodes) {
            return $result;
        }

        foreach ($nodes->children() as $name => $node) {
            $depends = array();

            if ($node->depends) {
                foreach ($node->depends->children() as $dependencyName => $dependencyNode) {
                    $depends[] = (string)$dependencyName;
                }
            }

            sort($depends);

            $result[(string)$name] = array(
                'active' => ((string)$node->active === 'true') ? 'yes' : 'no',
                'codePool' => $node->codePool ? (string)$node->codePool : 'unknown',
                'depends' => $depends,
            );
        }

        ksort($result);
        return $result;
    }

    protected function buildReverseDependencies(array $modules)
    {
        $result = array();

        foreach ($modules as $moduleName => $module) {
            foreach ($module['depends'] as $dependency) {
                if (!isset($result[$dependency])) {
                    $result[$dependency] = array();
                }

                $result[$dependency][] = $moduleName;
            }
        }

        foreach ($result as $dependency => $modulesForDependency) {
            sort($modulesForDependency);
            $result[$dependency] = $modulesForDependency;
        }

        return $result;
    }

    protected function findMissingDependencies(array $modules)
    {
        $missing = array();

        foreach ($modules as $moduleName => $module) {
            foreach ($module['depends'] as $dependency) {
                if (!isset($modules[$dependency])) {
                    $missing[] = array('module' => $moduleName, 'dependency' => $dependency);
                }
            }
        }

        return $missing;
    }

    protected function findSimpleCycles(array $modules)
    {
        $cycles = array();

        foreach ($modules as $moduleName => $module) {
            foreach ($module['depends'] as $dependency) {
                if (isset($modules[$dependency]) && in_array($moduleName, $modules[$dependency]['depends'], true)) {
                    $key = implode('|', array_unique(array($moduleName, $dependency)));
                    $cycles[$key] = array($moduleName, $dependency, $moduleName);
                }
            }
        }

        return array_values($cycles);
    }

    protected function formatList(array $values)
    {
        if (!count($values)) {
            return '[none]';
        }

        if (count($values) > 30) {
            $values = array_slice($values, 0, 30);
            $values[] = '[truncated]';
        }

        return implode(', ', $values);
    }
}
