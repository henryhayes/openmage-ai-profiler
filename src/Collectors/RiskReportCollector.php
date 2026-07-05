<?php

class RiskReportCollector extends AbstractCollector
{
    public function getCode() { return 'risk_report'; }
    public function getTitle() { return 'Risk Report'; }
    public function getCategory() { return 'AI'; }
    public function getDescription() { return 'Produces an opinionated risk report from profiler metrics.'; }
    public function getSince() { return '0.11.0'; }
    public function getDependencies() { return array('modules', 'rewrite_chains', 'observers', 'event_dispatches', 'database_schema', 'layout_graph', 'code_complexity', 'dead_code'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Provides an opinionated risk map for AI assistants before recommending Magento/OpenMage changes.',
            'Rule-based analysis over collected profiler sections',
            'Medium'
        );

        $sections = $this->sectionsByCode($report->toArray());
        $risks = array();

        $this->addRiskIf($risks, (int)$this->item($sections, 'modules', 'Summary / total modules') > 100, 'High module count', 'Large module surface area; check dependencies, rewrites and observers before changing behaviour.');
        $this->addRiskIf($risks, (int)$this->item($sections, 'modules', 'Summary / rewrites declared in config.xml') > 40, 'Rewrite-heavy installation', 'Core class replacement risk is high; inspect Rewrite Chains.');
        $this->addRiskIf($risks, (int)$this->item($sections, 'rewrite_chains', 'Summary / conflicting aliases') > 0, 'Rewrite conflicts', 'Multiple modules rewrite the same alias; winning class may not be obvious.');
        $this->addRiskIf($risks, (int)$this->item($sections, 'modules', 'Summary / observers declared in config.xml') > 300, 'Observer-heavy installation', 'Event-driven side effects likely; inspect Observer and Event Dispatch sections.');
        $this->addRiskIf($risks, (int)$this->item($sections, 'database_schema', 'Summary / likely custom tables') > 0, 'Custom database schema', 'Setup scripts and data migrations need extra caution.');
        $this->addRiskIf($risks, (int)$this->item($sections, 'code_complexity', 'Summary / complex methods >=20') > 0, 'Complex PHP methods', 'Some methods have high approximate complexity; inspect Code Complexity before editing.');
        $this->addRiskIf($risks, (int)$this->item($sections, 'dead_code', 'Summary / active modules with missing paths') > 0, 'Active modules with missing paths', 'Magento config declares active modules whose code is absent.');
        $this->addRiskIf($risks, (int)$this->item($sections, 'layout_graph', 'Summary / layout parse errors') > 0, 'Layout parse errors', 'Frontend layout behaviour may be incomplete or broken.');

        foreach ($risks as $risk) {
            $section->addItem($risk['level'] . ' risk', $risk['title']);
            $section->addItem('  Guidance', $risk['guidance']);
        }

        if (!count($risks)) {
            $section->addItem('Risk', '[none detected by risk rules]');
        }

        $section->addItem('Summary / risk count', count($risks));
    }

    protected function addRiskIf(array &$risks, $condition, $title, $guidance)
    {
        if ($condition) {
            $risks[] = array('level' => 'High', 'title' => $title, 'guidance' => $guidance);
        }
    }

    protected function sectionsByCode(array $data)
    {
        $result = array();
        foreach ($data['sections'] as $section) {
            if (isset($section['collector_code'])) {
                $result[$section['collector_code']] = $section;
            }
        }
        return $result;
    }

    protected function item(array $sections, $collectorCode, $key)
    {
        if (!isset($sections[$collectorCode])) {
            return 0;
        }
        foreach ($sections[$collectorCode]['items'] as $item) {
            if ($item['key'] === $key) {
                return $item['value'];
            }
        }
        return 0;
    }
}
