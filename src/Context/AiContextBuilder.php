<?php

class AiContextBuilder
{
    public function build(Report $report)
    {
        $context = new AiContext();
        $data = $report->toArray();

        $context->addItem('Overview', 'Tool', $this->metadata($data, 'Tool'));
        $context->addItem('Overview', 'Tool Version', $this->metadata($data, 'Tool Version'));
        $context->addItem('Overview', 'Generated', $this->metadata($data, 'Generated'));

        $this->extractEnvironment($context, $data);
        $this->extractPhp($context, $data);
        $this->extractMagento($context, $data);
        $this->extractStores($context, $data);
        $this->extractModules($context, $data);
        $this->extractThemes($context, $data);
        $this->extractRewrites($context, $data);
        $this->extractObservers($context, $data);
        $this->extractCron($context, $data);
        $this->extractIndexes($context, $data);
        $this->extractCache($context, $data);
        $this->extractDatabase($context, $data);
        $this->extractEav($context, $data);
        $this->extractRewriteMap($context, $data);
        $this->extractLayouts($context, $data);
        $this->extractRouters($context, $data);
        $this->extractControllers($context, $data);

        $this->addAiGuidance($context, $data);

        return $context;
    }

    protected function metadata(array $data, $key)
    {
        return isset($data['metadata'][$key]) ? $data['metadata'][$key] : '[unknown]';
    }

    protected function extractEnvironment(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'environment');

        if (!$section) {
            return;
        }

        $keys = array(
            'Project root',
            'Magento root',
            'Working directory',
            'Current user',
            'Default timezone',
        );

