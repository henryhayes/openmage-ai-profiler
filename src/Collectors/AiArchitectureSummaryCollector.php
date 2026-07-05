<?php

class AiArchitectureSummaryCollector extends AbstractCollector
{
    public function getCode() { return 'ai_architecture_summary'; }
    public function getTitle() { return 'AI Architecture Summary'; }
    public function getCategory() { return 'AI'; }
    public function getDescription() { return 'Summarises profiler coverage, architectural risks and AI-context completeness.'; }
    public function getSince() { return '0.11.0'; }

    public function getDependencies()
    {
        return array(
            'environment',
            'php',
            'magento',
            'stores',
            'modules',
            'themes',
            'theme_hierarchy',
            'rewrites',
            'rewrite_map',
            'rewrite_chains',
            'module_dependency_graph',
            'observers',
            'event_dispatches',
            'cron',
            'indexes',
            'cache',
            'database',
            'database_schema',
            'eav',
            'xml_merge_analysis',
            'layouts',
            'layout_graph',
            'routers',
            'controllers',
            'route_controller_map',
            'templates',
            'theme_fallback_map',
            'code_complexity',
            'dead_code',
            'code_relationship_index',
            'ai_index',
            'search_tags',
            'risk_report',
        );
    }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Provides a final AI-oriented capability score and high-level architectural risk summary based on all previous collectors.',
            'Profiler report sections collected during this run',
            'Medium'
        );

        $data = $report->toArray();
        $sections = $this->sectionsByCode($data);
        $score = $this->calculateScore($sections);

        $section->addItem('AI context score', $score . ' / 100');
        $section->addItem('Collector sections', count($data['sections']));
        $section->addItem('Collector errors', $this->countErrors($data));
        $section->addItem('Coverage / Magento runtime', $this->hasAll($sections, array('magento_bootstrap', 'magento', 'stores')) ? 'yes' : 'no');
        $section->addItem('Coverage / module architecture', $this->hasAll($sections, array('modules', 'module_dependency_graph', 'rewrites', 'rewrite_map', 'rewrite_chains', 'observers', 'event_dispatches')) ? 'yes' : 'no');
        $section->addItem('Coverage / frontend architecture', $this->hasAll($sections, array('themes', 'theme_hierarchy', 'theme_fallback_map', 'xml_merge_analysis', 'layouts', 'layout_graph', 'templates')) ? 'yes' : 'no');
        $section->addItem('Coverage / routing architecture', $this->hasAll($sections, array('routers', 'controllers', 'route_controller_map')) ? 'yes' : 'no');
        $section->addItem('Coverage / data architecture', $this->hasAll($sections, array('database', 'database_schema', 'eav')) ? 'yes' : 'no');
        $section->addItem('Coverage / operations', $this->hasAll($sections, array('cache', 'indexes', 'cron')) ? 'yes' : 'no');
        $section->addItem('Coverage / AI navigation', $this->hasAll($sections, array('code_relationship_index', 'ai_index', 'search_tags', 'risk_report')) ? 'yes' : 'no');

        $this->addMetric($section, $sections, 'modules', 'Summary / total modules', 'Total modules');
        $this->addMetric($section, $sections, 'modules', 'Summary / active modules', 'Active modules');
        $this->addMetric($section, $sections, 'modules', 'Summary / rewrites declared in config.xml', 'Declared rewrites');
        $this->addMetric($section, $sections, 'rewrite_chains', 'Summary / conflicting aliases', 'Rewrite conflicts');
        $this->addMetric($section, $sections, 'modules', 'Summary / observers declared in config.xml', 'Declared observers');
        $this->addMetric($section, $sections, 'event_dispatches', 'Summary / dispatched events', 'Dispatched events');
        $this->addMetric($section, $sections, 'layout_graph', 'Summary / layout handles', 'Layout handles');
        $this->addMetric($section, $sections, 'templates', 'Summary / PHTML files', 'PHTML templates');
        $this->addMetric($section, $sections, 'database_schema', 'Summary / likely custom tables', 'Likely custom database tables');
        $this->addMetric($section, $sections, 'module_dependency_graph', 'Summary / missing dependencies', 'Missing module dependencies');
        $this->addMetric($section, $sections, 'code_complexity', 'Summary / complex methods >=20', 'Complex methods');
        $this->addMetric($section, $sections, 'dead_code', 'Summary / active modules with missing paths', 'Active modules with missing paths');
        $this->addMetric($section, $sections, 'risk_report', 'Summary / risk count', 'Risk count');

        $risks = $this->detectRisks($sections);

        foreach ($risks as $risk) {
            $section->addItem('Risk', $risk);
        }

        if (!count($risks)) {
            $section->addItem('Risk', '[none detected by summary rules]');
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

    protected function calculateScore(array $sections)
    {
        $expected = array(
            'environment',
            'php',
            'magento_bootstrap',
            'magento',
            'stores',
            'modules',
            'themes',
            'theme_hierarchy',
            'rewrites',
            'rewrite_map',
            'rewrite_chains',
            'module_dependency_graph',
            'observers',
            'event_dispatches',
            'cron',
            'indexes',
            'cache',
            'database',
            'database_schema',
            'eav',
            'xml_merge_analysis',
            'layouts',
            'layout_graph',
            'routers',
            'controllers',
            'route_controller_map',
            'templates',
            'theme_fallback_map',
            'code_complexity',
            'dead_code',
            'code_relationship_index',
            'ai_index',
            'search_tags',
            'risk_report',
        );

        $present = 0;

        foreach ($expected as $code) {
            if (isset($sections[$code])) {
                $present++;
            }
        }

        return (int)round(($present / count($expected)) * 100);
    }

    protected function countErrors(array $data)
    {
        $count = 0;

        foreach ($data['sections'] as $section) {
            if (isset($section['errors']) && is_array($section['errors'])) {
                $count += count($section['errors']);
            }
        }

        return $count;
    }

    protected function hasAll(array $sections, array $codes)
    {
        foreach ($codes as $code) {
            if (!isset($sections[$code])) {
                return false;
            }
        }

        return true;
    }

    protected function addMetric(Section $section, array $sections, $collectorCode, $key, $label)
    {
        if (!isset($sections[$collectorCode])) {
            return;
        }

        foreach ($sections[$collectorCode]['items'] as $item) {
            if ($item['key'] === $key) {
                $section->addItem('Metric / ' . $label, $item['value']);
                return;
            }
        }
    }

    protected function detectRisks(array $sections)
    {
        $risks = array();

        $rewriteConflicts = $this->item($sections, 'rewrite_chains', 'Summary / conflicting aliases');

        if ((int)$rewriteConflicts > 0) {
            $risks[] = 'Rewrite conflicts detected. Inspect Rewrite Chains before advising on model/block/helper overrides.';
        }

        $missingPaths = $this->countItemsByKey($sections, 'modules', 'Tvcom_Blockcustom');

        if ($missingPaths > 0) {
            $risks[] = 'Some modules appear active while filesystem paths are missing. Inspect Module Missing Paths.';
        }

        $customTables = $this->item($sections, 'database_schema', 'Summary / likely custom tables');

        if ((int)$customTables > 0) {
            $risks[] = 'Custom database tables exist. Inspect Database Schema before writing setup/resource advice.';
        }

        $layoutParseErrors = $this->item($sections, 'layout_graph', 'Summary / layout parse errors');

        if ((int)$layoutParseErrors > 0) {
            $risks[] = 'Layout XML parse errors detected. Inspect Layout Graph before frontend/layout changes.';
        }

        $observerCount = $this->item($sections, 'modules', 'Summary / observers declared in config.xml');

        if ((int)$observerCount > 250) {
            $risks[] = 'Large observer surface area. Inspect Observer Architecture and Event Dispatches before changing save/order/product workflows.';
        }

        $complexMethods = $this->item($sections, 'code_complexity', 'Summary / complex methods >=20');

        if ((int)$complexMethods > 0) {
            $risks[] = 'Complex custom PHP methods detected. Inspect Code Complexity before editing large helpers, models or controllers.';
        }

        $missingDependencies = $this->item($sections, 'module_dependency_graph', 'Summary / missing dependencies');

        if ((int)$missingDependencies > 0) {
            $risks[] = 'Missing declared module dependencies detected. Inspect Module Dependency Graph before enabling/disabling modules.';
        }

        return $risks;
    }

    protected function item(array $sections, $collectorCode, $key)
    {
        if (!isset($sections[$collectorCode])) {
            return '[unknown]';
        }

        foreach ($sections[$collectorCode]['items'] as $item) {
            if ($item['key'] === $key) {
                return $item['value'];
            }
        }

        return '[unknown]';
    }

    protected function countItemsByKey(array $sections, $collectorCode, $key)
    {
        if (!isset($sections[$collectorCode])) {
            return 0;
        }

        $count = 0;

        foreach ($sections[$collectorCode]['items'] as $item) {
            if ($item['key'] === $key) {
                $count++;
            }
        }

        return $count;
    }
}
