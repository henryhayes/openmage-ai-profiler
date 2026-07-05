<?php

class RouterContextExtractor extends AbstractAiContextExtractor
{
    public function extract(AiContext $context, array $data)
    {
        $this->extractRouters($context, $data);
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
    
}
