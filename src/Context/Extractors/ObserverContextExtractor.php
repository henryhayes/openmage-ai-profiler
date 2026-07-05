<?php

class ObserverContextExtractor extends AbstractAiContextExtractor
{
    public function extract(AiContext $context, array $data)
    {
        $this->extractObservers($context, $data);
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
    
}
