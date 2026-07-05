<?php

class AdvancedArchitectureContextExtractor extends AbstractAiContextExtractor
{
    public function extract(AiContext $context, array $data)
    {
        $this->extractRewriteChains($context, $data);
        $this->extractLayoutGraph($context, $data);
        $this->extractTemplates($context, $data);
        $this->extractEventDispatches($context, $data);
        $this->extractRouteControllerMap($context, $data);
        $this->extractDatabaseSchema($context, $data);
        $this->extractModuleDependencyGraph($context, $data);
        $this->extractXmlMergeAnalysis($context, $data);
        $this->extractThemeFallbackMap($context, $data);
        $this->extractCodeComplexity($context, $data);
        $this->extractDeadCode($context, $data);
        $this->extractCodeRelationshipIndex($context, $data);
        $this->extractAiIndex($context, $data);
        $this->extractSearchTags($context, $data);
        $this->extractRiskReport($context, $data);
        $this->extractAiSummary($context, $data);
    }

    protected function extractRewriteChains(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'rewrite_chains');

        if (!$section) {
            return;
        }

        $this->addSummaryItems($context, $section, 'Rewrite Chains', array(
            'Summary / rewrite aliases',
            'Summary / rewrite declarations',
            'Summary / conflicting aliases',
            'Summary / missing declared class files',
            'Summary / missing winning class files',
        ));

        $shown = 0;

        foreach ($section['items'] as $item) {
            if ($item['key'] === 'Rewrite chain') {
                if ($shown >= 30) {
                    break;
                }

                $context->addItem('Rewrite Chain Highlights', 'Rewrite chain', $item['value']);
                $shown++;
            }
        }

