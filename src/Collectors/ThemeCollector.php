<?php

class ThemeCollector extends AbstractCollector
{
    public function getCode()
    {
        return 'themes';
    }

    public function getTitle()
    {
        return 'Themes';
    }

    public function getDescription()
    {
        return 'Frontend design package and theme filesystem inventory.';
    }

    public function getSince()
    {
        return '0.3.0';
    }

    public function getDependencies()
    {
        return array('magento_bootstrap', 'stores');
    }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Reports frontend design packages, themes, filesystem presence and basic file counts.',
            'Mage design configuration and filesystem scan',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so theme information is unavailable.');
            return;
        }

        $filesystem = new Filesystem();
        $locator = $context->getResourceLocator();

        $designFrontend = $locator->frontendDesign();
        $skinFrontend = $locator->frontendSkin();

        $section->addItem('Design frontend path', $designFrontend);
        $section->addItem('Skin frontend path', $skinFrontend);
        $section->addItem('Design frontend exists', $filesystem->directoryExists($designFrontend) ? 'yes' : 'no');
        $section->addItem('Skin frontend exists', $filesystem->directoryExists($skinFrontend) ? 'yes' : 'no');

        $usedPackages = array();

        try {
            foreach (Mage::app()->getStores() as $store) {
                $storeId = $store->getId();

                $package = Mage::getStoreConfig('design/package/name', $storeId);
                $themeDefault = Mage::getStoreConfig('design/theme/default', $storeId);
                $themeTemplate = Mage::getStoreConfig('design/theme/template', $storeId);
                $themeSkin = Mage::getStoreConfig('design/theme/skin', $storeId);
                $themeLayout = Mage::getStoreConfig('design/theme/layout', $storeId);

                if ($package === '') {
                    $package = 'default';
                }

                if ($themeDefault === '') {
                    $themeDefault = 'default';
                }

                $usedPackages[$package] = true;

                $section->addItem(
                    'Store theme / ' . $store->getCode(),
                    'package=' . $package
                    . '; default=' . $this->emptyValue($themeDefault)
                    . '; template=' . $this->emptyValue($themeTemplate)
                    . '; layout=' . $this->emptyValue($themeLayout)
                    . '; skin=' . $this->emptyValue($themeSkin)
                );
            }
        } catch (Exception $e) {
            $section->addError('Unable to read store theme configuration: ' . $e->getMessage());
        }

        $packages = $filesystem->listDirectories($designFrontend);

        $section->addItem('Summary / design packages found', count($packages));
        $section->addItem('Summary / design packages used by stores', count($usedPackages));

        foreach ($packages as $package) {
            $packagePath = $designFrontend . DIRECTORY_SEPARATOR . $package;
            $skinPackagePath = $skinFrontend . DIRECTORY_SEPARATOR . $package;

            $section->addItem('Package', $package);
            $section->addItem('  Design path', $packagePath);
            $section->addItem('  Skin path', $skinPackagePath);
            $section->addItem('  Used by store config', isset($usedPackages[$package]) ? 'yes' : 'no');
            $section->addItem('  Design package exists', $filesystem->directoryExists($packagePath) ? 'yes' : 'no');
            $section->addItem('  Skin package exists', $filesystem->directoryExists($skinPackagePath) ? 'yes' : 'no');

            $themes = $filesystem->listDirectories($packagePath);

            foreach ($themes as $theme) {
                $themePath = $packagePath . DIRECTORY_SEPARATOR . $theme;
                $skinThemePath = $skinPackagePath . DIRECTORY_SEPARATOR . $theme;

                $layoutPath = $themePath . DIRECTORY_SEPARATOR . 'layout';
                $templatePath = $themePath . DIRECTORY_SEPARATOR . 'template';
                $localePath = $themePath . DIRECTORY_SEPARATOR . 'locale';
                $etcPath = $themePath . DIRECTORY_SEPARATOR . 'etc';

                $section->addItem('  Theme', $package . '/' . $theme);
                $section->addItem('    Design path', $themePath);
                $section->addItem('    Skin path', $skinThemePath);
                $section->addItem('    Layout XML files', $filesystem->countFiles($layoutPath, array('xml')));
                $section->addItem('    Template PHTML files', $filesystem->countFiles($templatePath, array('phtml')));
                $section->addItem('    Locale CSV files', $filesystem->countFiles($localePath, array('csv')));
                $section->addItem('    Theme XML files', $filesystem->countFiles($etcPath, array('xml')));
                $section->addItem('    CSS files', $filesystem->countFiles($skinThemePath, array('css')));
                $section->addItem('    JS files', $filesystem->countFiles($skinThemePath, array('js')));
                $section->addItem('    Image files', $filesystem->countFiles($skinThemePath, array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg')));
                $section->addItem('    Design size', $filesystem->formatBytes($filesystem->directorySize($themePath)));
                $section->addItem('    Skin size', $filesystem->formatBytes($filesystem->directorySize($skinThemePath)));
            }
        }

        foreach (array_keys($usedPackages) as $usedPackage) {
            $packagePath = $designFrontend . DIRECTORY_SEPARATOR . $usedPackage;

            if (!$filesystem->directoryExists($packagePath)) {
                $section->addError('Configured design package does not exist on disk: ' . $usedPackage);
            }
        }
    }

    protected function emptyValue($value)
    {
        return $value !== '' ? $value : '[empty]';
    }
}