        foreach ($keys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('Environment', $key, $value);
            }
        }
    }

    protected function extractPhp(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'php');

        if (!$section) {
            return;
        }

        $keys = array(
            'PHP version',
            'PHP SAPI',
            'Memory limit',
            'Max execution time',
            'Upload max filesize',
            'Post max size',
            'Max input vars',
            'OPcache loaded',
            'Xdebug loaded',
            'APCu loaded',
            'Redis extension loaded',
            'ionCube loaded',
        );

        foreach ($keys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('PHP Runtime', $key, $value);
            }
        }

        $extensions = $this->item($section, 'Loaded extensions');

        if ($extensions !== '[unknown]') {
            $context->addItem('PHP Runtime', 'Loaded extensions', $this->summariseExtensions($extensions));
        }
    }

    protected function summariseExtensions($extensions)
    {
        $importantExtensions = array(
            'bcmath',
            'curl',
            'dom',
            'gd',
            'iconv',
            'intl',
            'json',
            'mbstring',
            'mcrypt',
            'mysqli',
            'mysql',
            'openssl',
            'pdo_mysql',
            'SimpleXML',
            'soap',
            'xml',
            'xmlreader',
            'xmlwriter',
            'xsl',
            'zip',
            'Zend OPcache',
            'xdebug',
        );

        $loaded = array();
        $extensionMap = array();
        $extensions = explode(',', $extensions);

        foreach ($extensions as $extension) {
            $extension = trim($extension);

            if ($extension === '') {
                continue;
            }

            $extensionMap[strtolower($extension)] = $extension;
        }

        foreach ($importantExtensions as $extension) {
            $key = strtolower($extension);

            if (isset($extensionMap[$key])) {
                $loaded[] = $extensionMap[$key];
            }
        }

        if (!count($loaded)) {
            return '[none of the common Magento-related extensions were detected]';
        }

        return implode(', ', $loaded);
    }

    protected function extractMagento(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'magento');

        if (!$section) {
            return;
        }

        $context->addItem('Magento', 'Version', $this->item($section, 'Mage::getVersion()'));
        $context->addItem('Magento', 'Edition', $this->item($section, 'Edition'));
        $context->addItem('Magento', 'Base Directory', $this->item($section, 'Mage base dir'));
        $context->addItem('Magento', 'Default Timezone', $this->item($section, 'Default timezone'));
        $context->addItem('Magento', 'Default Locale', $this->item($section, 'Default locale'));
        $context->addItem('Magento', 'Default Currency', $this->item($section, 'Default currency'));
        $context->addItem('Magento', 'Cache Backend', $this->item($section, 'Cache backend class'));
        $context->addItem('Magento', 'Session Save', $this->item($section, 'Session save'));
    }

    protected function extractStores(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'stores');

        if (!$section) {
            return;
        }

        $websites = 0;
        $storeGroups = 0;
        $storeViews = 0;
        $activeStores = 0;
        $inactiveStores = 0;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Website') {
                $websites++;
            } elseif ($key === 'Store group') {
                $storeGroups++;
            } elseif ($key === 'Store view') {
                $storeViews++;
            } elseif ($key === 'Active') {
                if ($value === 'yes') {
                    $activeStores++;
                } else {
                    $inactiveStores++;
                }
            }
        }

        $context->addItem('Store Architecture', 'Websites', $websites);
        $context->addItem('Store Architecture', 'Store Groups', $storeGroups);
        $context->addItem('Store Architecture', 'Store Views', $storeViews);
        $context->addItem('Store Architecture', 'Active Store Views', $activeStores);
        $context->addItem('Store Architecture', 'Inactive Store Views', $inactiveStores);
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
        $limit = 80;

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
        $limit = 80;

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
            80
        );
    }

    protected function addModuleWithObservers(AiContext $context, array $modules)
    {
        $this->addModuleMetricHighlights(
            $context,
            $modules,
            'Module Observers Declared',
            'observers',
            80
        );
    }

    protected function addModuleWithCronJobs(AiContext $context, array $modules)
    {
        $this->addModuleMetricHighlights(
            $context,
            $modules,
            'Module Cron Jobs Declared',
            'cron_jobs',
            80
        );
    }

    protected function addModuleWithRouters(AiContext $context, array $modules)
    {
        $this->addModuleMetricHighlights(
            $context,
            $modules,
            'Module Routers Declared',
            'routers',
            80
        );
    }

    protected function addModuleWithSetupScripts(AiContext $context, array $modules)
    {
        $this->addModuleMetricHighlights(
            $context,
            $modules,
            'Module Setup Scripts',
            'setup_scripts',
            80
        );
    }

    protected function addModuleWithAdminConfig(AiContext $context, array $modules)
    {
        $count = 0;
        $limit = 80;

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
                'Only the first ' . $limit . ' modules are shown in this short AI context. See full profile for all module data.'
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
        $limit = 100;

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
    
    protected function extractThemes(AiContext $context, array $data)
    {
        $themesSection = $this->findSection($data, 'themes');

        if (!$themesSection) {
            return;
        }

        $context->addItem(
            'Theme Architecture',
            'Design Packages Found',
            $this->item($themesSection, 'Summary / design packages found')
        );

        $context->addItem(
            'Theme Architecture',
            'Design Packages Used By Stores',
            $this->item($themesSection, 'Summary / design packages used by stores')
        );

        $themeHierarchySection = $this->findSection($data, 'theme_hierarchy');

        if (!$themeHierarchySection) {
            return;
        }

        $stores = $this->parseThemeHierarchyRows($themeHierarchySection);
        $packages = $this->parseThemePackageRows($themesSection);

        $this->addThemeResolutionSummary($context, $stores);
        $this->addThemeActiveStoreMap($context, $stores);
        $this->addThemeInactiveStoreMap($context, $stores);
        $this->addThemeFallbackChains($context, $stores);
        $this->addThemeResolverUsage($context, $stores);
        $this->addThemeSourceUsage($context, $stores);
        $this->addThemePackageUsage($context, $packages);
        $this->addThemeUnusedPackages($context, $packages);
        $this->addThemeXmlLayoutUpdates($context, $stores);
        $this->addThemeCssConventionHighlights($context, $stores);
    }
    
    protected function parseThemeHierarchyRows(array $section)
    {
        $stores = array();
        $currentStore = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Store') {
                if ($currentStore !== null) {
                    $stores[] = $currentStore;
                }

                $currentStore = array(
                    'store' => $value,
                    'active' => '',
                    'resolver' => '',
                    'source' => '',
                    'configured_package' => '',
                    'configured_theme' => '',
                    'effective_package' => '',
                    'effective_theme' => '',
                    'fallback_chain' => '',
                    'layout_theme_used' => '',
                    'template_theme_used' => '',
                    'skin_theme_used' => '',
                    'layout_fallbacks' => array(),
                    'template_fallbacks' => array(),
                    'skin_fallbacks' => array(),
                    'theme_xml' => array(),
                    'theme_xml_layout_updates' => array(),
                    'css_found' => array(),
                    'css_missing' => array(),
                );

                continue;
            }

            if ($currentStore === null) {
                continue;
            }

            if ($key === 'Active') {
                $currentStore['active'] = $value;
            } elseif ($key === 'Theme resolver') {
                $currentStore['resolver'] = $value;
            } elseif ($key === 'Theme source') {
                $currentStore['source'] = $value;
            } elseif ($key === 'Configured package') {
                $currentStore['configured_package'] = $value;
            } elseif ($key === 'Configured theme') {
                $currentStore['configured_theme'] = $value;
            } elseif ($key === 'Effective package') {
                $currentStore['effective_package'] = $value;
            } elseif ($key === 'Effective theme') {
                $currentStore['effective_theme'] = $value;
            } elseif ($key === 'Fallback chain') {
                $currentStore['fallback_chain'] = $value;
            } elseif ($key === 'Layout theme used') {
                $currentStore['layout_theme_used'] = $value;
            } elseif ($key === 'Template theme used') {
                $currentStore['template_theme_used'] = $value;
            } elseif ($key === 'Skin theme used') {
                $currentStore['skin_theme_used'] = $value;
            } elseif (strpos($key, 'Layout fallback / ') === 0) {
                $currentStore['layout_fallbacks'][] = $value;
            } elseif (strpos($key, 'Template fallback / ') === 0) {
                $currentStore['template_fallbacks'][] = $value;
            } elseif (strpos($key, 'Skin fallback / ') === 0) {
                $currentStore['skin_fallbacks'][] = $value;
            } elseif (strpos($key, 'theme.xml / ') === 0) {
                $currentStore['theme_xml'][] = str_replace('theme.xml / ', '', $key) . ': ' . $value;
            } elseif (strpos($key, 'theme.xml layout update / ') === 0) {
                $currentStore['theme_xml_layout_updates'][] = str_replace('theme.xml layout update / ', '', $key) . ': ' . $value;
            } elseif (strpos($key, 'theme.xml layout updates / ') === 0) {
                if ($value !== '[none]') {
                    $currentStore['theme_xml_layout_updates'][] = str_replace('theme.xml layout updates / ', '', $key) . ': ' . $value;
                }
            } elseif (strpos($key, 'CSS convention / ') === 0) {
                if ($value === 'found') {
                    $currentStore['css_found'][] = str_replace('CSS convention / ', '', $key);
                } elseif ($value === 'missing') {
                    $currentStore['css_missing'][] = str_replace('CSS convention / ', '', $key);
                }
            }
        }

        if ($currentStore !== null) {
            $stores[] = $currentStore;
        }

        return $stores;
    }

    protected function parseThemePackageRows(array $section)
    {
        $packages = array();
        $currentPackage = null;
        $currentTheme = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Package') {
                if ($currentTheme !== null && $currentPackage !== null) {
                    $currentPackage['themes'][] = $currentTheme;
                    $currentTheme = null;
                }

                if ($currentPackage !== null) {
                    $packages[] = $currentPackage;
                }

                $currentPackage = array(
                    'package' => $value,
                    'design_path' => '',
                    'skin_path' => '',
                    'used_by_store_config' => '',
                    'design_package_exists' => '',
                    'skin_package_exists' => '',
                    'themes' => array(),
                );

                continue;
            }

            if ($currentPackage === null) {
                continue;
            }

            if ($key === 'Theme') {
                if ($currentTheme !== null) {
                    $currentPackage['themes'][] = $currentTheme;
                }

                $currentTheme = array(
                    'theme' => $value,
                    'design_path' => '',
                    'skin_path' => '',
                    'layout_xml_files' => '',
                    'template_phtml_files' => '',
                    'locale_csv_files' => '',
                    'theme_xml_files' => '',
                    'css_files' => '',
                    'js_files' => '',
                    'image_files' => '',
                    'design_size' => '',
                    'skin_size' => '',
                );

                continue;
            }

            if ($currentTheme !== null) {
                if ($key === 'Design path') {
                    $currentTheme['design_path'] = $value;
                } elseif ($key === 'Skin path') {
                    $currentTheme['skin_path'] = $value;
                } elseif ($key === 'Layout XML files') {
                    $currentTheme['layout_xml_files'] = $value;
                } elseif ($key === 'Template PHTML files') {
                    $currentTheme['template_phtml_files'] = $value;
                } elseif ($key === 'Locale CSV files') {
                    $currentTheme['locale_csv_files'] = $value;
                } elseif ($key === 'Theme XML files') {
                    $currentTheme['theme_xml_files'] = $value;
                } elseif ($key === 'CSS files') {
                    $currentTheme['css_files'] = $value;
                } elseif ($key === 'JS files') {
                    $currentTheme['js_files'] = $value;
                } elseif ($key === 'Image files') {
                    $currentTheme['image_files'] = $value;
                } elseif ($key === 'Design size') {
                    $currentTheme['design_size'] = $value;
                } elseif ($key === 'Skin size') {
                    $currentTheme['skin_size'] = $value;
                }

                continue;
            }

            if ($key === 'Design path') {
                $currentPackage['design_path'] = $value;
            } elseif ($key === 'Skin path') {
                $currentPackage['skin_path'] = $value;
            } elseif ($key === 'Used by store config') {
                $currentPackage['used_by_store_config'] = $value;
            } elseif ($key === 'Design package exists') {
                $currentPackage['design_package_exists'] = $value;
            } elseif ($key === 'Skin package exists') {
                $currentPackage['skin_package_exists'] = $value;
            }
        }

        if ($currentTheme !== null && $currentPackage !== null) {
            $currentPackage['themes'][] = $currentTheme;
        }

        if ($currentPackage !== null) {
            $packages[] = $currentPackage;
        }

        return $packages;
    }

    protected function addThemeResolutionSummary(AiContext $context, array $stores)
    {
        foreach ($stores as $store) {
            $context->addItem('Theme Resolution', $store['store'] . ' / Resolver', $store['resolver']);
            $context->addItem('Theme Resolution', $store['store'] . ' / Source', $store['source']);
            $context->addItem('Theme Resolution', $store['store'] . ' / Configured Package', $store['configured_package']);
            $context->addItem('Theme Resolution', $store['store'] . ' / Configured Theme', $store['configured_theme']);
            $context->addItem('Theme Resolution', $store['store'] . ' / Effective Theme', $store['effective_theme']);
        }
    }

    protected function addThemeActiveStoreMap(AiContext $context, array $stores)
    {
        foreach ($stores as $store) {
            if ($store['active'] !== 'yes') {
                continue;
            }

            $context->addItem(
                'Theme Active Store Map',
                $store['store'],
                'effective=' . $store['effective_theme']
                . '; package=' . $store['effective_package']
                . '; source=' . $store['source']
                . '; configuredPackage=' . $store['configured_package']
                . '; configuredTheme=' . $store['configured_theme']
                . '; resolver=' . $store['resolver']
            );
        }
    }

    protected function addThemeInactiveStoreMap(AiContext $context, array $stores)
    {
        $count = 0;
        $limit = 30;

        foreach ($stores as $store) {
            if ($store['active'] !== 'no') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Theme Inactive Store Map',
                    $store['store'],
                    'effective=' . $store['effective_theme']
                    . '; package=' . $store['effective_package']
                    . '; source=' . $store['source']
                    . '; configuredPackage=' . $store['configured_package']
                    . '; configuredTheme=' . $store['configured_theme']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Theme Inactive Store Map',
                'Truncated',
                'Only the first ' . $limit . ' inactive store theme mappings are shown in this short AI context. See full profile for all theme hierarchy data.'
            );
        }
    }

    protected function addThemeFallbackChains(AiContext $context, array $stores)
    {
        foreach ($stores as $store) {
            if ($store['active'] !== 'yes') {
                continue;
            }

            $context->addItem(
                'Theme Active Store Fallback Chains',
                $store['store'],
                'effective=' . $store['effective_theme']
                . '; fallback=' . $store['fallback_chain']
                . '; layoutTheme=' . $store['layout_theme_used']
                . '; templateTheme=' . $store['template_theme_used']
                . '; skinTheme=' . $store['skin_theme_used']
            );
        }
    }

    protected function addThemeResolverUsage(AiContext $context, array $stores)
    {
        $usage = array();

        foreach ($stores as $store) {
            $resolver = $store['resolver'];

            if ($resolver === '') {
                $resolver = '[unknown]';
            }

            if (!isset($usage[$resolver])) {
                $usage[$resolver] = array(
                    'active' => 0,
                    'inactive' => 0,
                    'total' => 0,
                );
            }

            $usage[$resolver]['total']++;

            if ($store['active'] === 'yes') {
                $usage[$resolver]['active']++;
            } else {
                $usage[$resolver]['inactive']++;
            }
        }

        ksort($usage);

        foreach ($usage as $resolver => $counts) {
            $context->addItem(
                'Theme Resolver Usage',
                $resolver,
                'activeStores=' . $counts['active']
                . '; inactiveStores=' . $counts['inactive']
                . '; totalStores=' . $counts['total']
            );
        }
    }

    protected function addThemeSourceUsage(AiContext $context, array $stores)
    {
        $usage = array();

        foreach ($stores as $store) {
            $source = $store['source'];

            if ($source === '') {
                $source = '[unknown]';
            }

            if (!isset($usage[$source])) {
                $usage[$source] = array(
                    'active' => 0,
                    'inactive' => 0,
                    'total' => 0,
                );
            }

            $usage[$source]['total']++;

            if ($store['active'] === 'yes') {
                $usage[$source]['active']++;
            } else {
                $usage[$source]['inactive']++;
            }
        }

        ksort($usage);

        foreach ($usage as $source => $counts) {
            $context->addItem(
                'Theme Source Usage',
                $source,
                'activeStores=' . $counts['active']
                . '; inactiveStores=' . $counts['inactive']
                . '; totalStores=' . $counts['total']
            );
        }
    }

    protected function addThemePackageUsage(AiContext $context, array $packages)
    {
        foreach ($packages as $package) {
            $context->addItem(
                'Theme Package Usage',
                $package['package'],
                'usedByStoreConfig=' . $package['used_by_store_config']
                . '; designExists=' . $package['design_package_exists']
                . '; skinExists=' . $package['skin_package_exists']
                . '; themes=' . count($package['themes'])
            );
        }
    }

    protected function addThemeUnusedPackages(AiContext $context, array $packages)
    {
        $count = 0;
        $limit = 40;

        foreach ($packages as $package) {
            if ($package['used_by_store_config'] !== 'no') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Theme Packages Not Used By Store Config',
                    $package['package'],
                    'designExists=' . $package['design_package_exists']
                    . '; skinExists=' . $package['skin_package_exists']
                    . '; themes=' . count($package['themes'])
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Theme Packages Not Used By Store Config',
                'Truncated',
                'Only the first ' . $limit . ' unused design packages are shown in this short AI context. See full profile for all theme data.'
            );
        }
    }

    protected function addThemeXmlLayoutUpdates(AiContext $context, array $stores)
    {
        $seen = array();
        $count = 0;
        $limit = 60;

        foreach ($stores as $store) {
            if ($store['active'] !== 'yes') {
                continue;
            }

            foreach ($store['theme_xml_layout_updates'] as $layoutUpdate) {
                if (isset($seen[$layoutUpdate])) {
                    continue;
                }

                $seen[$layoutUpdate] = true;
                $count++;

                if ($count <= $limit) {
                    $context->addItem(
                        'Theme XML Layout Updates',
                        $store['store'],
                        $layoutUpdate
                    );
                }
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Theme XML Layout Updates',
                'Truncated',
                'Only the first ' . $limit . ' active-store theme.xml layout updates are shown in this short AI context. See full profile for all theme hierarchy data.'
            );
        }
    }

    protected function addThemeCssConventionHighlights(AiContext $context, array $stores)
    {
        foreach ($stores as $store) {
            if ($store['active'] !== 'yes') {
                continue;
            }

            $found = $this->summariseThemeConventionFiles($store['css_found'], 12);
            $missing = $this->summariseThemeConventionFiles($store['css_missing'], 12);

            $context->addItem(
                'Theme CSS Convention Files',
                $store['store'],
                'found=' . $found . '; missing=' . $missing
            );
        }
    }

    protected function summariseThemeConventionFiles(array $files, $limit)
    {
        if (!count($files)) {
            return '[none]';
        }

        $files = array_values(array_unique($files));

        if (count($files) <= $limit) {
            return implode(', ', $files);
        }

        return implode(', ', array_slice($files, 0, $limit)) . ', ...';
    }

    protected function extractRewrites(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'rewrites');

        if (!$section) {
            return;
        }

        $summaryKeys = array(
            'Summary / aliases with rewrites',
            'Summary / total rewrite declarations',
            'Summary / model rewrite declarations',
            'Summary / block rewrite declarations',
            'Summary / helper rewrite declarations',
            'Summary / aliases with conflicts',
            'Summary / missing declared class files',
            'Summary / missing winning class files',
        );

        foreach ($summaryKeys as $key) {
            $value = $this->item($section, $key);
            if ($value !== '[unknown]') {
                $context->addItem('Rewrite Architecture', str_replace('Summary / ', '', $key), $value);
            }
        }

        $this->extractRewriteHighlights($context, $section);
    }

    protected function extractRewriteHighlights(AiContext $context, array $section)
    {
        $rewrites = array();
        $currentRewrite = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Rewrite') {
                if ($currentRewrite !== null) {
                    $rewrites[] = $currentRewrite;
                }

                $currentRewrite = array(
                    'rewrite' => $value,
                    'type' => '',
                    'alias' => '',
                    'original_class' => '',
                    'winning_class' => '',
                    'winning_class_file_exists' => '',
                    'declaration_count' => '',
                    'conflict' => '',
                    'declarations' => array(),
                );

                continue;
            }

            if ($currentRewrite === null) {
                continue;
            }

            if ($key === 'Type') {
                $currentRewrite['type'] = $value;
            } elseif ($key === 'Alias') {
                $currentRewrite['alias'] = $value;
            } elseif ($key === 'Original class') {
                $currentRewrite['original_class'] = $value;
            } elseif ($key === 'Winning class') {
                $currentRewrite['winning_class'] = $value;
            } elseif ($key === 'Winning class file exists') {
                $currentRewrite['winning_class_file_exists'] = $value;
            } elseif ($key === 'Declaration count') {
                $currentRewrite['declaration_count'] = $value;
            } elseif ($key === 'Conflict') {
                $currentRewrite['conflict'] = $value;
            } elseif (strpos($key, 'Declaration / ') === 0) {
                $currentRewrite['declarations'][] = $value;
            }
        }

        if ($currentRewrite !== null) {
            $rewrites[] = $currentRewrite;
        }

        $this->addRewriteConflictHighlights($context, $rewrites);
        $this->addRewriteMissingClassHighlights($context, $rewrites);
        $this->addRewriteCustomWinningHighlights($context, $rewrites);
    }

    protected function addRewriteConflictHighlights(AiContext $context, array $rewrites)
    {
        $count = 0;
        $limit = 20;

        foreach ($rewrites as $rewrite) {
            if ($rewrite['conflict'] !== 'yes') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Rewrite Conflicts',
                    $rewrite['rewrite'],
                    'alias=' . $rewrite['alias']
                    . '; winning=' . $rewrite['winning_class']
                    . '; declarations=' . implode(' | ', $rewrite['declarations'])
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Rewrite Conflicts',
                'Truncated',
                'Only the first ' . $limit . ' rewrite conflicts are shown in this short AI context. See full profile for all rewrite data.'
            );
        }
    }

    protected function addRewriteMissingClassHighlights(AiContext $context, array $rewrites)
    {
        $missingDeclaredCount = 0;
        $missingWinningCount = 0;
        $limit = 20;

        foreach ($rewrites as $rewrite) {
            if ($rewrite['winning_class_file_exists'] === 'no') {
                $missingWinningCount++;

                if ($missingWinningCount <= $limit) {
                    $context->addItem(
                        'Rewrite Missing Winning Classes',
                        $rewrite['rewrite'],
                        'alias=' . $rewrite['alias']
                        . '; winning=' . $rewrite['winning_class']
                        . '; original=' . $rewrite['original_class']
                    );
                }
            }

            foreach ($rewrite['declarations'] as $declaration) {
                if (strpos($declaration, 'classFileExists=no') === false) {
                    continue;
                }

                $missingDeclaredCount++;

                if ($missingDeclaredCount <= $limit) {
                    $context->addItem(
                        'Rewrite Missing Declared Classes',
                        $rewrite['rewrite'],
                        $declaration
                    );
                }
            }
        }

        if ($missingWinningCount > $limit) {
            $context->addItem(
                'Rewrite Missing Winning Classes',
                'Truncated',
                'Only the first ' . $limit . ' missing winning rewrite classes are shown in this short AI context. See full profile for all rewrite data.'
            );
        }

        if ($missingDeclaredCount > $limit) {
            $context->addItem(
                'Rewrite Missing Declared Classes',
                'Truncated',
                'Only the first ' . $limit . ' missing declared rewrite classes are shown in this short AI context. See full profile for all rewrite data.'
            );
        }
    }

    protected function addRewriteCustomWinningHighlights(AiContext $context, array $rewrites)
    {
        $count = 0;
        $limit = 40;

        foreach ($rewrites as $rewrite) {
            if (!$this->isCustomRewriteClass($rewrite['winning_class'])) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Rewrite Custom Winning Classes',
                    $rewrite['rewrite'],
                    'alias=' . $rewrite['alias']
                    . '; original=' . $rewrite['original_class']
                    . '; winning=' . $rewrite['winning_class']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Rewrite Custom Winning Classes',
                'Truncated',
                'Only the first ' . $limit . ' custom winning rewrite classes are shown in this short AI context. See full profile for all rewrite data.'
            );
        }
    }

    protected function isCustomRewriteClass($class)
    {
        if ($class === '' || $class === '[unknown]') {
            return false;
        }

        $corePrefixes = array(
            'Mage_',
            'Enterprise_',
            'Varien_',
            'Zend_',
        );

        foreach ($corePrefixes as $prefix) {
            if (strpos($class, $prefix) === 0) {
                return false;
            }
        }

        return true;
    }
    
    protected function extractCron(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'cron');

        if (!$section) {
            return;
        }

        $summaryKeys = array(
            'Summary / total cron jobs',
            'Summary / jobs with inline schedule',
            'Summary / jobs with config schedule path',
            'Summary / jobs without schedule',
            'Summary / custom cron jobs',
            'Summary / missing cron class files',
        );

        foreach ($summaryKeys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem(
                    'Cron Architecture',
                    str_replace('Summary / ', '', $key),
                    $value
                );
            }
        }

        $this->extractCronHighlights($context, $section);
    }
    
    protected function extractCronHighlights(AiContext $context, array $section)
    {
        $cronJobs = $this->parseCronRows($section);

        $this->addCronCustomHighlights($context, $cronJobs);
        $this->addCronWithoutScheduleHighlights($context, $cronJobs);
        $this->addCronConfigPathHighlights($context, $cronJobs);
        $this->addCronMissingClassHighlights($context, $cronJobs);
        $this->addCronHighImpactHighlights($context, $cronJobs);
    }

    protected function parseCronRows(array $section)
    {
        $cronJobs = array();
        $currentJob = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Cron job') {
                if ($currentJob !== null) {
                    $cronJobs[] = $currentJob;
                }

                $currentJob = array(
                    'job_code' => $value,
                    'schedule' => '',
                    'config_schedule_path' => '',
                    'model' => '',
                    'model_alias' => '',
                    'method' => '',
                    'resolved_class' => '',
                    'custom' => '',
                    'class_file_exists' => '',
                    'class_file' => '',
                );

                continue;
            }

            if ($currentJob === null) {
                continue;
            }

            if ($key === 'Schedule') {
                $currentJob['schedule'] = $value;
            } elseif ($key === 'Config schedule path') {
                $currentJob['config_schedule_path'] = $value;
            } elseif ($key === 'Model') {
                $currentJob['model'] = $value;
            } elseif ($key === 'Model alias') {
                $currentJob['model_alias'] = $value;
            } elseif ($key === 'Method') {
                $currentJob['method'] = $value;
            } elseif ($key === 'Resolved class') {
                $currentJob['resolved_class'] = $value;
            } elseif ($key === 'Custom') {
                $currentJob['custom'] = $value;
            } elseif ($key === 'Class file exists') {
                $currentJob['class_file_exists'] = $value;
            } elseif ($key === 'Class file') {
                $currentJob['class_file'] = $value;
            }
        }

        if ($currentJob !== null) {
            $cronJobs[] = $currentJob;
        }

        return $cronJobs;
    }

    protected function addCronCustomHighlights(AiContext $context, array $cronJobs)
    {
        $count = 0;
        $limit = 40;

        foreach ($cronJobs as $cronJob) {
            if ($cronJob['custom'] !== 'yes') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Cron Custom Jobs',
                    $cronJob['job_code'],
                    'schedule=' . $cronJob['schedule']
                    . '; configPath=' . $cronJob['config_schedule_path']
                    . '; model=' . $cronJob['model']
                    . '; resolved=' . $cronJob['resolved_class']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Cron Custom Jobs',
                'Truncated',
                'Only the first ' . $limit . ' custom cron jobs are shown in this short AI context. See full profile for all cron data.'
            );
        }
    }

    protected function addCronWithoutScheduleHighlights(AiContext $context, array $cronJobs)
    {
        $count = 0;
        $limit = 40;

        foreach ($cronJobs as $cronJob) {
            if ($cronJob['schedule'] !== '[none]' || $cronJob['config_schedule_path'] !== '[none]') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Cron Jobs Without Schedule',
                    $cronJob['job_code'],
                    'model=' . $cronJob['model']
                    . '; resolved=' . $cronJob['resolved_class']
                    . '; method=' . $cronJob['method']
                    . '; custom=' . $cronJob['custom']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Cron Jobs Without Schedule',
                'Truncated',
                'Only the first ' . $limit . ' cron jobs without schedules are shown in this short AI context. See full profile for all cron data.'
            );
        }
    }

    protected function addCronConfigPathHighlights(AiContext $context, array $cronJobs)
    {
        $count = 0;
        $limit = 40;

        foreach ($cronJobs as $cronJob) {
            if ($cronJob['config_schedule_path'] === '[none]' || $cronJob['config_schedule_path'] === '') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Cron Config Schedule Paths',
                    $cronJob['job_code'],
                    'configPath=' . $cronJob['config_schedule_path']
                    . '; model=' . $cronJob['model']
                    . '; resolved=' . $cronJob['resolved_class']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Cron Config Schedule Paths',
                'Truncated',
                'Only the first ' . $limit . ' cron jobs using config schedule paths are shown in this short AI context. See full profile for all cron data.'
            );
        }
    }

    protected function addCronMissingClassHighlights(AiContext $context, array $cronJobs)
    {
        $count = 0;
        $limit = 40;

        foreach ($cronJobs as $cronJob) {
            if ($cronJob['class_file_exists'] !== 'no') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Cron Missing Classes',
                    $cronJob['job_code'],
                    'model=' . $cronJob['model']
                    . '; modelAlias=' . $cronJob['model_alias']
                    . '; resolved=' . $cronJob['resolved_class']
                    . '; method=' . $cronJob['method']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Cron Missing Classes',
                'Truncated',
                'Only the first ' . $limit . ' cron jobs with missing class files are shown in this short AI context. See full profile for all cron data.'
            );
        }
    }

    protected function addCronHighImpactHighlights(AiContext $context, array $cronJobs)
    {
        $count = 0;
        $limit = 60;

        foreach ($cronJobs as $cronJob) {
            if (!$this->isHighImpactCronJob($cronJob)) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Cron High Impact Jobs',
                    $cronJob['job_code'],
                    'schedule=' . $cronJob['schedule']
                    . '; configPath=' . $cronJob['config_schedule_path']
                    . '; model=' . $cronJob['model']
                    . '; resolved=' . $cronJob['resolved_class']
                    . '; method=' . $cronJob['method']
                    . '; custom=' . $cronJob['custom']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Cron High Impact Jobs',
                'Truncated',
                'Only the first ' . $limit . ' high-impact cron jobs are shown in this short AI context. See full profile for all cron data.'
            );
        }
    }

    protected function isHighImpactCronJob(array $cronJob)
    {
        if ($cronJob['custom'] === 'yes') {
            return true;
        }

        if ($cronJob['class_file_exists'] === 'no') {
            return true;
        }

        if ($cronJob['schedule'] === '[none]' && $cronJob['config_schedule_path'] === '[none]') {
            return true;
        }

        $haystack = strtolower(
            $cronJob['job_code']
            . ' ' . $cronJob['model']
            . ' ' . $cronJob['resolved_class']
            . ' ' . $cronJob['method']
        );

        $needles = array(
            'catalog',
            'customer',
            'email',
            'export',
            'import',
            'index',
            'mail',
            'm2epro',
            'newsletter',
            'order',
            'price',
            'product',
            'quickbooks',
            'queue',
            'report',
            'sales',
            'sitemap',
            'stock',
            'sync',
        );

        foreach ($needles as $needle) {
            if (strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
    
    protected function extractIndexes(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'indexes');

        if (!$section) {
            return;
        }

        $summaryKeys = array(
            'Summary / total indexers',
            'Summary / require reindex',
            'Summary / processing',
            'Summary / manual mode',
            'Summary / realtime mode',
        );

        foreach ($summaryKeys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('Index Architecture', str_replace('Summary / ', '', $key), $value);
            }
        }

        $this->extractIndexHighlights($context, $section);
    }

    protected function extractCache(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'cache');

        if (!$section) {
            return;
        }

        $keys = array(
            'Backend class',
            'Cache prefix',
            'Session save',
            'Summary / cache types',
            'Summary / enabled cache types',
            'Summary / disabled cache types',
        );

        foreach ($keys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('Cache Architecture', str_replace('Summary / ', '', $key), $value);
            }
        }

        $this->extractCacheHighlights($context, $section);
    }

    protected function extractIndexHighlights(AiContext $context, array $section)
    {
        $indexers = $this->parseIndexRows($section);

        $this->addIndexStatusCounts($context, $indexers);
        $this->addIndexModeCounts($context, $indexers);
        $this->addIndexManualIndexers($context, $indexers);
        $this->addIndexRequireReindexHighlights($context, $indexers);
        $this->addIndexProcessingHighlights($context, $indexers);
        $this->addIndexStaleDateHighlights($context, $indexers);
        $this->addIndexHighImpactHighlights($context, $indexers);
    }

    protected function parseIndexRows(array $section)
    {
        $indexers = array();
        $currentIndexer = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Indexer') {
                if ($currentIndexer !== null) {
                    $indexers[] = $currentIndexer;
                }

                $currentIndexer = array(
                    'code' => $value,
                    'name' => '',
                    'status' => '',
                    'mode' => '',
                    'last_started' => '',
                    'last_ended' => '',
                );

                continue;
            }

            if ($currentIndexer === null) {
                continue;
            }

            if ($key === 'Name') {
                $currentIndexer['name'] = $value;
            } elseif ($key === 'Status') {
                $currentIndexer['status'] = $value;
            } elseif ($key === 'Mode') {
                $currentIndexer['mode'] = $value;
            } elseif ($key === 'Last started') {
                $currentIndexer['last_started'] = $value;
            } elseif ($key === 'Last ended') {
                $currentIndexer['last_ended'] = $value;
            }
        }

        if ($currentIndexer !== null) {
            $indexers[] = $currentIndexer;
        }

        return $indexers;
    }

    protected function addIndexStatusCounts(AiContext $context, array $indexers)
    {
        $counts = array();

        foreach ($indexers as $indexer) {
            $status = $indexer['status'];

            if ($status === '') {
                $status = '[unknown]';
            }

            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }

            $counts[$status]++;
        }

        ksort($counts);

        foreach ($counts as $status => $count) {
            $context->addItem('Index Status Counts', $status, $count);
        }
    }

    protected function addIndexModeCounts(AiContext $context, array $indexers)
    {
        $counts = array();

        foreach ($indexers as $indexer) {
            $mode = $indexer['mode'];

            if ($mode === '') {
                $mode = '[unknown]';
            }

            if (!isset($counts[$mode])) {
                $counts[$mode] = 0;
            }

            $counts[$mode]++;
        }

        ksort($counts);

        foreach ($counts as $mode => $count) {
            $context->addItem('Index Mode Counts', $mode, $count);
        }
    }

    protected function addIndexManualIndexers(AiContext $context, array $indexers)
    {
        $count = 0;
        $limit = 40;

        foreach ($indexers as $indexer) {
            if ($indexer['mode'] !== 'manual') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Index Manual Indexers',
                    $indexer['code'],
                    'name=' . $indexer['name']
                    . '; status=' . $indexer['status']
                    . '; lastStarted=' . $indexer['last_started']
                    . '; lastEnded=' . $indexer['last_ended']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Index Manual Indexers',
                'Truncated',
                'Only the first ' . $limit . ' manual indexers are shown in this short AI context. See full profile for all index data.'
            );
        }
    }

    protected function addIndexRequireReindexHighlights(AiContext $context, array $indexers)
    {
        $count = 0;
        $limit = 40;

        foreach ($indexers as $indexer) {
            if ($indexer['status'] !== 'require_reindex') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Indexers Requiring Reindex',
                    $indexer['code'],
                    'name=' . $indexer['name']
                    . '; mode=' . $indexer['mode']
                    . '; lastStarted=' . $indexer['last_started']
                    . '; lastEnded=' . $indexer['last_ended']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Indexers Requiring Reindex',
                'Truncated',
                'Only the first ' . $limit . ' indexers requiring reindex are shown in this short AI context. See full profile for all index data.'
            );
        }
    }

    protected function addIndexProcessingHighlights(AiContext $context, array $indexers)
    {
        $count = 0;
        $limit = 40;

        foreach ($indexers as $indexer) {
            if ($indexer['status'] !== 'processing') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Indexers Processing',
                    $indexer['code'],
                    'name=' . $indexer['name']
                    . '; mode=' . $indexer['mode']
                    . '; lastStarted=' . $indexer['last_started']
                    . '; lastEnded=' . $indexer['last_ended']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Indexers Processing',
                'Truncated',
                'Only the first ' . $limit . ' processing indexers are shown in this short AI context. See full profile for all index data.'
            );
        }
    }

    protected function addIndexStaleDateHighlights(AiContext $context, array $indexers)
    {
        $count = 0;
        $limit = 40;

        foreach ($indexers as $indexer) {
            if (!$this->isPotentiallyStaleIndexer($indexer)) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Index Potentially Stale Dates',
                    $indexer['code'],
                    'name=' . $indexer['name']
                    . '; status=' . $indexer['status']
                    . '; mode=' . $indexer['mode']
                    . '; lastStarted=' . $indexer['last_started']
                    . '; lastEnded=' . $indexer['last_ended']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Index Potentially Stale Dates',
                'Truncated',
                'Only the first ' . $limit . ' potentially stale indexers are shown in this short AI context. See full profile for all index data.'
            );
        }
    }

    protected function addIndexHighImpactHighlights(AiContext $context, array $indexers)
    {
        $count = 0;
        $limit = 60;

        foreach ($indexers as $indexer) {
            if (!$this->isHighImpactIndexer($indexer)) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Index High Impact Indexers',
                    $indexer['code'],
                    'name=' . $indexer['name']
                    . '; status=' . $indexer['status']
                    . '; mode=' . $indexer['mode']
                    . '; lastStarted=' . $indexer['last_started']
                    . '; lastEnded=' . $indexer['last_ended']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Index High Impact Indexers',
                'Truncated',
                'Only the first ' . $limit . ' high-impact indexers are shown in this short AI context. See full profile for all index data.'
            );
        }
    }

    protected function isPotentiallyStaleIndexer(array $indexer)
    {
        if ($indexer['last_started'] === '' || $indexer['last_started'] === '[never]') {
            return true;
        }

        if (strpos($indexer['last_started'], '2016-') === 0) {
            return true;
        }

        if (strpos($indexer['last_started'], '2017-') === 0) {
            return true;
        }

        if (strpos($indexer['last_started'], '2018-') === 0) {
            return true;
        }

        if (strpos($indexer['last_started'], '2019-') === 0) {
            return true;
        }

        if (strpos($indexer['last_started'], '2020-') === 0) {
            return true;
        }

        return false;
    }

    protected function isHighImpactIndexer(array $indexer)
    {
        if ($indexer['status'] === 'require_reindex' || $indexer['status'] === 'processing') {
            return true;
        }

        if ($this->isPotentiallyStaleIndexer($indexer)) {
            return true;
        }

        $needles = array(
            'catalog',
            'category',
            'inventory',
            'price',
            'product',
            'search',
            'stock',
            'url',
        );

        $haystack = strtolower($indexer['code'] . ' ' . $indexer['name']);

        foreach ($needles as $needle) {
            if (strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function extractCacheHighlights(AiContext $context, array $section)
    {
        $cacheTypes = $this->parseCacheRows($section);

        $this->addCacheEnabledTypes($context, $cacheTypes);
        $this->addCacheDisabledTypes($context, $cacheTypes);
        $this->addCacheOperationalRisks($context, $section, $cacheTypes);
    }

    protected function parseCacheRows(array $section)
    {
        $cacheTypes = array();
        $currentType = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Cache type') {
                if ($currentType !== null) {
                    $cacheTypes[] = $currentType;
                }

                $currentType = array(
                    'type' => $value,
                    'status' => '',
                );

                continue;
            }

            if ($currentType === null) {
                continue;
            }

            if ($key === 'Status') {
                $currentType['status'] = $value;
            }
        }

        if ($currentType !== null) {
            $cacheTypes[] = $currentType;
        }

        return $cacheTypes;
    }

    protected function addCacheEnabledTypes(AiContext $context, array $cacheTypes)
    {
        $count = 0;

        foreach ($cacheTypes as $cacheType) {
            if ($cacheType['status'] !== 'enabled') {
                continue;
            }

            $count++;

            $context->addItem(
                'Cache Enabled Types',
                $cacheType['type'],
                'status=' . $cacheType['status']
            );
        }

        if ($count === 0) {
            $context->addItem('Cache Enabled Types', 'none', 'No enabled cache types were reported.');
        }
    }

    protected function addCacheDisabledTypes(AiContext $context, array $cacheTypes)
    {
        $count = 0;

        foreach ($cacheTypes as $cacheType) {
            if ($cacheType['status'] !== 'disabled') {
                continue;
            }

            $count++;

            $context->addItem(
                'Cache Disabled Types',
                $cacheType['type'],
                'status=' . $cacheType['status']
            );
        }

        if ($count === 0) {
            $context->addItem('Cache Disabled Types', 'none', 'No disabled cache types were reported.');
        }
    }

    protected function addCacheOperationalRisks(AiContext $context, array $section, array $cacheTypes)
    {
        $enabled = $this->item($section, 'Summary / enabled cache types');
        $disabled = $this->item($section, 'Summary / disabled cache types');
        $total = $this->item($section, 'Summary / cache types');
        $backend = $this->item($section, 'Backend class');
        $prefix = $this->item($section, 'Cache prefix');
        $sessionSave = $this->item($section, 'Session save');

        if ($total !== '[unknown]' && $enabled === '0') {
            $context->addItem(
                'Cache Operational Risks',
                'all cache types disabled',
                'enabled=' . $enabled . '; disabled=' . $disabled . '; total=' . $total
            );
        }

        if ($backend === 'Zend_Cache_Backend_File') {
            $context->addItem(
                'Cache Operational Risks',
                'file cache backend',
                'backend=' . $backend . '; prefix=' . $prefix
            );
        }

        if ($prefix === '[none]' || $prefix === '') {
            $context->addItem(
                'Cache Operational Risks',
                'cache prefix missing',
                'prefix=' . $prefix
            );
        }

        if ($sessionSave === '[unknown]' || $sessionSave === '') {
            $context->addItem(
                'Cache Operational Risks',
                'session save unknown',
                'sessionSave=' . $sessionSave
            );
        }
    }

    protected function extractDatabase(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'database');

        if (!$section) {
            return;
        }

        $keys = array(
            'Host',
            'Database name',
            'Table prefix',
            'Server version',
            'Table count',
            'Setup resources',
        );

        foreach ($keys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('Database Architecture', $key, $value);
            }
        }

        $this->extractDatabaseHighlights($context, $section);
    }

    protected function extractDatabaseHighlights(AiContext $context, array $section)
    {
        $setupResources = $this->parseDatabaseSetupResources($section);

        $this->addDatabaseSetupResourceCounts($context, $setupResources);
        $this->addDatabaseNonCoreSetupResources($context, $setupResources);
        $this->addDatabaseSetupVersionMismatches($context, $setupResources);
        $this->addDatabaseSetupMissingVersions($context, $setupResources);
        $this->addDatabaseHighImpactSetupResources($context, $setupResources);
    }

    protected function parseDatabaseSetupResources(array $section)
    {
        $setupResources = array();

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key !== 'Setup resource') {
                continue;
            }

            $setupResources[] = $this->parseDatabaseSetupResourceValue($value);
        }

        return $setupResources;
    }

    protected function parseDatabaseSetupResourceValue($value)
    {
        $parts = explode(';', $value);
        $code = trim(array_shift($parts));

        $resource = array(
            'code' => $code,
            'version' => '',
            'data_version' => '',
            'raw' => $value,
        );

        foreach ($parts as $part) {
            $part = trim($part);

            if (strpos($part, 'version=') === 0) {
                $resource['version'] = trim(substr($part, strlen('version=')));
            } elseif (strpos($part, 'data_version=') === 0) {
                $resource['data_version'] = trim(substr($part, strlen('data_version=')));
            }
        }

        return $resource;
    }

    protected function addDatabaseSetupResourceCounts(AiContext $context, array $setupResources)
    {
        $core = 0;
        $nonCore = 0;
        $missingVersion = 0;
        $versionMismatch = 0;

        foreach ($setupResources as $resource) {
            if ($this->isCoreSetupResource($resource['code'])) {
                $core++;
            } else {
                $nonCore++;
            }

            if ($this->isMissingSetupVersion($resource['version']) || $this->isMissingSetupVersion($resource['data_version'])) {
                $missingVersion++;
            }

            if (
                !$this->isMissingSetupVersion($resource['version'])
                && !$this->isMissingSetupVersion($resource['data_version'])
                && $resource['version'] !== $resource['data_version']
            ) {
                $versionMismatch++;
            }
        }

        $context->addItem('Database Setup Resource Summary', 'core setup resources', $core);
        $context->addItem('Database Setup Resource Summary', 'non-core setup resources', $nonCore);
        $context->addItem('Database Setup Resource Summary', 'resources with missing versions', $missingVersion);
        $context->addItem('Database Setup Resource Summary', 'resources with version/data_version mismatch', $versionMismatch);
    }

    protected function addDatabaseNonCoreSetupResources(AiContext $context, array $setupResources)
    {
        $count = 0;
        $limit = 80;

        foreach ($setupResources as $resource) {
            if ($this->isCoreSetupResource($resource['code'])) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Database Non-Core Setup Resources',
                    $resource['code'],
                    'version=' . $resource['version'] . '; dataVersion=' . $resource['data_version']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Database Non-Core Setup Resources',
                'Truncated',
                'Only the first ' . $limit . ' non-core setup resources are shown in this short AI context. See full profile for all database setup resource data.'
            );
        }
    }

    protected function addDatabaseSetupVersionMismatches(AiContext $context, array $setupResources)
    {
        $count = 0;
        $limit = 40;

        foreach ($setupResources as $resource) {
            if ($this->isMissingSetupVersion($resource['version']) || $this->isMissingSetupVersion($resource['data_version'])) {
                continue;
            }

            if ($resource['version'] === $resource['data_version']) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Database Setup Version Mismatches',
                    $resource['code'],
                    'version=' . $resource['version'] . '; dataVersion=' . $resource['data_version']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Database Setup Version Mismatches',
                'Truncated',
                'Only the first ' . $limit . ' setup resources with version mismatches are shown in this short AI context. See full profile for all database setup resource data.'
            );
        }
    }

    protected function addDatabaseSetupMissingVersions(AiContext $context, array $setupResources)
    {
        $count = 0;
        $limit = 40;

        foreach ($setupResources as $resource) {
            if (!$this->isMissingSetupVersion($resource['version']) && !$this->isMissingSetupVersion($resource['data_version'])) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Database Setup Missing Versions',
                    $resource['code'],
                    'version=' . $resource['version'] . '; dataVersion=' . $resource['data_version']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Database Setup Missing Versions',
                'Truncated',
                'Only the first ' . $limit . ' setup resources with missing versions are shown in this short AI context. See full profile for all database setup resource data.'
            );
        }
    }

    protected function addDatabaseHighImpactSetupResources(AiContext $context, array $setupResources)
    {
        $count = 0;
        $limit = 80;

        foreach ($setupResources as $resource) {
            if (!$this->isHighImpactSetupResource($resource)) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Database High Impact Setup Resources',
                    $resource['code'],
                    'version=' . $resource['version'] . '; dataVersion=' . $resource['data_version']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Database High Impact Setup Resources',
                'Truncated',
                'Only the first ' . $limit . ' high-impact setup resources are shown in this short AI context. See full profile for all database setup resource data.'
            );
        }
    }

    protected function isCoreSetupResource($code)
    {
        $coreResources = array(
            'admin_setup',
            'adminnotification_setup',
            'api_setup',
            'api2_setup',
            'backup_setup',
            'bundle_setup',
            'captcha_setup',
            'catalog_setup',
            'catalogindex_setup',
            'cataloginventory_setup',
            'catalogrule_setup',
            'catalogsearch_setup',
            'checkout_setup',
            'cms_setup',
            'compiler_setup',
            'contacts_setup',
            'core_setup',
            'cron_setup',
            'customer_setup',
            'dataflow_setup',
            'directory_setup',
            'downloadable_setup',
            'eav_setup',
            'giftmessage_setup',
            'googleanalytics_setup',
            'googlecheckout_setup',
            'importexport_setup',
            'index_setup',
            'install_setup',
            'log_setup',
            'newsletter_setup',
            'oauth_setup',
            'paygate_setup',
            'payment_setup',
            'paypal_setup',
            'paypaluk_setup',
            'persistent_setup',
            'poll_setup',
            'productalert_setup',
            'rating_setup',
            'reports_setup',
            'review_setup',
            'rss_setup',
            'rule_setup',
            'sales_setup',
            'salesrule_setup',
            'sendfriend_setup',
            'shipping_setup',
            'sitemap_setup',
            'tag_setup',
            'tax_setup',
            'usa_setup',
            'weee_setup',
            'widget_setup',
            'wishlist_setup',
            'xmlconnect_setup',
        );

        return in_array($code, $coreResources);
    }

    protected function isMissingSetupVersion($version)
    {
        return $version === '' || $version === '[none]' || $version === '[unknown]' || $version === 'null';
    }

    protected function isHighImpactSetupResource(array $resource)
    {
        if (!$this->isCoreSetupResource($resource['code'])) {
            return true;
        }

        if ($this->isMissingSetupVersion($resource['version']) || $this->isMissingSetupVersion($resource['data_version'])) {
            return true;
        }

        if ($resource['version'] !== $resource['data_version']) {
            return true;
        }

        $needles = array(
            'catalog',
            'checkout',
            'customer',
            'eav',
            'index',
            'payment',
            'sales',
            'salesrule',
            'shipping',
            'tax',
        );

        foreach ($needles as $needle) {
            if (strpos($resource['code'], $needle) !== false) {
                return true;
            }
        }

        return false;
    }
    
    protected function extractRewriteMap(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'rewrite_map');

        if (!$section) {
            return;
        }

        $summaryKeys = array(
            'Summary / resolved rewrite aliases',
            'Summary / custom winning classes',
            'Summary / missing winning class files',
        );

        foreach ($summaryKeys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('Rewrite Map', str_replace('Summary / ', '', $key), $value);
            }
        }
    }
    
    protected function extractEav(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'eav');

        if (!$section) {
            return;
        }

        $summaryKeys = array(
            'Summary / entity types',
            'Summary / attribute sets',
            'Summary / attribute groups',
            'Summary / attributes',
        );

        foreach ($summaryKeys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('EAV Architecture', str_replace('Summary / ', '', $key), $value);
            }
        }

        $this->extractEavHighlights($context, $section);
    }
    
    protected function extractEavHighlights(AiContext $context, array $section)
    {
        $rows = $this->parseEavRows($section);

        $this->addEavEntityTypeHighlights($context, $rows['entity_types']);
        $this->addEavAttributeSetHighlights($context, $rows['attribute_sets']);
        $this->addEavCustomAttributeHighlights($context, $rows['attributes']);
        $this->addEavModelAttributeHighlights($context, $rows['attributes']);
        $this->addEavSearchFilterHighlights($context, $rows['attributes']);
        $this->addEavListingSortingHighlights($context, $rows['attributes']);
        $this->addEavRequiredCustomHighlights($context, $rows['attributes']);
    }

    protected function parseEavRows(array $section)
    {
        $entityTypes = array();
        $attributeSets = array();
        $attributes = array();

        $currentEntityType = null;
        $currentAttribute = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Entity type') {
                if ($currentAttribute !== null) {
                    $attributes[] = $currentAttribute;
                    $currentAttribute = null;
                }

                $currentEntityType = array(
                    'entity_type' => $value,
                    'entity_type_id' => '',
                    'entity_model' => '',
                    'attribute_model' => '',
                    'entity_table' => '',
                    'value_table_prefix' => '',
                    'attribute_set_count' => '',
                    'attribute_count' => '',
                    'important_attributes_reported' => '',
                );

                $entityTypes[] = $currentEntityType;
                continue;
            }

            if (!count($entityTypes)) {
                continue;
            }

            $entityIndex = count($entityTypes) - 1;

            if ($key === 'Attribute set') {
                if ($currentAttribute !== null) {
                    $attributes[] = $currentAttribute;
                    $currentAttribute = null;
                }

                $attributeSets[] = array(
                    'entity_type' => $entityTypes[$entityIndex]['entity_type'],
                    'attribute_set' => $value,
                    'attribute_set_id' => '',
                    'sort_order' => '',
                    'attribute_groups' => '',
                );

                continue;
            }

            if ($key === 'Attribute') {
                if ($currentAttribute !== null) {
                    $attributes[] = $currentAttribute;
                }

                $currentAttribute = array(
                    'entity_type' => $entityTypes[$entityIndex]['entity_type'],
                    'attribute_code' => $value,
                    'attribute_id' => '',
                    'backend_type' => '',
                    'frontend_input' => '',
                    'backend_model' => '',
                    'source_model' => '',
                    'frontend_model' => '',
                    'required' => '',
                    'user_defined' => '',
                    'global_scope' => '',
                    'visible' => '',
                    'searchable' => '',
                    'filterable' => '',
                    'comparable' => '',
                    'used_for_promo_rules' => '',
                    'used_in_product_listing' => '',
                    'used_for_sorting' => '',
                    'apply_to_product_types' => '',
                );

                continue;
            }

            if ($currentAttribute !== null) {
                $this->applyEavAttributeField($currentAttribute, $key, $value);
                continue;
            }

            if (count($attributeSets)) {
                $setIndex = count($attributeSets) - 1;

                if ($key === 'Attribute set ID') {
                    $attributeSets[$setIndex]['attribute_set_id'] = $value;
                    continue;
                }

                if ($key === 'Sort order') {
                    $attributeSets[$setIndex]['sort_order'] = $value;
                    continue;
                }

                if ($key === 'Attribute groups') {
                    $attributeSets[$setIndex]['attribute_groups'] = $value;
                    continue;
                }
            }

            if ($key === 'Entity type ID') {
                $entityTypes[$entityIndex]['entity_type_id'] = $value;
            } elseif ($key === 'Entity model') {
                $entityTypes[$entityIndex]['entity_model'] = $value;
            } elseif ($key === 'Attribute model') {
                $entityTypes[$entityIndex]['attribute_model'] = $value;
            } elseif ($key === 'Entity table') {
                $entityTypes[$entityIndex]['entity_table'] = $value;
            } elseif ($key === 'Value table prefix') {
                $entityTypes[$entityIndex]['value_table_prefix'] = $value;
            } elseif ($key === 'Attribute set count') {
                $entityTypes[$entityIndex]['attribute_set_count'] = $value;
            } elseif ($key === 'Attribute count') {
                $entityTypes[$entityIndex]['attribute_count'] = $value;
            } elseif ($key === 'Important attributes reported') {
                $entityTypes[$entityIndex]['important_attributes_reported'] = $value;
            }
        }

        if ($currentAttribute !== null) {
            $attributes[] = $currentAttribute;
        }

        return array(
            'entity_types' => $entityTypes,
            'attribute_sets' => $attributeSets,
            'attributes' => $attributes,
        );
    }

    protected function applyEavAttributeField(array &$attribute, $key, $value)
    {
        if ($key === 'Attribute ID') {
            $attribute['attribute_id'] = $value;
        } elseif ($key === 'Backend type') {
            $attribute['backend_type'] = $value;
        } elseif ($key === 'Frontend input') {
            $attribute['frontend_input'] = $value;
        } elseif ($key === 'Backend model') {
            $attribute['backend_model'] = $value;
        } elseif ($key === 'Source model') {
            $attribute['source_model'] = $value;
        } elseif ($key === 'Frontend model') {
            $attribute['frontend_model'] = $value;
        } elseif ($key === 'Required') {
            $attribute['required'] = $value;
        } elseif ($key === 'User defined') {
            $attribute['user_defined'] = $value;
        } elseif ($key === 'Global scope') {
            $attribute['global_scope'] = $value;
        } elseif ($key === 'Visible') {
            $attribute['visible'] = $value;
        } elseif ($key === 'Searchable') {
            $attribute['searchable'] = $value;
        } elseif ($key === 'Filterable') {
            $attribute['filterable'] = $value;
        } elseif ($key === 'Comparable') {
            $attribute['comparable'] = $value;
        } elseif ($key === 'Used for promo rules') {
            $attribute['used_for_promo_rules'] = $value;
        } elseif ($key === 'Used in product listing') {
            $attribute['used_in_product_listing'] = $value;
        } elseif ($key === 'Used for sorting') {
            $attribute['used_for_sorting'] = $value;
        } elseif ($key === 'Apply to product types') {
            $attribute['apply_to_product_types'] = $value;
        }
    }

    protected function addEavEntityTypeHighlights(AiContext $context, array $entityTypes)
    {
        foreach ($entityTypes as $entityType) {
            $context->addItem(
                'EAV Entity Types',
                $entityType['entity_type'],
                'entityModel=' . $entityType['entity_model']
                . '; attributeModel=' . $entityType['attribute_model']
                . '; table=' . $entityType['entity_table']
                . '; attributeSets=' . $entityType['attribute_set_count']
                . '; attributes=' . $entityType['attribute_count']
                . '; importantReported=' . $entityType['important_attributes_reported']
            );
        }
    }

    protected function addEavAttributeSetHighlights(AiContext $context, array $attributeSets)
    {
        $count = 0;
        $limit = 80;

        foreach ($attributeSets as $attributeSet) {
            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'EAV Attribute Sets',
                    $attributeSet['entity_type'] . ' / ' . $attributeSet['attribute_set'],
                    'id=' . $attributeSet['attribute_set_id']
                    . '; groups=' . $this->summariseEavGroups($attributeSet['attribute_groups'])
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'EAV Attribute Sets',
                'Truncated',
                'Only the first ' . $limit . ' EAV attribute sets are shown in this short AI context. See full profile for all EAV data.'
            );
        }
    }

    protected function summariseEavGroups($groups)
    {
        if ($groups === '' || $groups === '[unknown]' || $groups === '[none]') {
            return $groups;
        }

        $parts = explode(',', $groups);
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, 'strlen');

        if (count($parts) <= 12) {
            return implode(', ', $parts);
        }

        return implode(', ', array_slice($parts, 0, 12)) . ', ...';
    }

    protected function addEavCustomAttributeHighlights(AiContext $context, array $attributes)
    {
        $sections = array(
            'catalog_product' => 'EAV Custom Product Attributes',
            'catalog_category' => 'EAV Custom Category Attributes',
            'customer' => 'EAV Custom Customer Attributes',
            'customer_address' => 'EAV Custom Customer Address Attributes',
        );

        $counts = array();

        foreach ($attributes as $attribute) {
            if ($attribute['user_defined'] !== 'yes') {
                continue;
            }

            $section = 'EAV Custom Other Attributes';

            if (isset($sections[$attribute['entity_type']])) {
                $section = $sections[$attribute['entity_type']];
            }

            if (!isset($counts[$section])) {
                $counts[$section] = 0;
            }

            $counts[$section]++;

            if ($counts[$section] <= 80) {
                $context->addItem(
                    $section,
                    $attribute['attribute_code'],
                    $this->formatEavAttributeSummary($attribute)
                );
            }
        }

        foreach ($counts as $section => $count) {
            if ($count > 80) {
                $context->addItem(
                    $section,
                    'Truncated',
                    'Only the first 80 custom attributes are shown in this short AI context. See full profile for all EAV data.'
                );
            }
        }
    }

    protected function addEavModelAttributeHighlights(AiContext $context, array $attributes)
    {
        $count = 0;
        $limit = 100;

        foreach ($attributes as $attribute) {
            if (!$this->hasImportantEavModel($attribute)) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'EAV Attributes With Models',
                    $attribute['entity_type'] . ' / ' . $attribute['attribute_code'],
                    'backend=' . $attribute['backend_model']
                    . '; source=' . $attribute['source_model']
                    . '; frontend=' . $attribute['frontend_model']
                    . '; input=' . $attribute['frontend_input']
                    . '; userDefined=' . $attribute['user_defined']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'EAV Attributes With Models',
                'Truncated',
                'Only the first ' . $limit . ' attributes with backend/source/frontend models are shown in this short AI context. See full profile for all EAV data.'
            );
        }
    }

    protected function hasImportantEavModel(array $attribute)
    {
        if ($this->isMeaningfulEavModel($attribute['backend_model'])) {
            return true;
        }

        if ($this->isMeaningfulEavModel($attribute['source_model'])) {
            return true;
        }

        if ($this->isMeaningfulEavModel($attribute['frontend_model'])) {
            return true;
        }

        return false;
    }

    protected function isMeaningfulEavModel($model)
    {
        if ($model === '' || $model === '[none]' || $model === '[unknown]' || $model === '[n/a]') {
            return false;
        }

        return true;
    }

    protected function addEavSearchFilterHighlights(AiContext $context, array $attributes)
    {
        $count = 0;
        $limit = 100;

        foreach ($attributes as $attribute) {
            if ($attribute['entity_type'] !== 'catalog_product') {
                continue;
            }

            if (
                $attribute['searchable'] !== 'yes'
                && $attribute['filterable'] !== 'yes'
                && $attribute['comparable'] !== 'yes'
                && $attribute['used_for_promo_rules'] !== 'yes'
            ) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'EAV Search Filter Promo Attributes',
                    $attribute['attribute_code'],
                    'input=' . $attribute['frontend_input']
                    . '; scope=' . $attribute['global_scope']
                    . '; searchable=' . $attribute['searchable']
                    . '; filterable=' . $attribute['filterable']
                    . '; comparable=' . $attribute['comparable']
                    . '; promoRules=' . $attribute['used_for_promo_rules']
                    . '; userDefined=' . $attribute['user_defined']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'EAV Search Filter Promo Attributes',
                'Truncated',
                'Only the first ' . $limit . ' product search/filter/comparable/promo attributes are shown in this short AI context. See full profile for all EAV data.'
            );
        }
    }

    protected function addEavListingSortingHighlights(AiContext $context, array $attributes)
    {
        $count = 0;
        $limit = 100;

        foreach ($attributes as $attribute) {
            if ($attribute['entity_type'] !== 'catalog_product') {
                continue;
            }

            if ($attribute['used_in_product_listing'] !== 'yes' && $attribute['used_for_sorting'] !== 'yes') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'EAV Product Listing Sorting Attributes',
                    $attribute['attribute_code'],
                    'input=' . $attribute['frontend_input']
                    . '; scope=' . $attribute['global_scope']
                    . '; listing=' . $attribute['used_in_product_listing']
                    . '; sorting=' . $attribute['used_for_sorting']
                    . '; userDefined=' . $attribute['user_defined']
                    . '; appliesTo=' . $attribute['apply_to_product_types']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'EAV Product Listing Sorting Attributes',
                'Truncated',
                'Only the first ' . $limit . ' product listing/sorting attributes are shown in this short AI context. See full profile for all EAV data.'
            );
        }
    }

    protected function addEavRequiredCustomHighlights(AiContext $context, array $attributes)
    {
        $count = 0;
        $limit = 80;

        foreach ($attributes as $attribute) {
            if ($attribute['required'] !== 'yes' || $attribute['user_defined'] !== 'yes') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'EAV Required Custom Attributes',
                    $attribute['entity_type'] . ' / ' . $attribute['attribute_code'],
                    $this->formatEavAttributeSummary($attribute)
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'EAV Required Custom Attributes',
                'Truncated',
                'Only the first ' . $limit . ' required custom attributes are shown in this short AI context. See full profile for all EAV data.'
            );
        }
    }

    protected function formatEavAttributeSummary(array $attribute)
    {
        return 'id=' . $attribute['attribute_id']
            . '; type=' . $attribute['backend_type']
            . '; input=' . $attribute['frontend_input']
            . '; required=' . $attribute['required']
            . '; scope=' . $attribute['global_scope']
            . '; visible=' . $attribute['visible']
            . '; appliesTo=' . $attribute['apply_to_product_types'];
    }
    
    protected function addAiGuidance(AiContext $context, array $data)
    {
        $context->addItem(
            'AI Guidance',
            'Purpose',
            'Use this context file as the first-pass architectural summary before reading the full profiler report.'
        );

        $context->addItem(
            'AI Guidance',
            'Magento 1/OpenMage',
            'Assume Magento 1.x/OpenMage conventions unless the detailed report proves otherwise.'
        );

        $context->addItem(
            'AI Guidance',
            'Theme work',
            'Check Theme Hierarchy and Theme Store Mapping before advising on frontend templates, layouts or CSS.'
        );
        
        $context->addItem('AI Guidance', 'Rewrite work', 'Check Rewrite Architecture and Rewrite Map before advising on model, block or helper overrides.');
        $context->addItem('AI Guidance', 'Module work', 'Check Module Architecture and Custom Modules before advising on code locations.');
        $context->addItem('AI Guidance', 'Performance work', 'Check Cache Architecture, Index Architecture and Cron Architecture before advising on frontend or catalogue performance.');
        $context->addItem('AI Guidance', 'Database work', 'Check Database Architecture before advising on setup scripts, resource versions or table-level issues.');
        $context->addItem('AI Guidance', 'EAV work', 'Check EAV Architecture and the full EAV collector output before advising on product, category, customer or customer address attributes.');
        $context->addItem('AI Guidance', 'Routing work', 'Check Router Architecture and Controller Architecture before advising on custom frontend or admin routes.');
        $context->addItem('AI Guidance', 'Layout work', 'Check Layout Architecture and Theme Resolution before advising on layout XML or template changes.');
    }
    
    protected function extractObservers(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'observers');

        if (!$section) {
            return;
        }

        $summaryKeys = array(
            'Summary / total observers',
            'Summary / events with observers',
            'Summary / global observers',
            'Summary / frontend observers',
            'Summary / adminhtml observers',
            'Summary / custom observers',
            'Summary / disabled observers',
            'Summary / missing observer class files',
        );

        foreach ($summaryKeys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem(
                    'Observer Architecture',
                    str_replace('Summary / ', '', $key),
                    $value
                );
            }
        }

        $this->extractObserverBusiestEvents($context, $section);
        $this->extractObserverHighlights($context, $section);
    }

    protected function extractObserverBusiestEvents(AiContext $context, array $section)
    {
        $count = 0;
        $limit = 10;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if (strpos($key, 'Summary / busiest event / ') !== 0) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Observer Busiest Events',
                    str_replace('Summary / ', '', $key),
                    $value
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Observer Busiest Events',
                'Truncated',
                'Only the first ' . $limit . ' busiest observer events are shown in this short AI context. See full profile for all observer data.'
            );
        }
    }

    protected function extractObserverHighlights(AiContext $context, array $section)
    {
        $observers = $this->parseObserverRows($section);

        $this->addObserverMissingClassHighlights($context, $observers);
        $this->addObserverDisabledHighlights($context, $observers);
        $this->addObserverCustomHighlights($context, $observers);
        $this->addObserverHighImpactHighlights($context, $observers);
    }

    protected function parseObserverRows(array $section)
    {
        $observers = array();
        $currentObserver = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Observer') {
                if ($currentObserver !== null) {
                    $observers[] = $currentObserver;
                }

                $currentObserver = array(
                    'observer' => $value,
                    'area' => '',
                    'event' => '',
                    'observer_name' => '',
                    'class' => '',
                    'resolved_class' => '',
                    'method' => '',
                    'type' => '',
                    'disabled' => '',
                    'custom' => '',
                    'class_file_exists' => '',
                    'class_file' => '',
                );

                continue;
            }

            if ($currentObserver === null) {
                continue;
            }

            if ($key === 'Area') {
                $currentObserver['area'] = $value;
            } elseif ($key === 'Event') {
                $currentObserver['event'] = $value;
            } elseif ($key === 'Observer name') {
                $currentObserver['observer_name'] = $value;
            } elseif ($key === 'Class') {
                $currentObserver['class'] = $value;
            } elseif ($key === 'Resolved class') {
                $currentObserver['resolved_class'] = $value;
            } elseif ($key === 'Method') {
                $currentObserver['method'] = $value;
            } elseif ($key === 'Type') {
                $currentObserver['type'] = $value;
            } elseif ($key === 'Disabled') {
                $currentObserver['disabled'] = $value;
            } elseif ($key === 'Custom') {
                $currentObserver['custom'] = $value;
            } elseif ($key === 'Class file exists') {
                $currentObserver['class_file_exists'] = $value;
            } elseif ($key === 'Class file') {
                $currentObserver['class_file'] = $value;
            }
        }

        if ($currentObserver !== null) {
            $observers[] = $currentObserver;
        }

        return $observers;
    }

    protected function addObserverMissingClassHighlights(AiContext $context, array $observers)
    {
        $count = 0;
        $limit = 20;

        foreach ($observers as $observer) {
            if ($observer['class_file_exists'] !== 'no') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Observer Missing Classes',
                    $observer['observer'],
                    'class=' . $observer['class']
                    . '; resolved=' . $observer['resolved_class']
                    . '; method=' . $observer['method']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Observer Missing Classes',
                'Truncated',
                'Only the first ' . $limit . ' missing observer classes are shown in this short AI context. See full profile for all observer data.'
            );
        }
    }

    protected function addObserverDisabledHighlights(AiContext $context, array $observers)
    {
        $count = 0;
        $limit = 20;

        foreach ($observers as $observer) {
            if ($observer['disabled'] !== 'yes') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Observer Disabled',
                    $observer['observer'],
                    'class=' . $observer['class']
                    . '; resolved=' . $observer['resolved_class']
                    . '; method=' . $observer['method']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Observer Disabled',
                'Truncated',
                'Only the first ' . $limit . ' disabled observers are shown in this short AI context. See full profile for all observer data.'
            );
        }
    }

    protected function addObserverCustomHighlights(AiContext $context, array $observers)
    {
        $count = 0;
        $limit = 60;

        foreach ($observers as $observer) {
            if ($observer['custom'] !== 'yes') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Observer Custom Observers',
                    $observer['observer'],
                    'class=' . $observer['class']
                    . '; resolved=' . $observer['resolved_class']
                    . '; method=' . $observer['method']
                    . '; type=' . $observer['type']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Observer Custom Observers',
                'Truncated',
                'Only the first ' . $limit . ' custom observers are shown in this short AI context. See full profile for all observer data.'
            );
        }
    }

    protected function addObserverHighImpactHighlights(AiContext $context, array $observers)
    {
        $count = 0;
        $limit = 80;

        foreach ($observers as $observer) {
            if (!$this->isHighImpactObserverEvent($observer['event'])) {
                continue;
            }

            if ($observer['custom'] !== 'yes' && $observer['class_file_exists'] !== 'no' && $observer['disabled'] !== 'yes') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Observer High Impact Events',
                    $observer['observer'],
                    'area=' . $observer['area']
                    . '; event=' . $observer['event']
                    . '; class=' . $observer['class']
                    . '; resolved=' . $observer['resolved_class']
                    . '; method=' . $observer['method']
                    . '; custom=' . $observer['custom']
                    . '; classFileExists=' . $observer['class_file_exists']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Observer High Impact Events',
                'Truncated',
                'Only the first ' . $limit . ' high-impact observer entries are shown in this short AI context. See full profile for all observer data.'
            );
        }
    }

    protected function isHighImpactObserverEvent($event)
    {
        $needles = array(
            'sales_',
            'checkout_',
            'customer_',
            'catalog_product_',
            'catalog_category_',
            'cataloginventory_',
            'controller_action_',
            'core_block_',
            'adminhtml_',
            'aschroder_smtppro_',
            'model_save_',
            'model_delete_',
        );

        foreach ($needles as $needle) {
            if (strpos($event, $needle) === 0) {
                return true;
            }
        }

        return false;
    }
    
    protected function extractLayouts(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'layouts');

        if (!$section) {
            return;
        }

        $keys = array(
            'Summary / declared module layout files',
            'Summary / frontend theme layout files',
            'Summary / total layout files',
        );

        foreach ($keys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('Layout Architecture', str_replace('Summary / ', '', $key), $value);
            }
        }

        $this->extractLayoutHighlights($context, $section, $data);
    }

    protected function extractLayoutHighlights(AiContext $context, array $section, array $data)
    {
        $rows = $this->parseLayoutRows($section);
        $activeThemes = $this->getActiveStoreLayoutThemes($data);

        $this->addLayoutPackageThemeCounts($context, $rows['theme_files']);
        $this->addLayoutMissingDeclaredFiles($context, $rows['declared_files']);
        $this->addLayoutCustomModuleFiles($context, $rows['declared_files']);
        $this->addLayoutActiveThemeFiles($context, $rows['theme_files'], $activeThemes);
        $this->addLayoutDuplicateThemeFiles($context, $rows['theme_files']);
    }

    protected function parseLayoutRows(array $section)
    {
        $declaredFiles = array();
        $themeFiles = array();
        $currentDeclaredFile = null;
        $currentThemeFile = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Declared layout file') {
                if ($currentDeclaredFile !== null) {
                    $declaredFiles[] = $currentDeclaredFile;
                }

                if ($currentThemeFile !== null) {
                    $themeFiles[] = $currentThemeFile;
                    $currentThemeFile = null;
                }

                $currentDeclaredFile = array(
                    'label' => $value,
                    'area' => '',
                    'module' => '',
                    'file' => '',
                    'path' => '',
                    'exists' => '',
                );

                continue;
            }

            if ($key === 'Theme layout file') {
                if ($currentDeclaredFile !== null) {
                    $declaredFiles[] = $currentDeclaredFile;
                    $currentDeclaredFile = null;
                }

                if ($currentThemeFile !== null) {
                    $themeFiles[] = $currentThemeFile;
                }

                $currentThemeFile = array(
                    'label' => $value,
                    'package' => '',
                    'theme' => '',
                    'package_theme' => '',
                    'file' => '',
                    'path' => '',
                );

                continue;
            }

            if ($currentDeclaredFile !== null) {
                if ($key === 'Area') {
                    $currentDeclaredFile['area'] = $value;
                } elseif ($key === 'Module') {
                    $currentDeclaredFile['module'] = $value;
                } elseif ($key === 'File') {
                    $currentDeclaredFile['file'] = $value;
                } elseif ($key === 'Path') {
                    $currentDeclaredFile['path'] = $value;
                } elseif ($key === 'Exists') {
                    $currentDeclaredFile['exists'] = $value;
                }

                continue;
            }

            if ($currentThemeFile !== null) {
                if ($key === 'Package') {
                    $currentThemeFile['package'] = $value;
                    $currentThemeFile['package_theme'] = $this->joinPackageTheme(
                        $currentThemeFile['package'],
                        $currentThemeFile['theme']
                    );
                } elseif ($key === 'Theme') {
                    $currentThemeFile['theme'] = $value;
                    $currentThemeFile['package_theme'] = $this->joinPackageTheme(
                        $currentThemeFile['package'],
                        $currentThemeFile['theme']
                    );
                } elseif ($key === 'File') {
                    $currentThemeFile['file'] = $value;
                } elseif ($key === 'Path') {
                    $currentThemeFile['path'] = $value;
                }
            }
        }

        if ($currentDeclaredFile !== null) {
            $declaredFiles[] = $currentDeclaredFile;
        }

        if ($currentThemeFile !== null) {
            $themeFiles[] = $currentThemeFile;
        }

        return array(
            'declared_files' => $declaredFiles,
            'theme_files' => $themeFiles,
        );
    }

    protected function getActiveStoreLayoutThemes(array $data)
    {
        $section = $this->findSection($data, 'theme_hierarchy');
        $themes = array();

        if (!$section) {
            return $themes;
        }

        $storeActive = false;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Store') {
                $storeActive = false;
                continue;
            }

            if ($key === 'Active') {
                $storeActive = ($value === 'yes');
                continue;
            }

            if (!$storeActive) {
                continue;
            }

            if ($key === 'Effective theme') {
                if ($value !== '' && $value !== '[unknown]') {
                    $themes[$value] = true;
                }

                continue;
            }

            if (strpos($key, 'Layout fallback / ') === 0) {
                $theme = $this->extractThemeNameFromFallbackValue($value);

                if ($theme !== '') {
                    $themes[$theme] = true;
                }
            }
        }

        return $themes;
    }

    protected function extractThemeNameFromFallbackValue($value)
    {
        $parts = explode(';', $value);
        $theme = trim($parts[0]);

        if ($theme === '' || $theme === '[unknown]') {
            return '';
        }

        return $theme;
    }

    protected function joinPackageTheme($package, $theme)
    {
        if ($package === '' || $theme === '') {
            return '';
        }

        return $package . '/' . $theme;
    }

    protected function addLayoutPackageThemeCounts(AiContext $context, array $themeFiles)
    {
        $counts = array();

        foreach ($themeFiles as $row) {
            if ($row['package_theme'] === '') {
                continue;
            }

            if (!isset($counts[$row['package_theme']])) {
                $counts[$row['package_theme']] = 0;
            }

            $counts[$row['package_theme']]++;
        }

        arsort($counts);

        $count = 0;
        $limit = 30;

        foreach ($counts as $packageTheme => $fileCount) {
            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Layout Theme File Counts',
                    $packageTheme,
                    $fileCount
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Layout Theme File Counts',
                'Truncated',
                'Only the first ' . $limit . ' package/theme layout file counts are shown in this short AI context. See full profile for all layout files.'
            );
        }
    }

    protected function addLayoutMissingDeclaredFiles(AiContext $context, array $declaredFiles)
    {
        $count = 0;
        $limit = 40;

        foreach ($declaredFiles as $row) {
            if ($row['exists'] !== 'no') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Layout Missing Declared Files',
                    $row['label'],
                    'area=' . $row['area']
                    . '; module=' . $row['module']
                    . '; file=' . $row['file']
                    . '; path=' . $row['path']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Layout Missing Declared Files',
                'Truncated',
                'Only the first ' . $limit . ' missing declared layout files are shown in this short AI context. See full profile for all layout data.'
            );
        }
    }

    protected function addLayoutCustomModuleFiles(AiContext $context, array $declaredFiles)
    {
        $count = 0;
        $limit = 80;

        foreach ($declaredFiles as $row) {
            if (!$this->isCustomModuleName($row['module'])) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Layout Custom Module Files',
                    $row['label'],
                    'area=' . $row['area']
                    . '; module=' . $row['module']
                    . '; file=' . $row['file']
                    . '; exists=' . $row['exists']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Layout Custom Module Files',
                'Truncated',
                'Only the first ' . $limit . ' custom module layout files are shown in this short AI context. See full profile for all layout data.'
            );
        }
    }

    protected function addLayoutActiveThemeFiles(AiContext $context, array $themeFiles, array $activeThemes)
    {
        $count = 0;
        $limit = 100;

        foreach ($themeFiles as $row) {
            if ($row['package_theme'] === '') {
                continue;
            }

            if (!isset($activeThemes[$row['package_theme']])) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Layout Active Store Theme Files',
                    $row['package_theme'] . ' / ' . $row['file'],
                    'path=' . $row['path']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Layout Active Store Theme Files',
                'Truncated',
                'Only the first ' . $limit . ' active store theme layout files are shown in this short AI context. See full profile for all layout data.'
            );
        }
    }

    protected function addLayoutDuplicateThemeFiles(AiContext $context, array $themeFiles)
    {
        $byFile = array();

        foreach ($themeFiles as $row) {
            if ($row['file'] === '') {
                continue;
            }

            if (!isset($byFile[$row['file']])) {
                $byFile[$row['file']] = array();
            }

            $byFile[$row['file']][] = $row['package_theme'];
        }

        ksort($byFile);

        $count = 0;
        $limit = 60;

        foreach ($byFile as $file => $packageThemes) {
            $packageThemes = array_values(array_unique($packageThemes));

            if (count($packageThemes) < 2) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Layout Duplicate Theme Filenames',
                    $file,
                    implode(', ', $packageThemes)
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Layout Duplicate Theme Filenames',
                'Truncated',
                'Only the first ' . $limit . ' duplicate theme layout filenames are shown in this short AI context. See full profile for all layout data.'
            );
        }
    }

    protected function isCustomModuleName($module)
    {
        if ($module === '' || $module === '[unknown]') {
            return false;
        }

        $corePrefixes = array(
            'Mage_',
            'Enterprise_',
        );

        foreach ($corePrefixes as $prefix) {
            if (strpos($module, $prefix) === 0) {
                return false;
            }
        }

        return true;
    }
    
    protected function extractRouters(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'routers');

        if (!$section) {
            return;
        }

        $value = $this->item($section, 'Summary / total routes');

        if ($value !== '[unknown]') {
            $context->addItem('Router Architecture', 'total resolved route module entries', $value);
        }

        $moduleRouterDeclarations = $this->findModuleRouterDeclarationCount($data);

        if ($moduleRouterDeclarations !== '[unknown]') {
            $context->addItem(
                'Router Architecture',
                'raw router declarations in config.xml',
                $moduleRouterDeclarations
            );
        }

        $this->extractRouterHighlights($context, $section);
    }

    protected function findModuleRouterDeclarationCount(array $data)
    {
        $section = $this->findSection($data, 'modules');

        if (!$section) {
            return '[unknown]';
        }

        return $this->item($section, 'Summary / routers declared in config.xml');
    }

    protected function extractRouterHighlights(AiContext $context, array $section)
    {
        $routes = $this->parseRouterRows($section);

        $this->addRouterAreaCounts($context, $routes);
        $this->addRouterFrontNames($context, $routes);
        $this->addRouterCustomRoutes($context, $routes);
        $this->addRouterAdminRoutes($context, $routes);
    }

    protected function parseRouterRows(array $section)
    {
        $routes = array();
        $currentRoute = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Router') {
                if ($currentRoute !== null) {
                    $routes[] = $currentRoute;
                }

                $currentRoute = array(
                    'router' => $value,
                    'area' => '',
                    'router_name' => '',
                    'module_alias' => '',
                    'module' => '',
                    'front_name' => '',
                );

                continue;
            }

            if ($currentRoute === null) {
                continue;
            }

            if ($key === 'Area') {
                $currentRoute['area'] = $value;
            } elseif ($key === 'Router name') {
                $currentRoute['router_name'] = $value;
            } elseif ($key === 'Module alias') {
                $currentRoute['module_alias'] = $value;
            } elseif ($key === 'Module') {
                $currentRoute['module'] = $value;
            } elseif ($key === 'Front name') {
                $currentRoute['front_name'] = $value;
            }
        }

        if ($currentRoute !== null) {
            $routes[] = $currentRoute;
        }

        return $routes;
    }

    protected function addRouterAreaCounts(AiContext $context, array $routes)
    {
        $counts = array();

        foreach ($routes as $route) {
            if ($route['area'] === '') {
                continue;
            }

            if (!isset($counts[$route['area']])) {
                $counts[$route['area']] = 0;
            }

            $counts[$route['area']]++;
        }

        ksort($counts);

        foreach ($counts as $area => $count) {
            $context->addItem(
                'Router Area Counts',
                $area,
                $count
            );
        }
    }

    protected function addRouterFrontNames(AiContext $context, array $routes)
    {
        $count = 0;
        $limit = 40;

        foreach ($routes as $route) {
            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Router Front Names',
                    $route['area'] . ' / ' . $route['front_name'],
                    'router=' . $route['router_name']
                    . '; moduleAlias=' . $route['module_alias']
                    . '; module=' . $route['module']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Router Front Names',
                'Truncated',
                'Only the first ' . $limit . ' router front names are shown in this short AI context. See full profile for all router data.'
            );
        }
    }

    protected function addRouterCustomRoutes(AiContext $context, array $routes)
    {
        $count = 0;
        $limit = 40;

        foreach ($routes as $route) {
            if (!$this->isCustomModuleName($route['module'])) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Router Custom Routes',
                    $route['router'],
                    'area=' . $route['area']
                    . '; frontName=' . $route['front_name']
                    . '; router=' . $route['router_name']
                    . '; moduleAlias=' . $route['module_alias']
                    . '; module=' . $route['module']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Router Custom Routes',
                'Truncated',
                'Only the first ' . $limit . ' custom router entries are shown in this short AI context. See full profile for all router data.'
            );
        }
    }

    protected function addRouterAdminRoutes(AiContext $context, array $routes)
    {
        $count = 0;
        $limit = 40;

        foreach ($routes as $route) {
            if ($route['area'] !== 'adminhtml') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Router Admin Routes',
                    $route['front_name'],
                    'router=' . $route['router_name']
                    . '; moduleAlias=' . $route['module_alias']
                    . '; module=' . $route['module']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Router Admin Routes',
                'Truncated',
                'Only the first ' . $limit . ' admin router entries are shown in this short AI context. See full profile for all router data.'
            );
        }
    }
    
    protected function extractControllers(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'controllers');

        if (!$section) {
            return;
        }

        $value = $this->item($section, 'Summary / controller files');

        if ($value !== '[unknown]') {
            $context->addItem('Controller Architecture', 'controller files', $value);
        }

        $this->extractControllerHighlights($context, $section);
    }
    
    protected function extractControllerHighlights(AiContext $context, array $section)
    {
        $controllers = $this->parseControllerRows($section);

        $this->addControllerCodePoolCounts($context, $controllers);
        $this->addControllerModuleCounts($context, $controllers);
        $this->addControllerCustomModuleCounts($context, $controllers);
        $this->addControllerAdminhtmlHighlights($context, $controllers);
        $this->addControllerCustomHighlights($context, $controllers);
        $this->addControllerHighImpactHighlights($context, $controllers);
    }

    protected function parseControllerRows(array $section)
    {
        $controllers = array();
        $currentController = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Controller file') {
                if ($currentController !== null) {
                    $controllers[] = $currentController;
                }

                $currentController = array(
                    'label' => $value,
                    'code_pool' => '',
                    'module' => '',
                    'path' => '',
                    'relative_path' => '',
                    'area' => '',
                );

                continue;
            }

            if ($currentController === null) {
                continue;
            }

            if ($key === 'Code pool') {
                $currentController['code_pool'] = $value;
            } elseif ($key === 'Module') {
                $currentController['module'] = $value;
            } elseif ($key === 'Path') {
                $currentController['path'] = $value;
                $currentController['relative_path'] = $this->makeControllerPathRelative($value);
                $currentController['area'] = $this->detectControllerArea($value);
            }
        }

        if ($currentController !== null) {
            $controllers[] = $currentController;
        }

        return $controllers;
    }

    protected function makeControllerPathRelative($path)
    {
        $pos = strpos($path, '/app/code/');

        if ($pos === false) {
            return $path;
        }

        return substr($path, $pos + 1);
    }

    protected function detectControllerArea($path)
    {
        if (strpos($path, '/controllers/Adminhtml/') !== false) {
            return 'adminhtml';
        }

        if (strpos($path, '/controllers/Admin/') !== false) {
            return 'adminhtml';
        }

        return 'frontend_or_global';
    }

    protected function addControllerCodePoolCounts(AiContext $context, array $controllers)
    {
        $counts = array();

        foreach ($controllers as $controller) {
            if ($controller['code_pool'] === '') {
                continue;
            }

            if (!isset($counts[$controller['code_pool']])) {
                $counts[$controller['code_pool']] = 0;
            }

            $counts[$controller['code_pool']]++;
        }

        ksort($counts);

        foreach ($counts as $codePool => $count) {
            $context->addItem(
                'Controller Code Pool Counts',
                $codePool,
                $count
            );
        }
    }

    protected function addControllerModuleCounts(AiContext $context, array $controllers)
    {
        $counts = array();

        foreach ($controllers as $controller) {
            if ($controller['module'] === '') {
                continue;
            }

            if (!isset($counts[$controller['module']])) {
                $counts[$controller['module']] = 0;
            }

            $counts[$controller['module']]++;
        }

        arsort($counts);

        $count = 0;
        $limit = 30;

        foreach ($counts as $module => $controllerCount) {
            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Controller Module Counts',
                    $module,
                    $controllerCount
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Controller Module Counts',
                'Truncated',
                'Only the first ' . $limit . ' controller-heavy modules are shown in this short AI context. See full profile for all controller data.'
            );
        }
    }

    protected function addControllerCustomModuleCounts(AiContext $context, array $controllers)
    {
        $counts = array();

        foreach ($controllers as $controller) {
            if (!$this->isCustomModuleName($controller['module'])) {
                continue;
            }

            if (!isset($counts[$controller['module']])) {
                $counts[$controller['module']] = 0;
            }

            $counts[$controller['module']]++;
        }

        arsort($counts);

        $count = 0;
        $limit = 40;

        foreach ($counts as $module => $controllerCount) {
            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Controller Custom Module Counts',
                    $module,
                    $controllerCount
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Controller Custom Module Counts',
                'Truncated',
                'Only the first ' . $limit . ' custom modules with controllers are shown in this short AI context. See full profile for all controller data.'
            );
        }
    }

    protected function addControllerAdminhtmlHighlights(AiContext $context, array $controllers)
    {
        $count = 0;
        $limit = 80;

        foreach ($controllers as $controller) {
            if ($controller['area'] !== 'adminhtml') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Controller Adminhtml Controllers',
                    $controller['label'],
                    'module=' . $controller['module']
                    . '; codePool=' . $controller['code_pool']
                    . '; path=' . $controller['relative_path']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Controller Adminhtml Controllers',
                'Truncated',
                'Only the first ' . $limit . ' adminhtml controllers are shown in this short AI context. See full profile for all controller data.'
            );
        }
    }

    protected function addControllerCustomHighlights(AiContext $context, array $controllers)
    {
        $count = 0;
        $limit = 100;

        foreach ($controllers as $controller) {
            if (!$this->isCustomModuleName($controller['module'])) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Controller Custom Controllers',
                    $controller['label'],
                    'area=' . $controller['area']
                    . '; module=' . $controller['module']
                    . '; codePool=' . $controller['code_pool']
                    . '; path=' . $controller['relative_path']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Controller Custom Controllers',
                'Truncated',
                'Only the first ' . $limit . ' custom controllers are shown in this short AI context. See full profile for all controller data.'
            );
        }
    }

    protected function addControllerHighImpactHighlights(AiContext $context, array $controllers)
    {
        $count = 0;
        $limit = 100;

        foreach ($controllers as $controller) {
            if (!$this->isHighImpactController($controller)) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Controller High Impact Controllers',
                    $controller['label'],
                    'area=' . $controller['area']
                    . '; module=' . $controller['module']
                    . '; codePool=' . $controller['code_pool']
                    . '; path=' . $controller['relative_path']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Controller High Impact Controllers',
                'Truncated',
                'Only the first ' . $limit . ' high-impact controllers are shown in this short AI context. See full profile for all controller data.'
            );
        }
    }

    protected function isHighImpactController(array $controller)
    {
        if ($controller['area'] === 'adminhtml') {
            return true;
        }

        if (!$this->isCustomModuleName($controller['module'])) {
            return false;
        }

        $haystack = strtolower(
            $controller['label']
            . ' ' . $controller['module']
            . ' ' . $controller['relative_path']
        );

        $needles = array(
            'account',
            'admin',
            'ajax',
            'api',
            'cart',
            'catalog',
            'category',
            'checkout',
            'customer',
            'export',
            'import',
            'index',
            'order',
            'payment',
            'product',
            'quote',
            'report',
            'sales',
            'search',
        );

        foreach ($needles as $needle) {
            if (strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
    
    protected function findSection(array $data, $collectorCode)
    {
        foreach ($data['sections'] as $section) {
            if (isset($section['collector_code']) && $section['collector_code'] === $collectorCode) {
                return $section;
            }
        }

        return null;
    }

    protected function item(array $section, $key)
    {
        foreach ($section['items'] as $item) {
            if ($item['key'] === $key) {
                return $item['value'];
            }
        }

        return '[unknown]';
    }
}