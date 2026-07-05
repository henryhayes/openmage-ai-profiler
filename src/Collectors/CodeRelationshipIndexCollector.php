<?php

class CodeRelationshipIndexCollector extends AbstractCollector
{
    public function getCode() { return 'code_relationship_index'; }
    public function getTitle() { return 'Code Relationship Index'; }
    public function getCategory() { return 'AI'; }
    public function getDescription() { return 'Builds practical cross-reference chains linking routes, layout handles, templates, helpers, models and observers.'; }
    public function getSince() { return '0.11.0'; }
    public function getDependencies() { return array('modules', 'rewrites', 'observers', 'layouts', 'layout_graph', 'routers', 'controllers', 'templates', 'event_dispatches'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Creates a navigable relationship index for common Magento areas so AI can move from business topic to likely files and architecture sections.',
            'Profiler report sections and static keyword relationship extraction',
            'Medium'
        );

        $data = $report->toArray();
        $topics = array(
            'Product View' => array('catalog_product_view', 'product/view', 'product.info', 'catalog/product'),
            'Category View' => array('catalog_category_view', 'category/view', 'product_list'),
            'Checkout Cart' => array('checkout_cart', 'checkout/cart', 'cartcontroller'),
            'Onepage Checkout' => array('checkout_onepage', 'onepagecontroller', 'payment'),
            'Customer Account' => array('customer_account', 'customer/account', 'login'),
            'Admin Reports' => array('adminhtml', 'report', 'dashboard'),
            'Product Save Workflow' => array('catalog_product_save', 'product_save', 'catalog/product'),
            'Order Workflow' => array('sales_order', 'order_save', 'sales/order'),
        );

        foreach ($topics as $topic => $needles) {
            $matches = $this->findMatches($data, $needles, 20);
            $section->addItem('Relationship topic', $topic);
            $section->addItem('  Search hints', implode(', ', $needles));
            $section->addItem('  Matching artefacts', count($matches) ? implode(' | ', $matches) : '[none found in compact relationship scan]');
            $section->addItem('  Recommended inspection path', $this->recommendedPath($topic));
        }

        $section->addItem('Summary / relationship topics', count($topics));
    }

    protected function findMatches(array $data, array $needles, $limit)
    {
        $matches = array();
        foreach ($data['sections'] as $section) {
            $collector = isset($section['collector_name']) ? $section['collector_name'] : '[unknown]';
            foreach ($section['items'] as $item) {
                $text = strtolower((string)$item['key'] . ' ' . (string)$item['value']);
                foreach ($needles as $needle) {
                    if (strpos($text, strtolower($needle)) !== false) {
                        $matches[] = $collector . ': ' . $item['key'] . '=' . $this->shorten((string)$item['value']);
                        break;
                    }
                }
                if (count($matches) >= $limit) {
                    return $matches;
                }
            }
        }
        return $matches;
    }

    protected function recommendedPath($topic)
    {
        if ($topic === 'Product View') {
            return 'Route Controller Map -> Layout Graph -> Template Architecture -> Rewrite Chains -> Event Dispatches';
        }
        if ($topic === 'Checkout Cart' || $topic === 'Onepage Checkout') {
            return 'Router Architecture -> Controller Architecture -> Rewrite Chains -> Observer Architecture -> Layout Graph';
        }
        if ($topic === 'Product Save Workflow' || $topic === 'Order Workflow') {
            return 'Event Dispatch Architecture -> Observer Architecture -> Rewrite Chains -> Database Schema';
        }
        return 'Module Architecture -> Router Architecture -> Controller Architecture -> Layout Graph -> Templates';
    }

    protected function shorten($value)
    {
        $value = trim(str_replace(array("\r", "\n"), ' ', $value));
        return strlen($value) > 120 ? substr($value, 0, 117) . '...' : $value;
    }
}
