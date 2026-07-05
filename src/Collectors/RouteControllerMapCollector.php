<?php

class RouteControllerMapCollector extends AbstractCollector
{
    public function getCode() { return 'route_controller_map'; }
    public function getTitle() { return 'Route Controller Map'; }
    public function getCategory() { return 'Architecture'; }
    public function getDescription() { return 'Maps Magento router frontNames to controller directories and actions where discoverable.'; }
    public function getSince() { return '0.10.0'; }
    public function getDependencies() { return array('magento_bootstrap', 'routers', 'controllers'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Builds a practical map from Magento router declarations to controller files and action methods.',
            'Mage router configuration and controller filesystem scan',
            'Medium'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so route/controller information is unavailable.');
            return;
        }

        $routes = $this->collectRoutes();
        $totalFiles = 0;
        $totalActions = 0;
        $missingDirectories = 0;

        foreach ($routes as $route) {
            $controllerDirectory = $this->controllerDirectory($route['module'], $route['area']);
            $files = $controllerDirectory !== '' && is_dir($controllerDirectory)
                ? $this->controllerFiles($controllerDirectory)
                : array();

            if (!is_dir($controllerDirectory)) {
                $missingDirectories++;
            }

            $section->addItem('Route map', $route['area'] . ' / ' . $route['front_name'] . ' / ' . $route['module']);
            $section->addItem('  Area', $route['area']);
            $section->addItem('  Router name', $route['router_name']);
            $section->addItem('  Front name', $route['front_name']);
            $section->addItem('  Module alias', $route['module_alias']);
            $section->addItem('  Module', $route['module']);
            $section->addItem('  Controller directory', $controllerDirectory !== '' ? $controllerDirectory : '[unknown]');
            $section->addItem('  Controller directory exists', is_dir($controllerDirectory) ? 'yes' : 'no');
            $section->addItem('  Controller files', count($files));

            $shown = 0;

            foreach ($files as $file) {
                $actions = $this->actionMethods($file);
                $totalActions += count($actions);

                if ($shown < 20) {
                    $section->addItem('  Controller file', $file);
                    $section->addItem('    Actions', $this->formatList($actions));
                }

                $shown++;
            }

            if (count($files) > 20) {
                $section->addItem('  Controller files truncated', 'Only the first 20 controller files are shown for this route.');
            }

            $totalFiles += count($files);
        }

        $section->addItem('Summary / routes mapped', count($routes));
        $section->addItem('Summary / controller files mapped', $totalFiles);
        $section->addItem('Summary / action methods found', $totalActions);
        $section->addItem('Summary / missing controller directories', $missingDirectories);
    }

    protected function collectRoutes()
    {
        $routes = array();

        foreach (array('frontend', 'adminhtml') as $area) {
            $routersNode = Mage::getConfig()->getNode($area . '/routers');

            if (!$routersNode) {
                continue;
            }

            foreach ($routersNode->children() as $routerName => $routerNode) {
                if (!$routerNode->args || !$routerNode->args->modules) {
                    continue;
                }

                $frontName = $routerNode->args->frontName
                    ? trim((string)$routerNode->args->frontName)
                    : '[none]';

                foreach ($routerNode->args->modules->children() as $moduleAlias => $moduleNode) {
                    $routes[] = array(
                        'area' => $area,
                        'router_name' => (string)$routerName,
                        'module_alias' => (string)$moduleAlias,
                        'module' => trim((string)$moduleNode),
                        'front_name' => $frontName,
                    );
                }
            }
        }

        return $routes;
    }

    protected function controllerDirectory($module, $area)
    {
        if ($module === '') {
            return '';
        }

        $parts = explode('_', $module);

        if (count($parts) < 2) {
            return '';
        }

        foreach (array('local', 'community', 'core') as $pool) {
            $directory = Mage::getBaseDir('code') . DIRECTORY_SEPARATOR . $pool
                . DIRECTORY_SEPARATOR . $parts[0] . DIRECTORY_SEPARATOR . $parts[1]
                . DIRECTORY_SEPARATOR . 'controllers';

            if (is_dir($directory)) {
                return $directory;
            }
        }

        return Mage::getBaseDir('code') . DIRECTORY_SEPARATOR . 'community'
            . DIRECTORY_SEPARATOR . $parts[0] . DIRECTORY_SEPARATOR . $parts[1]
            . DIRECTORY_SEPARATOR . 'controllers';
    }

    protected function controllerFiles($directory)
    {
        $files = array();

        if (!is_dir($directory)) {
            return $files;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'php') {
                $files[] = $fileInfo->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    protected function actionMethods($file)
    {
        $content = @file_get_contents($file);

        if ($content === false) {
            return array();
        }

        $actions = array();

        if (preg_match_all('#function\s+([A-Za-z0-9_]+Action)\s*\(#', $content, $matches)) {
            foreach ($matches[1] as $method) {
                $actions[] = $method;
            }
        }

        sort($actions);

        return $actions;
    }

    protected function formatList(array $values)
    {
        if (!count($values)) {
            return '[none]';
        }

        if (count($values) > 40) {
            $values = array_slice($values, 0, 40);
            $values[] = '[truncated]';
        }

        return implode(', ', $values);
    }
}
