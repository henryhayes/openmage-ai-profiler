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
