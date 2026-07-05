<?php

class ModuleContextExtractor extends AbstractAiContextExtractor
{
    public function extract(AiContext $context, array $data)
    {
        $this->extractModules($context, $data);
    }
    protected function extractModules(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'modules');

        if (!$section) {
            return;
        }

        $summaryKeys = array(
            'Summary / total modules',
            'Summary / active modules',
            'Summary / inactive modules',
            'Summary / code pool / core',
            'Summary / code pool / community',
            'Summary / code pool / local',
            'Summary / controllers',
            'Summary / models',
            'Summary / blocks',
            'Summary / helpers',
            'Summary / rewrites declared in config.xml',
            'Summary / observers declared in config.xml',
            'Summary / cron jobs declared in config.xml',
            'Summary / routers declared in config.xml',
        );

        foreach ($summaryKeys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('Module Architecture', str_replace('Summary / ', '', $key), $value);
            }
        }

        $this->extractModuleHighlights($context, $section);
    }

    protected function extractModuleHighlights(AiContext $context, array $section)
    {
        $modules = $this->parseModuleRows($section);

        $this->addModuleCodePoolStatusCounts($context, $modules);
        $this->addModuleActiveCustomModules($context, $modules);
        $this->addModuleInactiveCustomModules($context, $modules);
        $this->addModuleInactiveBehaviouralModules($context, $modules);
        $this->addModuleMissingPathHighlights($context, $modules);
        $this->addModuleWithRewrites($context, $modules);
        $this->addModuleWithObservers($context, $modules);
        $this->addModuleWithCronJobs($context, $modules);
        $this->addModuleWithRouters($context, $modules);
        $this->addModuleWithSetupScripts($context, $modules);
        $this->addModuleWithAdminConfig($context, $modules);
        $this->addModuleHighImpactModules($context, $modules);
    }

    protected function parseModuleRows(array $section)
    {
        $modules = array();

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === '' || strpos($key, 'Summary / ') === 0) {
                continue;
            }

            $module = $this->parseModuleValue($key, $value);

            if ($module !== null) {
                $modules[] = $module;
            }
        }

        return $modules;
    }

    protected function parseModuleValue($moduleName, $value)
    {
        if (strpos($value, 'active=') === false || strpos($value, 'codePool=') === false) {
            return null;
        }

        $module = array(
            'name' => $moduleName,
            'active' => '',
            'code_pool' => '',
            'version' => '',
            'depends' => '',
            'path_exists' => '',
            'config_xml' => '',
            'system_xml' => '',
            'adminhtml_xml' => '',
            'controllers' => 0,
            'models' => 0,
            'blocks' => 0,
            'helpers' => 0,
            'setup_scripts' => 0,
            'rewrites' => 0,
            'observers' => 0,
            'cron_jobs' => 0,
            'routers' => 0,
            'raw' => $value,
        );

        $parts = explode(';', $value);

        foreach ($parts as $part) {
            $part = trim($part);

            if (strpos($part, '=') === false) {
                continue;
            }

            list($field, $fieldValue) = explode('=', $part, 2);
            $field = trim($field);
            $fieldValue = trim($fieldValue);

            if ($field === 'active') {
                $module['active'] = $fieldValue;
            } elseif ($field === 'codePool') {
                $module['code_pool'] = $fieldValue;
            } elseif ($field === 'version') {
                $module['version'] = $fieldValue;
            } elseif ($field === 'depends') {
                $module['depends'] = $fieldValue;
            } elseif ($field === 'pathExists') {
                $module['path_exists'] = $fieldValue;
            } elseif ($field === 'config.xml') {
                $module['config_xml'] = $fieldValue;
            } elseif ($field === 'system.xml') {
                $module['system_xml'] = $fieldValue;
            } elseif ($field === 'adminhtml.xml') {
                $module['adminhtml_xml'] = $fieldValue;
            } elseif ($field === 'controllers') {
                $module['controllers'] = (int)$fieldValue;
            } elseif ($field === 'models') {
                $module['models'] = (int)$fieldValue;
            } elseif ($field === 'blocks') {
                $module['blocks'] = (int)$fieldValue;
            } elseif ($field === 'helpers') {
                $module['helpers'] = (int)$fieldValue;
            } elseif ($field === 'setupScripts') {
                $module['setup_scripts'] = (int)$fieldValue;
            } elseif ($field === 'rewrites') {
                $module['rewrites'] = (int)$fieldValue;
            } elseif ($field === 'observers') {
                $module['observers'] = (int)$fieldValue;
            } elseif ($field === 'cronJobs') {
                $module['cron_jobs'] = (int)$fieldValue;
            } elseif ($field === 'routers') {
                $module['routers'] = (int)$fieldValue;
            }
        }

        return $module;
    }

    protected function addModuleCodePoolStatusCounts(AiContext $context, array $modules)
    {
        $counts = array();

        foreach ($modules as $module) {
            $codePool = $module['code_pool'];

            if ($codePool === '') {
                $codePool = '[unknown]';
            }

            if (!isset($counts[$codePool])) {
                $counts[$codePool] = array(
                    'active' => 0,
                    'inactive' => 0,
                    'total' => 0,
                );
            }

            $counts[$codePool]['total']++;

            if ($module['active'] === 'yes') {
                $counts[$codePool]['active']++;
            } else {
                $counts[$codePool]['inactive']++;
            }
        }

        ksort($counts);

        foreach ($counts as $codePool => $count) {
            $context->addItem(
                'Module Code Pool Status Counts',
                $codePool,
                'active=' . $count['active']
                . '; inactive=' . $count['inactive']
                . '; total=' . $count['total']
            );
        }
    }

    protected function addModuleActiveCustomModules(AiContext $context, array $modules)
    {
        $count = 0;
        $limit = 50;

        foreach ($modules as $module) {
            if ($module['active'] !== 'yes' || !$this->isCustomModuleName($module['name'])) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Module Active Custom Modules',
                    $module['name'],
                    $this->formatModuleSummary($module)
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Module Active Custom Modules',
                'Truncated',
                'Only the first ' . $limit . ' active custom modules are shown in this short AI context. See full profile for all module data.'
            );
        }
    }

    protected function addModuleInactiveCustomModules(AiContext $context, array $modules)
    {
        $count = 0;
        $limit = 25;

        foreach ($modules as $module) {
            if ($module['active'] !== 'no' || !$this->isCustomModuleName($module['name'])) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Module Inactive Custom Modules',
                    $module['name'],
                    $this->formatModuleSummary($module)
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Module Inactive Custom Modules',
                'Truncated',
                'Only the first ' . $limit . ' inactive custom modules are shown in this short AI context. See full profile for all module data.'
            );
        }
    }

    protected function addModuleInactiveBehaviouralModules(AiContext $context, array $modules)
    {
        $rows = array();

        foreach ($modules as $module) {
            if ($module['active'] !== 'no') {
                continue;
            }

            if (
                $module['rewrites'] <= 0
                && $module['observers'] <= 0
                && $module['cron_jobs'] <= 0
                && $module['routers'] <= 0
                && $module['setup_scripts'] <= 0
                && $module['controllers'] <= 0
            ) {
                continue;
            }

            $rows[] = $module;
        }

        usort($rows, array($this, 'sortModulesByBehaviourWeight'));

        $count = 0;
        $limit = 25;

        foreach ($rows as $module) {
            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Module Inactive Behavioural Modules',
                    $module['name'],
                    $this->formatModuleSummary($module)
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Module Inactive Behavioural Modules',
                'Truncated',
                'Only the first ' . $limit . ' inactive modules with declared behaviour are shown in this short AI context. See full profile for all module data.'
            );
        }
    }

    protected function addModuleMissingPathHighlights(AiContext $context, array $modules)
    {
        $count = 0;
        $limit = 40;

        foreach ($modules as $module) {
            if ($module['path_exists'] !== 'no') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Module Missing Paths',
                    $module['name'],
                    $this->formatModuleSummary($module)
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Module Missing Paths',
                'Truncated',
                'Only the first ' . $limit . ' modules with missing filesystem paths are shown in this short AI context. See full profile for all module data.'
            );
        }
    }

    protected function addModuleWithRewrites(AiContext $context, array $modules)
    {
        $this->addModuleMetricHighlights(
            $context,
            $modules,
            'Module Rewrites Declared',
            'rewrites',
            30
        );
    }

    protected function addModuleWithObservers(AiContext $context, array $modules)
    {
        $this->addModuleMetricHighlights(
            $context,
            $modules,
            'Module Observers Declared',
            'observers',
            30
        );
    }

    protected function addModuleWithCronJobs(AiContext $context, array $modules)
    {
        $this->addModuleMetricHighlights(
            $context,
            $modules,
            'Module Cron Jobs Declared',
            'cron_jobs',
            30
        );
    }

    protected function addModuleWithRouters(AiContext $context, array $modules)
    {
        $this->addModuleMetricHighlights(
            $context,
            $modules,
            'Module Routers Declared',
            'routers',
            30
        );
    }

    protected function addModuleWithSetupScripts(AiContext $context, array $modules)
    {
        $this->addModuleMetricHighlights(
            $context,
            $modules,
            'Module Setup Scripts',
            'setup_scripts',
            30
        );
    }

    protected function addModuleWithAdminConfig(AiContext $context, array $modules)
    {
        $count = 0;
        $limit = 30;

        foreach ($modules as $module) {
            if ($module['system_xml'] !== 'yes' && $module['adminhtml_xml'] !== 'yes') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Module Admin Config Files',
                    $module['name'],
                    'active=' . $module['active']
                    . '; codePool=' . $module['code_pool']
                    . '; system.xml=' . $module['system_xml']
                    . '; adminhtml.xml=' . $module['adminhtml_xml']
                    . '; version=' . $module['version']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Module Admin Config Files',
                'Truncated',
                'Only the first ' . $limit . ' modules with admin/system config files are shown in this short AI context. See full profile for all module data.'
            );
        }
    }

    protected function addModuleMetricHighlights(AiContext $context, array $modules, $sectionTitle, $metricKey, $limit)
    {
        $rows = array();

        foreach ($modules as $module) {
            if ($module['active'] !== 'yes') {
                continue;
            }

            if (!isset($module[$metricKey]) || (int)$module[$metricKey] <= 0) {
                continue;
            }

            $rows[] = $module;
        }

        usort($rows, array($this, 'sortModulesByBehaviourWeight'));

        $count = 0;

        foreach ($rows as $module) {
            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    $sectionTitle,
                    $module['name'],
                    $metricKey . '=' . $module[$metricKey]
                    . '; active=' . $module['active']
                    . '; codePool=' . $module['code_pool']
                    . '; version=' . $module['version']
                    . '; depends=' . $module['depends']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                $sectionTitle,
                'Truncated',
                'Only the first ' . $limit . ' active modules are shown in this short AI context. See full profile for all module data.'
            );
        }
    }

    protected function addModuleHighImpactModules(AiContext $context, array $modules)
    {
        $rows = array();

        foreach ($modules as $module) {
            if (!$this->isHighImpactModule($module)) {
                continue;
            }

            $rows[] = $module;
        }

        usort($rows, array($this, 'sortModulesByBehaviourWeight'));

        $count = 0;
        $limit = 50;

        foreach ($rows as $module) {
            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Module High Impact Modules',
                    $module['name'],
                    $this->formatModuleSummary($module)
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Module High Impact Modules',
                'Truncated',
                'Only the first ' . $limit . ' high-impact modules are shown in this short AI context. See full profile for all module data.'
            );
        }
    }

    protected function isHighImpactModule(array $module)
    {
        if ($module['active'] !== 'yes') {
            return false;
        }

        if ($module['path_exists'] === 'no') {
            return true;
        }

        if (!$this->isCustomModuleName($module['name'])) {
            return false;
        }

        if (
            $module['rewrites'] > 0
            || $module['observers'] > 0
            || $module['cron_jobs'] > 0
            || $module['routers'] > 0
            || $module['setup_scripts'] > 0
            || $module['controllers'] > 0
        ) {
            return true;
        }

        $haystack = strtolower($module['name'] . ' ' . $module['depends']);

        $needles = array(
            'admin',
            'api',
            'automation',
            'catalog',
            'checkout',
            'customer',
            'dashboard',
            'export',
            'import',
            'index',
            'mail',
            'order',
            'payment',
            'product',
            'quickbooks',
            'quote',
            'report',
            'sales',
            'shipping',
            'supplier',
            'task',
        );

        foreach ($needles as $needle) {
            if (strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function sortModulesByBehaviourWeight(array $a, array $b)
    {
        $weightA = $this->getModuleBehaviourWeight($a);
        $weightB = $this->getModuleBehaviourWeight($b);

        if ($weightA === $weightB) {
            return strcmp($a['name'], $b['name']);
        }

        return ($weightA > $weightB) ? -1 : 1;
    }

    protected function getModuleBehaviourWeight(array $module)
    {
        return
            ($module['rewrites'] * 50)
            + ($module['observers'] * 20)
            + ($module['cron_jobs'] * 20)
            + ($module['routers'] * 10)
            + ($module['controllers'] * 8)
            + ($module['setup_scripts'] * 5)
            + ($module['models'] * 2)
            + $module['blocks']
            + $module['helpers'];
    }

    protected function formatModuleSummary(array $module)
    {
        return 'active=' . $module['active']
            . '; codePool=' . $module['code_pool']
            . '; version=' . $module['version']
            . '; depends=' . $module['depends']
            . '; pathExists=' . $module['path_exists']
            . '; controllers=' . $module['controllers']
            . '; models=' . $module['models']
            . '; blocks=' . $module['blocks']
            . '; helpers=' . $module['helpers']
            . '; setupScripts=' . $module['setup_scripts']
            . '; rewrites=' . $module['rewrites']
            . '; observers=' . $module['observers']
            . '; cronJobs=' . $module['cron_jobs']
            . '; routers=' . $module['routers'];
    }
    
}
