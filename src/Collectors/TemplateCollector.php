<?php

class TemplateCollector extends AbstractCollector
{
    public function getCode() { return 'templates'; }
    public function getTitle() { return 'Templates'; }
    public function getCategory() { return 'Architecture'; }
    public function getDescription() { return 'Reports frontend PHTML templates, child block usage and template references.'; }
    public function getSince() { return '0.10.0'; }
    public function getDependencies() { return array('themes', 'theme_hierarchy'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Reports PHTML templates and common Magento template relationships such as getChildHtml(), include and setTemplate().',
            'Filesystem scan of app/design/frontend template directories',
            'Medium'
        );

        $frontend = $context->getResourceLocator()->frontendDesign();

        if (!is_dir($frontend)) {
            $section->addError('Frontend design directory does not exist.');
            return;
        }

        $rows = $this->scanTemplates($frontend);
        $packageCounts = array();
        $totalGetChildHtml = 0;
        $totalSetTemplate = 0;
        $totalIncludes = 0;
        $highImpact = array();

        foreach ($rows as $row) {
            $packageTheme = $row['package'] . '/' . $row['theme'];

            if (!isset($packageCounts[$packageTheme])) {
                $packageCounts[$packageTheme] = 0;
            }

            $packageCounts[$packageTheme]++;

            $totalGetChildHtml += $row['get_child_html'];
            $totalSetTemplate += $row['set_template'];
            $totalIncludes += $row['includes'];

            if ($row['score'] > 0) {
                $highImpact[] = $row;
            }
        }

        arsort($packageCounts);
        usort($highImpact, array($this, 'sortRowsByScore'));

        foreach ($packageCounts as $packageTheme => $count) {
            $section->addItem('Template theme count', $packageTheme);
            $section->addItem('  PHTML files', $count);
        }

        $shown = 0;

        foreach ($highImpact as $row) {
            if ($shown >= 120) {
                break;
            }

            $section->addItem('Template file', $row['relative']);
            $section->addItem('  Package', $row['package']);
            $section->addItem('  Theme', $row['theme']);
            $section->addItem('  Path', $row['path']);
            $section->addItem('  getChildHtml calls', $row['get_child_html']);
            $section->addItem('  setTemplate calls', $row['set_template']);
            $section->addItem('  include/require calls', $row['includes']);
            $section->addItem('  Referenced child aliases', $this->formatList($row['child_aliases']));
            $section->addItem('  Referenced templates', $this->formatList($row['templates']));
            $shown++;
        }

        if (count($highImpact) > 120) {
            $section->addItem('Template files truncated', 'Only the first 120 relationship-heavy templates are shown.');
        }

        $section->addItem('Summary / PHTML files', count($rows));
        $section->addItem('Summary / package themes', count($packageCounts));
        $section->addItem('Summary / templates with relationships', count($highImpact));
        $section->addItem('Summary / getChildHtml calls', $totalGetChildHtml);
        $section->addItem('Summary / setTemplate calls', $totalSetTemplate);
        $section->addItem('Summary / include require calls', $totalIncludes);
    }

    protected function scanTemplates($frontend)
    {
        $rows = array();

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($frontend, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile() || strtolower($fileInfo->getExtension()) !== 'phtml') {
                continue;
            }

            if (strpos($fileInfo->getPath(), DIRECTORY_SEPARATOR . 'template') === false) {
                continue;
            }

            $path = $fileInfo->getPathname();
            $relative = str_replace(rtrim($frontend, '/\\') . DIRECTORY_SEPARATOR, '', $path);
            $parts = explode(DIRECTORY_SEPARATOR, $relative);
            $package = isset($parts[0]) ? $parts[0] : '[unknown]';
            $theme = isset($parts[1]) ? $parts[1] : '[unknown]';

            $content = @file_get_contents($path);

            if ($content === false) {
                $content = '';
            }

            $childAliases = $this->matchQuotedArguments($content, 'getChildHtml');
            $templates = array_merge(
                $this->matchQuotedArguments($content, 'setTemplate'),
                $this->matchTemplateStrings($content)
            );

            $getChildHtml = substr_count($content, 'getChildHtml(');
            $setTemplate = substr_count($content, 'setTemplate(');
            $includes = substr_count($content, 'include ') + substr_count($content, 'include(')
                + substr_count($content, 'require ') + substr_count($content, 'require(');

            $rows[] = array(
                'package' => $package,
                'theme' => $theme,
                'relative' => $relative,
                'path' => $path,
                'get_child_html' => $getChildHtml,
                'set_template' => $setTemplate,
                'includes' => $includes,
                'child_aliases' => $childAliases,
                'templates' => $templates,
                'score' => $getChildHtml + $setTemplate + $includes + count($childAliases) + count($templates),
            );
        }

        return $rows;
    }

    protected function matchQuotedArguments($content, $method)
    {
        $result = array();
        $pattern = '#' . preg_quote($method, '#') . '\s*\(\s*[\'"]([^\'"]*)[\'"]#';

        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $value) {
                if ($value !== '') {
                    $result[$value] = true;
                }
            }
        }

        return array_keys($result);
    }

    protected function matchTemplateStrings($content)
    {
        $result = array();

        if (preg_match_all('#[\'"]([a-zA-Z0-9_\-/]+\.phtml)[\'"]#', $content, $matches)) {
            foreach ($matches[1] as $value) {
                $result[$value] = true;
            }
        }

        return array_keys($result);
    }

    protected function sortRowsByScore($left, $right)
    {
        if ($left['score'] === $right['score']) {
            return strcmp($left['relative'], $right['relative']);
        }

        return ($left['score'] > $right['score']) ? -1 : 1;
    }

    protected function formatList(array $values)
    {
        if (!count($values)) {
            return '[none]';
        }

        sort($values);

        if (count($values) > 20) {
            $values = array_slice($values, 0, 20);
            $values[] = '[truncated]';
        }

        return implode(', ', $values);
    }
}
