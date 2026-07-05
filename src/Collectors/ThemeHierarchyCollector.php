<?php

class ThemeHierarchyCollector extends AbstractCollector
{
    public function getCode()
    {
        return 'theme_hierarchy';
    }

    public function getTitle()
    {
        return 'Theme Hierarchy';
    }

    public function getCategory()
    {
        return 'Architecture';
    }
    
    public function getDescription()
    {
        return 'Theme fallback hierarchy, theme.xml layout updates and shared CSS pattern detection.';
    }

    public function getSince()
    {
        return '0.3.0';
    }

    public function getDependencies()
    {
        return array('magento_bootstrap', 'stores', 'themes');
    }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Reports per-store frontend theme fallback chains, theme.xml files and known shared CSS convention files.',
            'Mage design fallback, core/design_config, app/design/frontend and skin/frontend',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so theme hierarchy information is unavailable.');
            return;
        }

        $filesystem = $context->getFilesystem();
        $locator = $context->getResourceLocator();

        try {
            foreach (Mage::app()->getStores() as $store) {
                $storeId = $store->getId();

                $resolvedTheme = $context->getThemeResolver()->resolveStore($store, $context);

                $package = $resolvedTheme['effective_package'];
                $themeDefault = $resolvedTheme['effective_theme'];

                $themeLayout = Mage::getStoreConfig('design/theme/layout', $storeId);
                $themeTemplate = Mage::getStoreConfig('design/theme/template', $storeId);
                $themeSkin = Mage::getStoreConfig('design/theme/skin', $storeId);

                if ($themeLayout === '') {
                    $themeLayout = $themeDefault;
                }

                if ($themeTemplate === '') {
                    $themeTemplate = $themeDefault;
                }

                if ($themeSkin === '') {
                    $themeSkin = $themeDefault;
                }

                $resolvedLayoutTheme = $themeLayout;
                $resolvedTemplateTheme = $themeTemplate;
                $resolvedSkinTheme = $themeSkin;

                $section->addItem(
                    'Store',
                    $storeId . ' / ' . $store->getCode() . ' / ' . $store->getName()
                );

                $section->addItem('  Active', $resolvedTheme['is_active'] ? 'yes' : 'no');
                $section->addItem('  Theme resolver', $resolvedTheme['resolver']);
                $section->addItem('  Theme source', $resolvedTheme['source']);
                $section->addItem('  Configured package', $resolvedTheme['configured_package']);
                $section->addItem('  Configured theme', $resolvedTheme['configured_theme']);
                $section->addItem('  Effective package', $resolvedTheme['effective_package']);
                $section->addItem('  Effective theme', $resolvedTheme['effective_theme_path']);

                if (count($resolvedTheme['fallback_chain'])) {
                    $section->addItem('  Fallback chain', implode(' > ', $resolvedTheme['fallback_chain']));
                }

                $section->addItem('  Layout theme used', $resolvedLayoutTheme);
                $section->addItem('  Template theme used', $resolvedTemplateTheme);
                $section->addItem('  Skin theme used', $resolvedSkinTheme);
                
                $layoutFallbacks = $this->getFallbacks('frontend', $package, $resolvedLayoutTheme);
                $templateFallbacks = $this->getFallbacks('frontend', $package, $resolvedTemplateTheme);
                $skinFallbacks = $this->getFallbacks('frontend', $package, $resolvedSkinTheme);

                $this->addDesignFallbackSection(
                    $section,
                    $filesystem,
                    $locator,
                    '  Layout fallback',
                    $layoutFallbacks,
                    'layout'
                );

                $this->addDesignFallbackSection(
                    $section,
                    $filesystem,
                    $locator,
                    '  Template fallback',
                    $templateFallbacks,
                    'template'
                );

                $this->addSkinFallbackSection(
                    $section,
                    $filesystem,
                    $locator,
                    '  Skin fallback',
                    $skinFallbacks
                );

                $this->addThemeXmlUpdates(
                    $section,
                    $filesystem,
                    $locator,
                    $layoutFallbacks
                );

                $this->addCssConventionFiles(
                    $section,
                    $filesystem,
                    $locator,
                    $skinFallbacks,
                    $package,
                    $store->getCode()
                );
            }
        } catch (Exception $e) {
            $section->addError($e->getMessage());
        }
    }

    protected function getFallbacks($area, $package, $theme)
    {
        $result = array();

        try {
            $fallback = Mage::getModel('core/design_fallback');
            $scheme = $fallback->getFallbackScheme($area, $package, $theme);

            foreach ($scheme as $item) {
                if (!isset($item['_package']) || !isset($item['_theme'])) {
                    continue;
                }

                $result[] = array(
                    'package' => $item['_package'],
                    'theme' => $item['_theme'],
                );
            }
        } catch (Exception $e) {
            $result[] = array(
                'package' => $package,
                'theme' => $theme,
            );

            if (!($package === 'base' && $theme === 'default')) {
                $result[] = array(
                    'package' => 'base',
                    'theme' => 'default',
                );
            }
        }

        return $this->normaliseFallbacks($result);
    }

    protected function normaliseFallbacks(array $fallbacks)
    {
        $seen = array();
        $result = array();

        foreach ($fallbacks as $fallback) {
            $key = $fallback['package'] . '/' . $fallback['theme'];

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = $fallback;
        }

        return $result;
    }

    protected function addDesignFallbackSection(
        Section $section,
        Filesystem $filesystem,
        ResourceLocator $locator,
        $label,
        array $fallbacks,
        $subdirectory
    ) {
        $position = 1;

        foreach ($fallbacks as $fallback) {
            $path = $locator->frontendDesign()
                . DIRECTORY_SEPARATOR . $fallback['package']
                . DIRECTORY_SEPARATOR . $fallback['theme'];

            $subPath = $path . DIRECTORY_SEPARATOR . $subdirectory;

            $section->addItem(
                $label . ' / ' . $position,
                $fallback['package'] . '/' . $fallback['theme']
                . '; themePath=' . $path
                . '; exists=' . ($filesystem->directoryExists($path) ? 'yes' : 'no')
                . '; ' . $subdirectory . 'Exists=' . ($filesystem->directoryExists($subPath) ? 'yes' : 'no')
            );

            $position++;
        }
    }

    protected function addSkinFallbackSection(
        Section $section,
        Filesystem $filesystem,
        ResourceLocator $locator,
        $label,
        array $fallbacks
    ) {
        $position = 1;

        foreach ($fallbacks as $fallback) {
            $path = $locator->frontendSkin()
                . DIRECTORY_SEPARATOR . $fallback['package']
                . DIRECTORY_SEPARATOR . $fallback['theme'];

            $section->addItem(
                $label . ' / ' . $position,
                $fallback['package'] . '/' . $fallback['theme']
                . '; skinPath=' . $path
                . '; exists=' . ($filesystem->directoryExists($path) ? 'yes' : 'no')
            );

            $position++;
        }
    }

    protected function addThemeXmlUpdates(
        Section $section,
        Filesystem $filesystem,
        ResourceLocator $locator,
        array $fallbacks
    ) {
        foreach ($fallbacks as $fallback) {
            $themeXml = $locator->frontendDesign()
                . DIRECTORY_SEPARATOR . $fallback['package']
                . DIRECTORY_SEPARATOR . $fallback['theme']
                . DIRECTORY_SEPARATOR . 'etc'
                . DIRECTORY_SEPARATOR . 'theme.xml';

            $themeKey = $fallback['package'] . '/' . $fallback['theme'];

            $section->addItem(
                '  theme.xml / ' . $themeKey,
                $themeXml . '; exists=' . ($filesystem->fileExists($themeXml) ? 'yes' : 'no')
            );

            $updates = Mage::getSingleton('core/design_config')->getNode(
                'frontend/' . $fallback['package'] . '/' . $fallback['theme'] . '/layout/updates'
            );

            if (!$updates) {
                $section->addItem('  theme.xml layout updates / ' . $themeKey, '[none]');
                continue;
            }

            foreach ($updates as $updateGroup) {
                $updateGroupArray = $updateGroup->asArray();

                foreach ($updateGroupArray as $key => $themeUpdate) {
                    if (isset($themeUpdate['file'])) {
                        $section->addItem(
                            '  theme.xml layout update / ' . $themeKey . ' / ' . $key,
                            $themeUpdate['file']
                        );
                    }
                }
            }
        }
    }

    protected function addCssConventionFiles(
        Section $section,
        Filesystem $filesystem,
        ResourceLocator $locator,
        array $fallbacks,
        $package,
        $storeCode
    ) {
        $candidates = array(
            'css/shared/2-variables.css',
            'css/shared/5-helpers.css',
            'css/shared/7-styles.css',
            'css/2-variables.css',
            'css/5-helpers.css',
            'css/7-styles.css',
            'css/' . $package . '.css',
            'css/' . $storeCode . '.css',
        );

        $candidates = array_values(array_unique($candidates));

        foreach ($fallbacks as $fallback) {
            $skinPath = $locator->frontendSkin()
                . DIRECTORY_SEPARATOR . $fallback['package']
                . DIRECTORY_SEPARATOR . $fallback['theme'];

            foreach ($candidates as $relativeFile) {
                $file = $skinPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeFile);

                $section->addItem(
                    '  CSS convention / ' . $fallback['package'] . '/' . $fallback['theme'] . ' / ' . $relativeFile,
                    $filesystem->fileExists($file) ? 'found' : 'missing'
                );
            }
        }
    }
}