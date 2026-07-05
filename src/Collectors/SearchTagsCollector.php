<?php

class SearchTagsCollector extends AbstractCollector
{
    public function getCode() { return 'search_tags'; }
    public function getTitle() { return 'Search Tags'; }
    public function getCategory() { return 'AI'; }
    public function getDescription() { return 'Generates retrieval-friendly tags from modules, routes, observers and theme artefacts.'; }
    public function getSince() { return '0.11.0'; }
    public function getDependencies() { return array('modules', 'routers', 'controllers', 'observers', 'templates'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Creates compact search tags to improve AI retrieval and orientation over a large Magento profile.',
            'Profiler report keyword frequency extraction',
            'Medium'
        );

        $words = array();
        $data = strtolower($this->flattenReport($report->toArray()));

        if (preg_match_all('#\b[a-z][a-z0-9_]{2,}\b#', $data, $matches)) {
            foreach ($matches[0] as $word) {
                if ($this->isStopWord($word)) {
                    continue;
                }
                if (!isset($words[$word])) {
                    $words[$word] = 0;
                }
                $words[$word]++;
            }
        }

        arsort($words);
        $shown = 0;
        foreach ($words as $word => $count) {
            if ($shown >= 120) {
                break;
            }
            $section->addItem('Search tag', $word . ' / ' . $count);
            $shown++;
        }

        $section->addItem('Summary / tags generated', count($words));
        $section->addItem('Summary / tags shown', $shown);
    }

    protected function flattenReport(array $data)
    {
        $parts = array();
        foreach ($data['sections'] as $section) {
            foreach ($section['items'] as $item) {
                $parts[] = (string)$item['key'];
                $parts[] = (string)$item['value'];
            }
        }
        return implode("\n", $parts);
    }

    protected function isStopWord($word)
    {
        $stop = array('yes', 'none', 'path', 'file', 'files', 'active', 'version', 'codepool', 'exists', 'controllers', 'models', 'blocks', 'helpers', 'summary', 'data', 'core', 'community', 'local', 'unknown', 'frontend');
        return in_array($word, $stop, true) || is_numeric($word);
    }
}
