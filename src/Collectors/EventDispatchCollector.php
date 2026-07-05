<?php

class EventDispatchCollector extends AbstractCollector
{
    public function getCode() { return 'event_dispatches'; }
    public function getTitle() { return 'Event Dispatches'; }
    public function getCategory() { return 'Architecture'; }
    public function getDescription() { return 'Reports Mage::dispatchEvent() calls and observer coverage.'; }
    public function getSince() { return '0.10.0'; }
    public function getDependencies() { return array('magento_bootstrap', 'observers'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Maps event dispatch points found in PHP source code to configured Magento observers.',
            'Filesystem PHP scan and Mage merged event configuration',
            'Medium'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so event dispatch information is unavailable.');
            return;
        }

        $dispatches = $this->scanDispatches($context->getMagentoRoot());
        $observers = $this->collectObservers();

        ksort($dispatches);

        $eventsWithObservers = 0;
        $eventsWithoutObservers = 0;
        $totalDispatches = 0;

        foreach ($dispatches as $eventName => $rows) {
            $totalDispatches += count($rows);
            $observerCount = isset($observers[$eventName]) ? count($observers[$eventName]) : 0;

            if ($observerCount > 0) {
                $eventsWithObservers++;
            } else {
                $eventsWithoutObservers++;
            }

            $section->addItem('Dispatched event', $eventName);
            $section->addItem('  Dispatch points', count($rows));
            $section->addItem('  Observer count', $observerCount);
            $section->addItem('  Observers', $this->formatObservers(isset($observers[$eventName]) ? $observers[$eventName] : array()));

            $shown = 0;

            foreach ($rows as $row) {
                if ($shown >= 8) {
                    break;
                }

                $section->addItem('  Dispatch file', $row['file']);
                $section->addItem('    Line', $row['line']);
                $shown++;
            }

            if (count($rows) > 8) {
                $section->addItem('  Dispatch files truncated', 'Only the first 8 dispatch points are shown for this event.');
            }
        }

        $section->addItem('Summary / dispatched events', count($dispatches));
        $section->addItem('Summary / dispatch calls', $totalDispatches);
        $section->addItem('Summary / dispatched events with observers', $eventsWithObservers);
        $section->addItem('Summary / dispatched events without observers', $eventsWithoutObservers);
    }

    protected function scanDispatches($root)
    {
        $result = array();
        $paths = array(
            $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'code',
            $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'design',
        );

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile()) {
                    continue;
                }

                $extension = strtolower($fileInfo->getExtension());

                if ($extension !== 'php' && $extension !== 'phtml') {
                    continue;
                }

                $content = @file_get_contents($fileInfo->getPathname());

                if ($content === false || strpos($content, 'dispatchEvent') === false) {
                    continue;
                }

                if (preg_match_all('#dispatchEvent\s*\(\s*[\'"]([^\'"]+)[\'"]#', $content, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[1] as $match) {
                        $eventName = $match[0];
                        $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;

                        if (!isset($result[$eventName])) {
                            $result[$eventName] = array();
                        }

                        $result[$eventName][] = array(
                            'file' => $fileInfo->getPathname(),
                            'line' => $line,
                        );
                    }
                }
            }
        }

        return $result;
    }

    protected function collectObservers()
    {
        $result = array();

        foreach (array('global', 'frontend', 'adminhtml') as $area) {
            $eventsNode = Mage::getConfig()->getNode($area . '/events');

            if (!$eventsNode) {
                continue;
            }

            foreach ($eventsNode->children() as $eventName => $eventNode) {
                if (!$eventNode->observers) {
                    continue;
                }

                foreach ($eventNode->observers->children() as $observerName => $observerNode) {
                    if (!isset($result[(string)$eventName])) {
                        $result[(string)$eventName] = array();
                    }

                    $result[(string)$eventName][] = $area . '/' . (string)$observerName . '/' . $this->observerClass($observerNode);
                }
            }
        }

        return $result;
    }

    protected function observerClass($observerNode)
    {
        if ($observerNode->class) {
            return trim((string)$observerNode->class);
        }

        if ($observerNode->model) {
            return trim((string)$observerNode->model);
        }

        return '[unknown]';
    }

    protected function formatObservers(array $observers)
    {
        if (!count($observers)) {
            return '[none]';
        }

        sort($observers);

        if (count($observers) > 20) {
            $observers = array_slice($observers, 0, 20);
            $observers[] = '[truncated]';
        }

        return implode(', ', $observers);
    }
}
