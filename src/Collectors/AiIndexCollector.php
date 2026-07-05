<?php

class AiIndexCollector extends AbstractCollector
{
    public function getCode() { return 'ai_index'; }
    public function getTitle() { return 'AI Index'; }
    public function getCategory() { return 'AI'; }
    public function getDescription() { return 'Creates topic-oriented index entries for common Magento development questions.'; }
    public function getSince() { return '0.11.0'; }
    public function getDependencies() { return array('modules', 'rewrites', 'observers', 'cron', 'routers', 'controllers', 'templates'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Provides a high-level topic index so an AI can jump to relevant Magento architecture areas quickly.',
            'Profiler report sections and topic keyword rules',
            'Medium'
        );

        $data = $report->toArray();
        $topics = array(
            'catalog product pricing stock search' => array('catalog', 'product', 'price', 'pricing', 'stock', 'inventory', 'search'),
            'checkout cart payment order sales' => array('checkout', 'cart', 'payment', 'paypal', 'stripe', 'sagepay', 'order', 'sales'),
            'customer account login restrictions' => array('customer', 'login', 'account', 'autologin', 'restrictions'),
            'email smtp mailchimp automation' => array('email', 'smtp', 'mailchimp', 'mandrill', 'automation'),
            'admin reports dashboards exports' => array('admin', 'report', 'dashboard', 'export'),
            'frontend themes layout templates css' => array('theme', 'layout', 'template', 'phtml', 'css', 'frontend'),
            'cron integrations queues feeds' => array('cron', 'queue', 'feed', 'integration', 'quickbooks', 'm2epro'),
            'rewrites overrides conflicts' => array('rewrite', 'override', 'conflict'),
        );

        $haystack = strtolower($this->flattenReport($data));

        foreach ($topics as $topic => $keywords) {
            $score = 0;
            $matched = array();
            foreach ($keywords as $keyword) {
                $count = substr_count($haystack, strtolower($keyword));
                if ($count > 0) {
                    $score += $count;
                    $matched[] = $keyword . '=' . $count;
                }
            }
            $section->addItem('AI topic', $topic);
            $section->addItem('  Keyword score', $score);
            $section->addItem('  Matched keywords', count($matched) ? implode(', ', $matched) : '[none]');
            $section->addItem('  Relevant full-profile sections', $this->sectionsForTopic($topic));
        }

        $section->addItem('Summary / indexed topics', count($topics));
    }

    protected function flattenReport(array $data)
    {
        $parts = array();
        foreach ($data['sections'] as $section) {
            $parts[] = isset($section['collector_name']) ? $section['collector_name'] : '';
            foreach ($section['items'] as $item) {
                $parts[] = (string)$item['key'];
                $parts[] = (string)$item['value'];
            }
        }
        return implode("\n", $parts);
    }

    protected function sectionsForTopic($topic)
    {
        if (strpos($topic, 'frontend') !== false) {
            return 'Themes, Theme Hierarchy, Theme Fallback Map, Layouts, Layout Graph, Templates';
        }
        if (strpos($topic, 'rewrites') !== false) {
            return 'Rewrites, Rewrite Map, Rewrite Chains';
        }
        if (strpos($topic, 'cron') !== false) {
            return 'Cron, Modules, Event Dispatches';
        }
        if (strpos($topic, 'checkout') !== false) {
            return 'Modules, Rewrites, Observers, Routes, Controllers, Layout Graph';
        }
        return 'Modules, Rewrites, Observers, Routes, Controllers, Database Schema';
    }
}
