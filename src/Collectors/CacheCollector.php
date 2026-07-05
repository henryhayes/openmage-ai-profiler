<?php

class CacheCollector extends AbstractCollector
{
    public function getCode() { return 'cache'; }
    public function getTitle() { return 'Cache'; }
    public function getCategory() { return 'Operations'; }
    public function getDescription() { return 'Reports Magento cache backend and cache type status.'; }
    public function getSince() { return '0.6.0'; }
    public function getDependencies() { return array('magento_bootstrap'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Reports Magento cache backend, configured cache types and enabled status.',
            'Mage cache configuration',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so cache information is unavailable.');
            return;
        }

        $cache = Mage::app()->getCache();
        $types = Mage::app()->useCache();

        $enabled = 0;
        $disabled = 0;

        $section->addItem('Backend class', get_class($cache->getBackend()));
        $section->addItem('Cache prefix', Mage::getConfig()->getNode('global/cache/prefix') ? (string)Mage::getConfig()->getNode('global/cache/prefix') : '[none]');
        $section->addItem('Session save', $this->getSessionSave());

        foreach ($types as $type => $state) {
            if ($state) {
                $enabled++;
                $stateText = 'enabled';
            } else {
                $disabled++;
                $stateText = 'disabled';
            }

            $section->addItem('Cache type', $type);
            $section->addItem('  Status', $stateText);
        }

        $section->addItem('Summary / cache types', count($types));
        $section->addItem('Summary / enabled cache types', $enabled);
        $section->addItem('Summary / disabled cache types', $disabled);
    }


    protected function getSessionSave()
    {
        try {
            $sessionSave = (string)Mage::getConfig()->getNode('global/session_save');

            if ($sessionSave !== '') {
                return $sessionSave;
            }
        } catch (Exception $e) {
            return '[unavailable]';
        }

        return '[default]';
    }
}
