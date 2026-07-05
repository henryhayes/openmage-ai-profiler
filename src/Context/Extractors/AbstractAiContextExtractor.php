<?php

abstract class AbstractAiContextExtractor implements AiContextExtractorInterface
{
    protected function metadata(array $data, $key)
    {
        return isset($data['metadata'][$key]) ? $data['metadata'][$key] : '[unknown]';
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
}
