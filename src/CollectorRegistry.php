<?php

class CollectorRegistry
{
    protected $collectors = array();

    public function register(CollectorInterface $collector)
    {
        $code = $collector->getCode();

        if (isset($this->collectors[$code])) {
            throw new Exception('Collector already registered: ' . $code);
        }

        $this->collectors[$code] = $collector;
    }

    public function registerDirectory($directory)
    {
        if (!is_dir($directory)) {
            throw new Exception('Collector directory does not exist: ' . $directory);
        }

        $files = glob(rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . '*Collector.php');
        sort($files);

        foreach ($files as $file) {
            require_once $file;

            $className = basename($file, '.php');

            if (!class_exists($className, false)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if ($reflection->isAbstract()) {
                continue;
            }

            if (!$reflection->implementsInterface('CollectorInterface')) {
                continue;
            }

            $this->register(new $className());
        }
    }

    public function getCollectors()
    {
        return $this->sortByDependencies();
    }

    protected function sortByDependencies()
    {
        $sorted = array();
        $visited = array();
        $visiting = array();

        foreach ($this->getOrderedCollectorCodes() as $code) {
            $this->visitCollector($code, $sorted, $visited, $visiting);
        }

        return $sorted;
    }

    protected function getOrderedCollectorCodes()
    {
        $codes = array_keys($this->collectors);
        $weights = $this->getPreferredCollectorOrderWeights();

        usort($codes, function ($left, $right) use ($weights) {
            $leftWeight = isset($weights[$left]) ? $weights[$left] : 10000;
            $rightWeight = isset($weights[$right]) ? $weights[$right] : 10000;

            if ($leftWeight === $rightWeight) {
                return strcmp($left, $right);
            }

            return ($leftWeight < $rightWeight) ? -1 : 1;
        });

        return $codes;
    }

    protected function getPreferredCollectorOrderWeights()
    {
        $order = array(
            'environment',
            'php',
            'magento_bootstrap',
            'magento',
            'stores',
            'modules',
            'themes',
            'theme_hierarchy',
            'rewrites',
            'rewrite_map',
            'rewrite_chains',
            'observers',
            'event_dispatches',
            'cron',
            'indexes',
            'cache',
            'database',
            'database_schema',
            'eav',
            'layouts',
            'layout_graph',
            'routers',
            'controllers',
            'route_controller_map',
            'templates',
            'ai_architecture_summary',
        );

        $weights = array();
        $weight = 0;

        foreach ($order as $code) {
            $weights[$code] = $weight;
            $weight++;
        }

        return $weights;
    }

    protected function visitCollector($code, array &$sorted, array &$visited, array &$visiting)
    {
        if (isset($visited[$code])) {
            return;
        }

        if (isset($visiting[$code])) {
            throw new Exception('Circular collector dependency detected at: ' . $code);
        }

        if (!isset($this->collectors[$code])) {
            throw new Exception('Collector dependency is not registered: ' . $code);
        }

        $visiting[$code] = true;

        $dependencies = $this->collectors[$code]->getDependencies();

        foreach ($dependencies as $dependencyCode) {
            if (!isset($this->collectors[$dependencyCode])) {
                throw new Exception(
                    'Collector "' . $code . '" depends on missing collector "' . $dependencyCode . '"'
                );
            }

            $this->visitCollector($dependencyCode, $sorted, $visited, $visiting);
        }

        unset($visiting[$code]);

        $visited[$code] = true;
        $sorted[$code] = $this->collectors[$code];
    }
}
