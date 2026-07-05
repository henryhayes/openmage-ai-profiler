<?php

class LayoutContextExtractor extends AbstractAiContextExtractor
{
    public function extract(AiContext $context, array $data)
    {
        $this->extractLayouts($context, $data);
    }
    protected function extractLayouts(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'layouts');

        if (!$section) {
            return;
        }

        $keys = array(
            'Summary / declared module layout files',
            'Summary / frontend theme layout files',
            'Summary / total layout files',
        );

        foreach ($keys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('Layout Architecture', str_replace('Summary / ', '', $key), $value);
            }
        }

        $this->extractLayoutHighlights($context, $section, $data);
    }

    protected function extractLayoutHighlights(AiContext $context, array $section, array $data)
    {
        $rows = $this->parseLayoutRows($section);
        $activeThemes = $this->getActiveStoreLayoutThemes($data);

        $this->addLayoutPackageThemeCounts($context, $rows['theme_files']);
        $this->addLayoutMissingDeclaredFiles($context, $rows['declared_files']);
        $this->addLayoutCustomModuleFiles($context, $rows['declared_files']);
        $this->addLayoutActiveThemeFiles($context, $rows['theme_files'], $activeThemes);
        $this->addLayoutDuplicateThemeFiles($context, $rows['theme_files']);
    }

    protected function parseLayoutRows(array $section)
    {
        $declaredFiles = array();
        $themeFiles = array();
        $currentDeclaredFile = null;
        $currentThemeFile = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Declared layout file') {
                if ($currentDeclaredFile !== null) {
                    $declaredFiles[] = $currentDeclaredFile;
                }

                if ($currentThemeFile !== null) {
                    $themeFiles[] = $currentThemeFile;
                    $currentThemeFile = null;
                }

                $currentDeclaredFile = array(
                    'label' => $value,
                    'area' => '',
                    'module' => '',
                    'file' => '',
                    'path' => '',
                    'exists' => '',
                );

                continue;
            }

            if ($key === 'Theme layout file') {
                if ($currentDeclaredFile !== null) {
                    $declaredFiles[] = $currentDeclaredFile;
                    $currentDeclaredFile = null;
                }

                if ($currentThemeFile !== null) {
                    $themeFiles[] = $currentThemeFile;
                }

                $currentThemeFile = array(
                    'label' => $value,
                    'package' => '',
                    'theme' => '',
                    'package_theme' => '',
                    'file' => '',
                    'path' => '',
                );

                continue;
            }

            if ($currentDeclaredFile !== null) {
                if ($key === 'Area') {
                    $currentDeclaredFile['area'] = $value;
                } elseif ($key === 'Module') {
                    $currentDeclaredFile['module'] = $value;
                } elseif ($key === 'File') {
                    $currentDeclaredFile['file'] = $value;
                } elseif ($key === 'Path') {
                    $currentDeclaredFile['path'] = $value;
                } elseif ($key === 'Exists') {
                    $currentDeclaredFile['exists'] = $value;
                }

                continue;
            }

            if ($currentThemeFile !== null) {
                if ($key === 'Package') {
                    $currentThemeFile['package'] = $value;
                    $currentThemeFile['package_theme'] = $this->joinPackageTheme(
                        $currentThemeFile['package'],
                        $currentThemeFile['theme']
                    );
                } elseif ($key === 'Theme') {
                    $currentThemeFile['theme'] = $value;
                    $currentThemeFile['package_theme'] = $this->joinPackageTheme(
                        $currentThemeFile['package'],
                        $currentThemeFile['theme']
                    );
                } elseif ($key === 'File') {
                    $currentThemeFile['file'] = $value;
                } elseif ($key === 'Path') {
                    $currentThemeFile['path'] = $value;
                }
            }
        }

        if ($currentDeclaredFile !== null) {
            $declaredFiles[] = $currentDeclaredFile;
        }

        if ($currentThemeFile !== null) {
            $themeFiles[] = $currentThemeFile;
        }

        return array(
            'declared_files' => $declaredFiles,
            'theme_files' => $themeFiles,
        );
    }

    protected function getActiveStoreLayoutThemes(array $data)
    {
        $section = $this->findSection($data, 'theme_hierarchy');
        $themes = array();

        if (!$section) {
            return $themes;
        }

        $storeActive = false;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Store') {
                $storeActive = false;
                continue;
            }

            if ($key === 'Active') {
                $storeActive = ($value === 'yes');
                continue;
            }

            if (!$storeActive) {
                continue;
            }

            if ($key === 'Effective theme') {
                if ($value !== '' && $value !== '[unknown]') {
                    $themes[$value] = true;
                }

                continue;
            }

            if (strpos($key, 'Layout fallback / ') === 0) {
                $theme = $this->extractThemeNameFromFallbackValue($value);

                if ($theme !== '') {
                    $themes[$theme] = true;
                }
            }
        }

        return $themes;
    }

    protected function extractThemeNameFromFallbackValue($value)
    {
        $parts = explode(';', $value);
        $theme = trim($parts[0]);

        if ($theme === '' || $theme === '[unknown]') {
            return '';
        }

        return $theme;
    }

    protected function joinPackageTheme($package, $theme)
    {
        if ($package === '' || $theme === '') {
            return '';
        }

        return $package . '/' . $theme;
    }

    protected function addLayoutPackageThemeCounts(AiContext $context, array $themeFiles)
    {
        $counts = array();

        foreach ($themeFiles as $row) {
            if ($row['package_theme'] === '') {
                continue;
            }

            if (!isset($counts[$row['package_theme']])) {
                $counts[$row['package_theme']] = 0;
            }

            $counts[$row['package_theme']]++;
        }

        arsort($counts);

        $count = 0;
        $limit = 30;

        foreach ($counts as $packageTheme => $fileCount) {
            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Layout Theme File Counts',
                    $packageTheme,
                    $fileCount
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Layout Theme File Counts',
                'Truncated',
                'Only the first ' . $limit . ' package/theme layout file counts are shown in this short AI context. See full profile for all layout files.'
            );
        }
    }

    protected function addLayoutMissingDeclaredFiles(AiContext $context, array $declaredFiles)
    {
        $count = 0;
        $limit = 40;

        foreach ($declaredFiles as $row) {
            if ($row['exists'] !== 'no') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Layout Missing Declared Files',
                    $row['label'],
                    'area=' . $row['area']
                    . '; module=' . $row['module']
                    . '; file=' . $row['file']
                    . '; path=' . $row['path']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Layout Missing Declared Files',
                'Truncated',
                'Only the first ' . $limit . ' missing declared layout files are shown in this short AI context. See full profile for all layout data.'
            );
        }
    }

    protected function addLayoutCustomModuleFiles(AiContext $context, array $declaredFiles)
    {
        $count = 0;
        $limit = 80;

        foreach ($declaredFiles as $row) {
            if (!$this->isCustomModuleName($row['module'])) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Layout Custom Module Files',
                    $row['label'],
                    'area=' . $row['area']
                    . '; module=' . $row['module']
                    . '; file=' . $row['file']
                    . '; exists=' . $row['exists']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Layout Custom Module Files',
                'Truncated',
                'Only the first ' . $limit . ' custom module layout files are shown in this short AI context. See full profile for all layout data.'
            );
        }
    }

    protected function addLayoutActiveThemeFiles(AiContext $context, array $themeFiles, array $activeThemes)
    {
        $count = 0;
        $limit = 100;

        foreach ($themeFiles as $row) {
            if ($row['package_theme'] === '') {
                continue;
            }

            if (!isset($activeThemes[$row['package_theme']])) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Layout Active Store Theme Files',
                    $row['package_theme'] . ' / ' . $row['file'],
                    'path=' . $row['path']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Layout Active Store Theme Files',
                'Truncated',
                'Only the first ' . $limit . ' active store theme layout files are shown in this short AI context. See full profile for all layout data.'
            );
        }
    }

    protected function addLayoutDuplicateThemeFiles(AiContext $context, array $themeFiles)
    {
        $byFile = array();

        foreach ($themeFiles as $row) {
            if ($row['file'] === '') {
                continue;
            }

            if (!isset($byFile[$row['file']])) {
                $byFile[$row['file']] = array();
            }

            $byFile[$row['file']][] = $row['package_theme'];
        }

        ksort($byFile);

        $count = 0;
        $limit = 60;

        foreach ($byFile as $file => $packageThemes) {
            $packageThemes = array_values(array_unique($packageThemes));

            if (count($packageThemes) < 2) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Layout Duplicate Theme Filenames',
                    $file,
                    implode(', ', $packageThemes)
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Layout Duplicate Theme Filenames',
                'Truncated',
                'Only the first ' . $limit . ' duplicate theme layout filenames are shown in this short AI context. See full profile for all layout data.'
            );
        }
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
