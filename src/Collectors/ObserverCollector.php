<?php

class ObserverCollector extends AbstractCollector
{
    public function getCode()
    {
        return 'observers';
    }

    public function getTitle()
    {
        return 'Observers';
    }

    public function getCategory()
    {
        return 'Architecture';
    }

    public function getDescription()
    {
        return 'Magento event observer declarations across global, frontend and adminhtml areas.';
    }

    public function getSince()
    {
        return '0.5.0';
    }

    public function getDependencies()
    {
        return array('magento_bootstrap', 'modules');
    }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Reports Magento event observers across global, frontend and adminhtml areas.',
            'Mage merged configuration',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so observer information is unavailable.');
            return;
        }

        $areas = array('global', 'frontend', 'adminhtml');

        $totalObservers = 0;
        $eventsWithObservers = 0;
        $missingClassFiles = 0;
        $disabledObservers = 0;
        $customObservers = 0;

        $areaCounts = array(
            'global' => 0,
            'frontend' => 0,
            'adminhtml' => 0,
        );

        $observerRows = array();
        $eventCounts = array();

        foreach ($areas as $area) {
            $eventsNode = Mage::getConfig()->getNode($area . '/events');

            if (!$eventsNode) {
                continue;
            }

            foreach ($eventsNode->children() as $eventName => $eventNode) {
                if (!$eventNode->observers) {
                    continue;
                }

                $eventObserverCount = 0;

                foreach ($eventNode->observers->children() as $observerName => $observerNode) {
                    $eventObserverCount++;
                    $totalObservers++;
                    $areaCounts[$area]++;

                    $class = $this->getObserverClass($observerNode);
                    $method = $this->getObserverMethod($observerNode);
                    $type = $this->getObserverType($observerNode);
                    $disabled = $this->getObserverDisabled($observerNode);

                    $resolvedClass = $this->resolveClassName($class);
                    $classFile = $this->classToFile($resolvedClass);
                    $classFileExists = ($classFile !== '' && file_exists($classFile)) ? 'yes' : 'no';

                    if ($classFileExists === 'no') {
                        $missingClassFiles++;
                    }

                    if ($disabled === 'yes') {
                        $disabledObservers++;
                    }

                    if ($this->isCustomClass($resolvedClass)) {
                        $customObservers++;
                    }

                    $eventKey = $area . ' / ' . (string)$eventName;

                    if (!isset($eventCounts[$eventKey])) {
                        $eventCounts[$eventKey] = 0;
                    }

                    $eventCounts[$eventKey]++;

                    $observerRows[] = array(
                        'area' => $area,
                        'event' => (string)$eventName,
                        'observer' => (string)$observerName,
                        'class' => $class,
                        'resolved_class' => $resolvedClass,
                        'method' => $method,
                        'type' => $type,
                        'disabled' => $disabled,
                        'custom' => $this->isCustomClass($resolvedClass) ? 'yes' : 'no',
                        'class_file_exists' => $classFileExists,
                        'class_file' => $classFile !== '' ? $classFile : '[unknown]',
                    );
                }

                if ($eventObserverCount > 0) {
                    $eventsWithObservers++;
                }
            }
        }

        arsort($eventCounts);

        $section->addItem('Summary / total observers', $totalObservers);
        $section->addItem('Summary / events with observers', $eventsWithObservers);
        $section->addItem('Summary / global observers', $areaCounts['global']);
        $section->addItem('Summary / frontend observers', $areaCounts['frontend']);
        $section->addItem('Summary / adminhtml observers', $areaCounts['adminhtml']);
        $section->addItem('Summary / custom observers', $customObservers);
        $section->addItem('Summary / disabled observers', $disabledObservers);
        $section->addItem('Summary / missing observer class files', $missingClassFiles);

        $top = 0;

        foreach ($eventCounts as $eventKey => $count) {
            $top++;

            if ($top > 20) {
                break;
            }

            $section->addItem('Summary / busiest event / ' . $top, $eventKey . ' / observers=' . $count);
        }

        foreach ($observerRows as $row) {
            $section->addItem(
                'Observer',
                $row['area'] . ' / ' . $row['event'] . ' / ' . $row['observer']
            );

            $section->addItem('  Area', $row['area']);
            $section->addItem('  Event', $row['event']);
            $section->addItem('  Observer name', $row['observer']);
            $section->addItem('  Class', $row['class']);
            $section->addItem('  Resolved class', $row['resolved_class']);
            $section->addItem('  Method', $row['method']);
            $section->addItem('  Type', $row['type']);
            $section->addItem('  Disabled', $row['disabled']);
            $section->addItem('  Custom', $row['custom']);
            $section->addItem('  Class file exists', $row['class_file_exists']);
            $section->addItem('  Class file', $row['class_file']);
        }
    }

    protected function getObserverClass($observerNode)
    {
        if ($observerNode->class) {
            return trim((string)$observerNode->class);
        }

        if ($observerNode->model) {
            return trim((string)$observerNode->model);
        }

        return '[none]';
    }

    protected function getObserverMethod($observerNode)
    {
        if ($observerNode->method) {
            return trim((string)$observerNode->method);
        }

        return '[none]';
    }

    protected function getObserverType($observerNode)
    {
        if ($observerNode->type) {
            return trim((string)$observerNode->type);
        }

        return '[default]';
    }

    protected function getObserverDisabled($observerNode)
    {
        if ($observerNode->disabled) {
            $value = strtolower(trim((string)$observerNode->disabled));

            if ($value === 'true' || $value === '1') {
                return 'yes';
            }
        }

        return 'no';
    }

    protected function resolveClassName($class)
    {
        if ($class === '' || $class === '[none]') {
            return '[unknown]';
        }

        if (strpos($class, '/') === false) {
            return $class;
        }

        try {
            return Mage::getConfig()->getModelClassName($class);
        } catch (Exception $e) {
            return '[unknown]';
        }
    }

    protected function classToFile($class)
    {
        if ($class === '' || $class === '[unknown]' || $class === '[none]') {
            return '';
        }

        $relativeFile = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';

        $locations = array(
            Mage::getBaseDir('code') . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . $relativeFile,
            Mage::getBaseDir('code') . DIRECTORY_SEPARATOR . 'community' . DIRECTORY_SEPARATOR . $relativeFile,
            Mage::getBaseDir('code') . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . $relativeFile,
            Mage::getBaseDir('lib') . DIRECTORY_SEPARATOR . $relativeFile,
        );

        foreach ($locations as $file) {
            if (file_exists($file)) {
                return $file;
            }
        }

        return '';
    }

    protected function isCustomClass($class)
    {
        if ($class === '' || $class === '[unknown]' || $class === '[none]') {
            return false;
        }

        $customPrefixes = array(
            'Radiotronics_',
            'HenryHayes_',
            'Symbix_',
            'OpenSearch',
            'Ethan_',
            'Tvcom_',
            'Tvmenu_',
        );

        foreach ($customPrefixes as $prefix) {
            if (strpos($class, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }
}