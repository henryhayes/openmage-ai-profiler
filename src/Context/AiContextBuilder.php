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

        $this->extractMagento($context, $data);
        $this->extractStores($context, $data);
        $this->extractModules($context, $data);
        $this->extractThemes($context, $data);
        $this->extractRewrites($context, $data);
        $this->extractObservers($context, $data);
        $this->extractCron($context, $data);

        $this->addAiGuidance($context, $data);

        return $context;
    }

    protected function metadata(array $data, $key)
    {
        return isset($data['metadata'][$key]) ? $data['metadata'][$key] : '[unknown]';
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

        $customModules = array();

        foreach ($section['items'] as $item) {
            if (strpos($item['key'], 'Radiotronics_') === 0 || strpos($item['key'], 'HenryHayes_') === 0) {
                $customModules[] = $item['key'];
            }
        }

        if (count($customModules)) {
            $context->addItem('Custom Modules', 'Detected', implode(', ', $customModules));
        }
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

        $currentStore = null;

        foreach ($themeHierarchySection['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Store') {
                $currentStore = $value;
                continue;
            }

            if ($currentStore === null) {
                continue;
            }

            if ($key === 'Theme resolver') {
                $context->addItem('Theme Resolution', $currentStore . ' / Resolver', $value);
            }

            if ($key === 'Theme source') {
                $context->addItem('Theme Resolution', $currentStore . ' / Source', $value);
            }
            
            if ($key === 'Configured package') {
                $context->addItem('Theme Resolution', $currentStore . ' / Configured Package', $value);
            }

            if ($key === 'Effective theme') {
                $context->addItem('Theme Resolution', $currentStore . ' / Effective Theme', $value);
            }

            if ($key === 'Configured theme') {
                $context->addItem('Theme Resolution', $currentStore . ' / Configured Theme', $value);
            }
        }
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

        $context->addItem(
            'AI Guidance',
            'Rewrite work',
            'Check Rewrite Architecture before advising on model, block or helper overrides.'
        );

        $context->addItem(
            'AI Guidance',
            'Module work',
            'Check Module Architecture and Custom Modules before advising on code locations.'
        );
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