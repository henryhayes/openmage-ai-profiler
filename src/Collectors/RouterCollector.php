<?php

class RouterCollector extends AbstractCollector
{
    public function getCode() { return 'routers'; }
    public function getTitle() { return 'Routers'; }
    public function getCategory() { return 'Architecture'; }
    public function getDescription() { return 'Reports frontend and adminhtml router declarations.'; }
    public function getSince() { return '0.7.0'; }
    public function getDependencies() { return array('magento_bootstrap', 'modules'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Reports Magento router declarations, route front names and modules.',
            'Mage merged configuration',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so router information is unavailable.');
            return;
        }

        $areas = array('frontend', 'adminhtml');
        $totalRoutes = 0;

        foreach ($areas as $area) {
            $routersNode = Mage::getConfig()->getNode($area . '/routers');

            if (!$routersNode) {
                continue;
            }

            foreach ($routersNode->children() as $routerName => $routerNode) {
                if (!$routerNode->args || !$routerNode->args->modules) {
                    continue;
                }

                foreach ($routerNode->args->modules->children() as $moduleAlias => $moduleNode) {
                    $totalRoutes++;

                    $frontName = $routerNode->args->frontName
                        ? trim((string)$routerNode->args->frontName)
                        : '[none]';

                    $section->addItem('Router', $area . ' / ' . $routerName . ' / ' . $moduleAlias);
                    $section->addItem('  Area', $area);
                    $section->addItem('  Router name', (string)$routerName);
                    $section->addItem('  Module alias', (string)$moduleAlias);
                    $section->addItem('  Module', trim((string)$moduleNode));
                    $section->addItem('  Front name', $frontName);
                }
            }
        }

        $section->addItem('Summary / total routes', $totalRoutes);
    }
}