<?php

class ControllerContextExtractor extends AbstractAiContextExtractor
{
    public function extract(AiContext $context, array $data)
    {
        $this->extractControllers($context, $data);
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
    
}
