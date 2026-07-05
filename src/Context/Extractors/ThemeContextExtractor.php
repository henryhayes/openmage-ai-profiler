<?php

class ThemeContextExtractor extends AbstractAiContextExtractor
{
    public function extract(AiContext $context, array $data)
    {
        $this->extractThemes($context, $data);
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

        $stores = $this->parseThemeHierarchyRows($themeHierarchySection);
        $packages = $this->parseThemePackageRows($themesSection);

        $this->addThemeResolutionSummary($context, $stores);
        $this->addThemeActiveStoreMap($context, $stores);
        $this->addThemeInactiveStoreMap($context, $stores);
        $this->addThemeFallbackChains($context, $stores);
        $this->addThemeResolverUsage($context, $stores);
        $this->addThemeSourceUsage($context, $stores);
        $this->addThemePackageUsage($context, $packages);
        $this->addThemeUnusedPackages($context, $packages);
        $this->addThemeXmlLayoutUpdates($context, $stores);
        $this->addThemeCssConventionHighlights($context, $stores);
    }
    
    protected function parseThemeHierarchyRows(array $section)
    {
        $stores = array();
        $currentStore = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Store') {
                if ($currentStore !== null) {
                    $stores[] = $currentStore;
                }

                $currentStore = array(
                    'store' => $value,
                    'active' => '',
                    'resolver' => '',
                    'source' => '',
                    'configured_package' => '',
                    'configured_theme' => '',
                    'effective_package' => '',
                    'effective_theme' => '',
                    'fallback_chain' => '',
                    'layout_theme_used' => '',
                    'template_theme_used' => '',
                    'skin_theme_used' => '',
                    'layout_fallbacks' => array(),
                    'template_fallbacks' => array(),
                    'skin_fallbacks' => array(),
                    'theme_xml' => array(),
                    'theme_xml_layout_updates' => array(),
                    'css_found' => array(),
                    'css_missing' => array(),
                );

                continue;
            }

            if ($currentStore === null) {
                continue;
            }

