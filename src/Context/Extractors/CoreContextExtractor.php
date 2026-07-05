<?php

class CoreContextExtractor extends AbstractAiContextExtractor
{
    public function extract(AiContext $context, array $data)
    {
        $context->addItem('Overview', 'Tool', $this->metadata($data, 'Tool'));
        $context->addItem('Overview', 'Tool Version', $this->metadata($data, 'Tool Version'));
        $context->addItem('Overview', 'Generated', $this->metadata($data, 'Generated'));

        $this->extractEnvironment($context, $data);
        $this->extractPhp($context, $data);
        $this->extractMagento($context, $data);
        $this->extractStores($context, $data);
    }
    protected function metadata(array $data, $key)
    {
        return isset($data['metadata'][$key]) ? $data['metadata'][$key] : '[unknown]';
    }

    protected function extractEnvironment(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'environment');

        if (!$section) {
            return;
        }

        $keys = array(
            'Project root',
            'Magento root',
            'Working directory',
            'Current user',
            'Default timezone',
        );

        foreach ($keys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('Environment', $key, $value);
            }
        }
    }

    protected function extractPhp(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'php');

        if (!$section) {
            return;
        }

        $keys = array(
            'PHP version',
            'PHP SAPI',
            'Memory limit',
            'Max execution time',
            'Upload max filesize',
            'Post max size',
            'Max input vars',
            'OPcache loaded',
            'Xdebug loaded',
            'APCu loaded',
            'Redis extension loaded',
            'ionCube loaded',
        );

        foreach ($keys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('PHP Runtime', $key, $value);
            }
        }

        $extensions = $this->item($section, 'Loaded extensions');

        if ($extensions !== '[unknown]') {
            $context->addItem('PHP Runtime', 'Loaded extensions', $this->summariseExtensions($extensions));
        }
    }

    protected function summariseExtensions($extensions)
    {
        $importantExtensions = array(
            'bcmath',
            'curl',
            'dom',
            'gd',
            'iconv',
            'intl',
            'json',
            'mbstring',
            'mcrypt',
            'mysqli',
            'mysql',
            'openssl',
            'pdo_mysql',
            'SimpleXML',
            'soap',
            'xml',
            'xmlreader',
            'xmlwriter',
            'xsl',
            'zip',
            'Zend OPcache',
            'xdebug',
        );

        $loaded = array();
        $extensionMap = array();
        $extensions = explode(',', $extensions);

        foreach ($extensions as $extension) {
            $extension = trim($extension);

            if ($extension === '') {
                continue;
            }

            $extensionMap[strtolower($extension)] = $extension;
        }

        foreach ($importantExtensions as $extension) {
            $key = strtolower($extension);

            if (isset($extensionMap[$key])) {
                $loaded[] = $extensionMap[$key];
            }
        }

        if (!count($loaded)) {
            return '[none of the common Magento-related extensions were detected]';
        }

        return implode(', ', $loaded);
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

}
