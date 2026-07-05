<?php

class ThemeResolver
{
    public function resolveStore(Mage_Core_Model_Store $store, ProfilerContext $context)
    {
        $storeId = $store->getId();

        $configuredPackage = $this->getRawConfig('design/package/name', 'stores', $storeId);
        $configuredDefaultTheme = $this->getRawConfig('design/theme/default', 'stores', $storeId);

        $effectivePackage = Mage::getStoreConfig('design/package/name', $storeId);
        $effectiveDefaultTheme = Mage::getStoreConfig('design/theme/default', $storeId);

        if ($effectivePackage === '') {
            $effectivePackage = 'default';
        }

        if ($effectiveDefaultTheme === '') {
            $effectiveDefaultTheme = 'default';
        }

        return array(
            'store_id' => $store->getId(),
            'store_code' => $store->getCode(),
            'store_name' => $store->getName(),
            'is_active' => (bool)$store->getIsActive(),

            'configured_package' => $configuredPackage !== null ? $configuredPackage : '[inherit]',
            'configured_theme' => $configuredDefaultTheme !== null ? $configuredDefaultTheme : '[inherit]',

            'effective_package' => $effectivePackage,
            'effective_theme' => $effectiveDefaultTheme,
            'effective_theme_path' => $effectivePackage . '/' . $effectiveDefaultTheme,

            'source' => $this->detectSource($configuredPackage, $configuredDefaultTheme),
            'resolver' => $this->detectThemeResolver(),
            'fallback_chain' => $this->getFallbackChain($effectivePackage, $effectiveDefaultTheme),
        );
    }

    protected function getRawConfig($path, $scope, $scopeId)
    {
        try {
            $collection = Mage::getModel('core/config_data')->getCollection();
            $collection->addFieldToFilter('path', $path);
            $collection->addFieldToFilter('scope', $scope);
            $collection->addFieldToFilter('scope_id', $scopeId);
            $collection->setPageSize(1);

            $item = $collection->getFirstItem();

            if (!$item || !$item->getId()) {
                return null;
            }

            return (string)$item->getValue();
        } catch (Exception $e) {
            return null;
        }
    }

    protected function detectSource($configuredPackage, $configuredDefaultTheme)
    {
        if ($configuredPackage !== null || $configuredDefaultTheme !== null) {
            return 'Explicit store configuration';
        }

        return 'Inherited configuration';
    }

    protected function detectThemeResolver()
    {
        if (class_exists('Radiotronics_Theme_Observer_Themefallback', false)
            || class_exists('Radiotronics_Theme_Observer_ThemeFallback', false)
        ) {
            return 'Radiotronics_Theme_Observer_ThemeFallback';
        }

        try {
            $events = Mage::getConfig()->getNode('global/events');

            if ($events) {
                foreach ($events->children() as $eventNode) {
                    if (!$eventNode->observers) {
                        continue;
                    }

                    foreach ($eventNode->observers->children() as $observerNode) {
                        $class = '';

                        if ($observerNode->class) {
                            $class = (string)$observerNode->class;
                        } elseif ($observerNode->model) {
                            $class = (string)$observerNode->model;
                        }

                        if (stripos($class, 'Radiotronics_Theme_Observer_Theme') !== false) {
                            return 'Radiotronics_Theme_Observer_ThemeFallback';
                        }
                    }
                }
            }
        } catch (Exception $e) {
            return 'Magento core design fallback';
        }

        return 'Magento core design fallback';
    }

    protected function getFallbackChain($package, $theme)
    {
        $result = array();

        try {
            $fallback = Mage::getModel('core/design_fallback');
            $scheme = $fallback->getFallbackScheme('frontend', $package, $theme);

            foreach ($scheme as $item) {
                if (!isset($item['_package']) || !isset($item['_theme'])) {
                    continue;
                }

                $key = $item['_package'] . '/' . $item['_theme'];

                if (!in_array($key, $result, true)) {
                    $result[] = $key;
                }
            }
        } catch (Exception $e) {
            $result[] = $package . '/' . $theme;

            if ($package !== 'base' || $theme !== 'default') {
                $result[] = 'base/default';
            }
        }

        return $result;
    }
}