        if ($shown >= 30) {
            $context->addItem('Rewrite Chain Highlights', 'Truncated', 'Only the first 30 rewrite chains are shown in compact context. See full profile.');
        }
    }

    protected function extractLayoutGraph(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'layout_graph');

        if (!$section) {
            return;
        }

        $this->addSummaryItems($context, $section, 'Layout Graph', array(
            'Summary / layout files parsed',
            'Summary / layout parse errors',
            'Summary / layout handles',
            'Summary / handle declarations',
            'Summary / block declarations',
            'Summary / reference declarations',
            'Summary / action declarations',
            'Summary / template declarations',
        ));

        $shown = 0;

        foreach ($section['items'] as $item) {
            if ($item['key'] === 'Layout handle') {
                if ($shown >= 40) {
                    break;
                }

                $context->addItem('Layout Handle Highlights', 'Handle', $item['value']);
                $shown++;
            }
        }
    }

    protected function extractTemplates(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'templates');

        if (!$section) {
            return;
        }

        $this->addSummaryItems($context, $section, 'Template Architecture', array(
            'Summary / PHTML files',
            'Summary / package themes',
            'Summary / templates with relationships',
            'Summary / getChildHtml calls',
            'Summary / setTemplate calls',
            'Summary / include require calls',
        ));

        $shown = 0;

        foreach ($section['items'] as $item) {
            if ($item['key'] === 'Template file') {
                if ($shown >= 40) {
                    break;
                }

                $context->addItem('Template Relationship Highlights', 'Template', $item['value']);
                $shown++;
            }
        }
    }

    protected function extractEventDispatches(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'event_dispatches');

        if (!$section) {
            return;
        }

        $this->addSummaryItems($context, $section, 'Event Dispatch Architecture', array(
            'Summary / dispatched events',
            'Summary / dispatch calls',
            'Summary / dispatched events with observers',
            'Summary / dispatched events without observers',
        ));

        $shown = 0;

        foreach ($section['items'] as $item) {
            if ($item['key'] === 'Dispatched event') {
                if ($shown >= 40) {
                    break;
                }

                $context->addItem('Event Dispatch Highlights', 'Event', $item['value']);
                $shown++;
            }
        }
    }

    protected function extractRouteControllerMap(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'route_controller_map');

        if (!$section) {
            return;
        }

        $this->addSummaryItems($context, $section, 'Route Controller Map', array(
            'Summary / routes mapped',
            'Summary / controller files mapped',
            'Summary / action methods found',
            'Summary / missing controller directories',
        ));

        $shown = 0;

        foreach ($section['items'] as $item) {
            if ($item['key'] === 'Route map') {
                if ($shown >= 40) {
                    break;
                }

                $context->addItem('Route Controller Highlights', 'Route', $item['value']);
                $shown++;
            }
        }
    }

    protected function extractDatabaseSchema(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'database_schema');

        if (!$section) {
            return;
        }

        $this->addSummaryItems($context, $section, 'Database Schema', array(
            'Summary / tables',
            'Summary / columns',
            'Summary / likely custom tables',
            'Summary / likely custom table names',
        ));
    }


    protected function extractModuleDependencyGraph(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'module_dependency_graph');

        if (!$section) {
            return;
        }

        $this->addSummaryItems($context, $section, 'Module Dependency Graph', array(
            'Summary / modules',
            'Summary / custom modules',
            'Summary / modules with dependencies',
            'Summary / missing dependencies',
            'Summary / simple dependency cycles',
        ));
    }

    protected function extractXmlMergeAnalysis(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'xml_merge_analysis');

        if (!$section) {
            return;
        }

        $this->addSummaryItems($context, $section, 'XML Merge Analysis', array(
            'Summary / XML merge files',
            'Summary / XML parse errors',
        ));
    }

    protected function extractThemeFallbackMap(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'theme_fallback_map');

        if (!$section) {
            return;
        }

        $this->addSummaryItems($context, $section, 'Theme Fallback Map', array(
            'Summary / packages used by stores',
        ));

        $shown = 0;
        foreach ($section['items'] as $item) {
            if ($item['key'] === 'Theme fallback') {
                if ($shown >= 20) {
                    break;
                }
                $context->addItem('Theme Fallback Highlights', 'Theme', $item['value']);
                $shown++;
            }
        }
    }

    protected function extractCodeComplexity(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'code_complexity');

        if (!$section) {
            return;
        }

        $this->addSummaryItems($context, $section, 'Code Complexity', array(
            'Summary / PHP files scanned',
            'Summary / PHP lines scanned',
            'Summary / methods found',
            'Summary / complex methods >=20',
        ));

        $shown = 0;
        foreach ($section['items'] as $item) {
            if ($item['key'] === 'Complex method') {
                if ($shown >= 30) {
                    break;
                }
                $context->addItem('Code Complexity Highlights', 'Complex method', $item['value']);
                $shown++;
            }
        }
    }

    protected function extractDeadCode(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'dead_code');

        if (!$section) {
            return;
        }

        $this->addSummaryItems($context, $section, 'Dead Code Indicators', array(
            'Summary / inactive modules with code',
            'Summary / active modules with missing paths',
            'Summary / inactive behavioural modules',
            'Summary / possible orphan layout files',
        ));
    }

    protected function extractCodeRelationshipIndex(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'code_relationship_index');

        if (!$section) {
            return;
        }

        $this->addSummaryItems($context, $section, 'Code Relationship Index', array(
            'Summary / relationship topics',
        ));

        $shown = 0;
        foreach ($section['items'] as $item) {
            if ($item['key'] === 'Relationship topic') {
                if ($shown >= 20) {
                    break;
                }
                $context->addItem('Code Relationship Topics', 'Topic', $item['value']);
                $shown++;
            }
        }
    }

    protected function extractAiIndex(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'ai_index');

        if (!$section) {
            return;
        }

        $this->addSummaryItems($context, $section, 'AI Index', array(
            'Summary / indexed topics',
        ));

        $shown = 0;
        foreach ($section['items'] as $item) {
            if ($item['key'] === 'AI topic') {
                if ($shown >= 20) {
                    break;
                }
                $context->addItem('AI Topic Index', 'Topic', $item['value']);
                $shown++;
            }
        }
    }

    protected function extractSearchTags(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'search_tags');

        if (!$section) {
            return;
        }

        $this->addSummaryItems($context, $section, 'Search Tags', array(
            'Summary / tags generated',
            'Summary / tags shown',
        ));

        $tags = array();
        foreach ($section['items'] as $item) {
            if ($item['key'] === 'Search tag' && count($tags) < 80) {
                $tags[] = $item['value'];
            }
        }

        if (count($tags)) {
            $context->addItem('Search Tags', 'Top tags', implode(', ', $tags));
        }
    }

    protected function extractRiskReport(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'risk_report');

        if (!$section) {
            return;
        }

        $this->addSummaryItems($context, $section, 'Risk Report', array(
            'Summary / risk count',
        ));

        foreach ($section['items'] as $item) {
            if (strpos($item['key'], 'High risk') !== false || $item['key'] === 'Risk') {
                $context->addItem('Risk Report', $item['key'], $item['value']);
            }
        }
    }

    protected function extractAiSummary(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'ai_architecture_summary');

        if (!$section) {
            return;
        }

        foreach ($section['items'] as $item) {
            if (strpos($item['key'], 'Coverage / ') === 0
                || strpos($item['key'], 'Metric / ') === 0
                || $item['key'] === 'AI context score'
                || $item['key'] === 'Risk'
            ) {
                $context->addItem('AI Architecture Summary', $item['key'], $item['value']);
            }
        }
    }

    protected function addSummaryItems(AiContext $context, array $section, $contextSection, array $keys)
    {
        foreach ($keys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem($contextSection, str_replace('Summary / ', '', $key), $value);
            }
        }
    }
}