            if ($key === 'Active') {
                $currentStore['active'] = $value;
            } elseif ($key === 'Theme resolver') {
                $currentStore['resolver'] = $value;
            } elseif ($key === 'Theme source') {
                $currentStore['source'] = $value;
            } elseif ($key === 'Configured package') {
                $currentStore['configured_package'] = $value;
            } elseif ($key === 'Configured theme') {
                $currentStore['configured_theme'] = $value;
            } elseif ($key === 'Effective package') {
                $currentStore['effective_package'] = $value;
            } elseif ($key === 'Effective theme') {
                $currentStore['effective_theme'] = $value;
            } elseif ($key === 'Fallback chain') {
                $currentStore['fallback_chain'] = $value;
            } elseif ($key === 'Layout theme used') {
                $currentStore['layout_theme_used'] = $value;
            } elseif ($key === 'Template theme used') {
                $currentStore['template_theme_used'] = $value;
            } elseif ($key === 'Skin theme used') {
                $currentStore['skin_theme_used'] = $value;
            } elseif (strpos($key, 'Layout fallback / ') === 0) {
                $currentStore['layout_fallbacks'][] = $value;
            } elseif (strpos($key, 'Template fallback / ') === 0) {
                $currentStore['template_fallbacks'][] = $value;
            } elseif (strpos($key, 'Skin fallback / ') === 0) {
                $currentStore['skin_fallbacks'][] = $value;
            } elseif (strpos($key, 'theme.xml / ') === 0) {
                $currentStore['theme_xml'][] = str_replace('theme.xml / ', '', $key) . ': ' . $value;
            } elseif (strpos($key, 'theme.xml layout update / ') === 0) {
                $currentStore['theme_xml_layout_updates'][] = str_replace('theme.xml layout update / ', '', $key) . ': ' . $value;
            } elseif (strpos($key, 'theme.xml layout updates / ') === 0) {
                if ($value !== '[none]') {
                    $currentStore['theme_xml_layout_updates'][] = str_replace('theme.xml layout updates / ', '', $key) . ': ' . $value;
                }
            } elseif (strpos($key, 'CSS convention / ') === 0) {
                if ($value === 'found') {
                    $currentStore['css_found'][] = str_replace('CSS convention / ', '', $key);
                } elseif ($value === 'missing') {
                    $currentStore['css_missing'][] = str_replace('CSS convention / ', '', $key);
                }
            }
        }

        if ($currentStore !== null) {
            $stores[] = $currentStore;
        }

        return $stores;
    }

    protected function parseThemePackageRows(array $section)
    {
        $packages = array();
        $currentPackage = null;
        $currentTheme = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Package') {
                if ($currentTheme !== null && $currentPackage !== null) {
                    $currentPackage['themes'][] = $currentTheme;
                    $currentTheme = null;
                }

                if ($currentPackage !== null) {
                    $packages[] = $currentPackage;
                }

                $currentPackage = array(
                    'package' => $value,
                    'design_path' => '',
                    'skin_path' => '',
                    'used_by_store_config' => '',
                    'design_package_exists' => '',
                    'skin_package_exists' => '',
                    'themes' => array(),
                );

                continue;
            }

            if ($currentPackage === null) {
                continue;
            }

            if ($key === 'Theme') {
                if ($currentTheme !== null) {
                    $currentPackage['themes'][] = $currentTheme;
                }

                $currentTheme = array(
                    'theme' => $value,
                    'design_path' => '',
                    'skin_path' => '',
                    'layout_xml_files' => '',
                    'template_phtml_files' => '',
                    'locale_csv_files' => '',
                    'theme_xml_files' => '',
                    'css_files' => '',
                    'js_files' => '',
                    'image_files' => '',
                    'design_size' => '',
                    'skin_size' => '',
                );

                continue;
            }

            if ($currentTheme !== null) {
                if ($key === 'Design path') {
                    $currentTheme['design_path'] = $value;
                } elseif ($key === 'Skin path') {
                    $currentTheme['skin_path'] = $value;
                } elseif ($key === 'Layout XML files') {
                    $currentTheme['layout_xml_files'] = $value;
                } elseif ($key === 'Template PHTML files') {
                    $currentTheme['template_phtml_files'] = $value;
                } elseif ($key === 'Locale CSV files') {
                    $currentTheme['locale_csv_files'] = $value;
                } elseif ($key === 'Theme XML files') {
                    $currentTheme['theme_xml_files'] = $value;
                } elseif ($key === 'CSS files') {
                    $currentTheme['css_files'] = $value;
                } elseif ($key === 'JS files') {
                    $currentTheme['js_files'] = $value;
                } elseif ($key === 'Image files') {
                    $currentTheme['image_files'] = $value;
                } elseif ($key === 'Design size') {
                    $currentTheme['design_size'] = $value;
                } elseif ($key === 'Skin size') {
                    $currentTheme['skin_size'] = $value;
                }

                continue;
            }

            if ($key === 'Design path') {
                $currentPackage['design_path'] = $value;
            } elseif ($key === 'Skin path') {
                $currentPackage['skin_path'] = $value;
            } elseif ($key === 'Used by store config') {
                $currentPackage['used_by_store_config'] = $value;
            } elseif ($key === 'Design package exists') {
                $currentPackage['design_package_exists'] = $value;
            } elseif ($key === 'Skin package exists') {
                $currentPackage['skin_package_exists'] = $value;
            }
        }

        if ($currentTheme !== null && $currentPackage !== null) {
            $currentPackage['themes'][] = $currentTheme;
        }

        if ($currentPackage !== null) {
            $packages[] = $currentPackage;
        }

        return $packages;
    }

    protected function addThemeResolutionSummary(AiContext $context, array $stores)
    {
        foreach ($stores as $store) {
            $context->addItem('Theme Resolution', $store['store'] . ' / Resolver', $store['resolver']);
            $context->addItem('Theme Resolution', $store['store'] . ' / Source', $store['source']);
            $context->addItem('Theme Resolution', $store['store'] . ' / Configured Package', $store['configured_package']);
            $context->addItem('Theme Resolution', $store['store'] . ' / Configured Theme', $store['configured_theme']);
            $context->addItem('Theme Resolution', $store['store'] . ' / Effective Theme', $store['effective_theme']);
        }
    }

    protected function addThemeActiveStoreMap(AiContext $context, array $stores)
    {
        foreach ($stores as $store) {
            if ($store['active'] !== 'yes') {
                continue;
            }

            $context->addItem(
                'Theme Active Store Map',
                $store['store'],
                'effective=' . $store['effective_theme']
                . '; package=' . $store['effective_package']
                . '; source=' . $store['source']
                . '; configuredPackage=' . $store['configured_package']
                . '; configuredTheme=' . $store['configured_theme']
                . '; resolver=' . $store['resolver']
            );
        }
    }

    protected function addThemeInactiveStoreMap(AiContext $context, array $stores)
    {
        $count = 0;
        $limit = 30;

        foreach ($stores as $store) {
            if ($store['active'] !== 'no') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Theme Inactive Store Map',
                    $store['store'],
                    'effective=' . $store['effective_theme']
                    . '; package=' . $store['effective_package']
                    . '; source=' . $store['source']
                    . '; configuredPackage=' . $store['configured_package']
                    . '; configuredTheme=' . $store['configured_theme']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Theme Inactive Store Map',
                'Truncated',
                'Only the first ' . $limit . ' inactive store theme mappings are shown in this short AI context. See full profile for all theme hierarchy data.'
            );
        }
    }

    protected function addThemeFallbackChains(AiContext $context, array $stores)
    {
        foreach ($stores as $store) {
            if ($store['active'] !== 'yes') {
                continue;
            }

            $context->addItem(
                'Theme Active Store Fallback Chains',
                $store['store'],
                'effective=' . $store['effective_theme']
                . '; fallback=' . $store['fallback_chain']
                . '; layoutTheme=' . $store['layout_theme_used']
                . '; templateTheme=' . $store['template_theme_used']
                . '; skinTheme=' . $store['skin_theme_used']
            );
        }
    }

    protected function addThemeResolverUsage(AiContext $context, array $stores)
    {
        $usage = array();

        foreach ($stores as $store) {
            $resolver = $store['resolver'];

            if ($resolver === '') {
                $resolver = '[unknown]';
            }

            if (!isset($usage[$resolver])) {
                $usage[$resolver] = array(
                    'active' => 0,
                    'inactive' => 0,
                    'total' => 0,
                );
            }

            $usage[$resolver]['total']++;

            if ($store['active'] === 'yes') {
                $usage[$resolver]['active']++;
            } else {
                $usage[$resolver]['inactive']++;
            }
        }

        ksort($usage);

        foreach ($usage as $resolver => $counts) {
            $context->addItem(
                'Theme Resolver Usage',
                $resolver,
                'activeStores=' . $counts['active']
                . '; inactiveStores=' . $counts['inactive']
                . '; totalStores=' . $counts['total']
            );
        }
    }

    protected function addThemeSourceUsage(AiContext $context, array $stores)
    {
        $usage = array();

        foreach ($stores as $store) {
            $source = $store['source'];

            if ($source === '') {
                $source = '[unknown]';
            }

            if (!isset($usage[$source])) {
                $usage[$source] = array(
                    'active' => 0,
                    'inactive' => 0,
                    'total' => 0,
                );
            }

            $usage[$source]['total']++;

            if ($store['active'] === 'yes') {
                $usage[$source]['active']++;
            } else {
                $usage[$source]['inactive']++;
            }
        }

        ksort($usage);

        foreach ($usage as $source => $counts) {
            $context->addItem(
                'Theme Source Usage',
                $source,
                'activeStores=' . $counts['active']
                . '; inactiveStores=' . $counts['inactive']
                . '; totalStores=' . $counts['total']
            );
        }
    }

    protected function addThemePackageUsage(AiContext $context, array $packages)
    {
        foreach ($packages as $package) {
            $context->addItem(
                'Theme Package Usage',
                $package['package'],
                'usedByStoreConfig=' . $package['used_by_store_config']
                . '; designExists=' . $package['design_package_exists']
                . '; skinExists=' . $package['skin_package_exists']
                . '; themes=' . count($package['themes'])
            );
        }
    }

    protected function addThemeUnusedPackages(AiContext $context, array $packages)
    {
        $count = 0;
        $limit = 40;

        foreach ($packages as $package) {
            if ($package['used_by_store_config'] !== 'no') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Theme Packages Not Used By Store Config',
                    $package['package'],
                    'designExists=' . $package['design_package_exists']
                    . '; skinExists=' . $package['skin_package_exists']
                    . '; themes=' . count($package['themes'])
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Theme Packages Not Used By Store Config',
                'Truncated',
                'Only the first ' . $limit . ' unused design packages are shown in this short AI context. See full profile for all theme data.'
            );
        }
    }

    protected function addThemeXmlLayoutUpdates(AiContext $context, array $stores)
    {
        $seen = array();
        $count = 0;
        $limit = 60;

        foreach ($stores as $store) {
            if ($store['active'] !== 'yes') {
                continue;
            }

            foreach ($store['theme_xml_layout_updates'] as $layoutUpdate) {
                if (isset($seen[$layoutUpdate])) {
                    continue;
                }

                $seen[$layoutUpdate] = true;
                $count++;

                if ($count <= $limit) {
                    $context->addItem(
                        'Theme XML Layout Updates',
                        $store['store'],
                        $layoutUpdate
                    );
                }
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Theme XML Layout Updates',
                'Truncated',
                'Only the first ' . $limit . ' active-store theme.xml layout updates are shown in this short AI context. See full profile for all theme hierarchy data.'
            );
        }
    }

    protected function addThemeCssConventionHighlights(AiContext $context, array $stores)
    {
        foreach ($stores as $store) {
            if ($store['active'] !== 'yes') {
                continue;
            }

            $found = $this->summariseThemeConventionFiles($store['css_found'], 12);
            $missing = $this->summariseThemeConventionFiles($store['css_missing'], 12);

            $context->addItem(
                'Theme CSS Convention Files',
                $store['store'],
                'found=' . $found . '; missing=' . $missing
            );
        }
    }

    protected function summariseThemeConventionFiles(array $files, $limit)
    {
        if (!count($files)) {
            return '[none]';
        }

        $files = array_values(array_unique($files));

        if (count($files) <= $limit) {
            return implode(', ', $files);
        }

        return implode(', ', array_slice($files, 0, $limit)) . ', ...';
    }

}
