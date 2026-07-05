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

    public function getVersion()
    {
        return '1.0.0';
    }

    public function getSince()
    {
        return '0.5.0';
    }

    public function getDescription()
    {
        return 'Reports Magento event observers across global, frontend and adminhtml areas.';
    }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            'Reports Magento event observers across global, frontend and adminhtml areas.',
            'Mage merged configuration',
            'High',
            array()
        );

        if (!$context->isMagentoBootstrapped()) {
            $section->addError('Magento is not bootstrapped.');
            $report->addSection($section);
            return;
        }

        $areas = array('global', 'frontend', 'adminhtml');

        $totalObservers = 0;
        $eventsWithObservers = 0;
        $areaCounts = array(
            'global' => 0,
            'frontend' => 0,
            'adminhtml' => 0,
        );

        $observerRows = array();

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
                    $classFileExists = $classFile !== '' && file_exists($classFile);

                    $observerRows[] = array(
                        'area' => $area,
                        'event' => (string)$eventName,
                        'observer' => (string)$observerName,
                        'class' => $class,
                        'resolved_class' => $resolvedClass,
                        'method' => $method,
                        'type' => $type,
                        'disabled' => $disabled,
                        'class_file_exists' => $classFileExists ? 'yes' : 'no',
                        'class_file' => $classFile !== '' ? $classFile : '[unknown]',
                    );
                }

                if ($eventObserverCount > 0) {
                    $eventsWithObservers++;
                }
            }
        }

        $section->addItem('Summary / total observers', $totalObservers);
        $section->addItem('Summary / events with observers', $eventsWithObservers);
        $section->addItem('Summary / global observers', $areaCounts['global']);
        $section->addItem('Summary / frontend observers', $areaCounts['frontend']);
        $section->addItem('Summary / adminhtml observers', $areaCounts['adminhtml']);

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
            $section->addItem('  Class file exists', $row['class_file_exists']);
            $section->addItem('  Class file', $row['class_file']);
        }

        $report->addSection($section);
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
        if ($class === '' || $class === '[unknown]') {
            return '';
        }

        try {
            $file = Varien_Autoload::instance()->getClassFile($class);

            if ($file) {
                return $file;
            }
        } catch (Exception $e) {
            return '';
        }

        return '';
    }
}