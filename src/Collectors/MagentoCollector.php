<?php

class MagentoCollector extends AbstractCollector
{
    public function getCode()
    {
        return 'magento';
    }

    public function getTitle()
    {
        return 'Magento';
    }

    public function getDescription()
    {
        return 'Magento/OpenMage version and core application information.';
    }

    public function getSince()
    {
        return '0.2.0';
    }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Magento',
            'Reports core Magento/OpenMage application identity and basic runtime configuration.',
            'Mage runtime',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addItem('Available', $context->isMagentoAvailable() ? 'yes' : 'no');
            $section->addItem('Bootstrapped', 'no');
            $section->addError('Magento was not bootstrapped, so Magento application information is unavailable.');
            return;
        }

        $section->addItem('Available', 'yes');
        $section->addItem('Bootstrapped', 'yes');
        $section->addItem('Mage::getVersion()', $context->getMagentoVersion());
        $section->addItem('Edition', $context->getMagentoEdition());
        $section->addItem('Mage base dir', $context->getMagentoBaseDir());
        $section->addItem('Default timezone', Mage::getStoreConfig('general/locale/timezone'));
        $section->addItem('Default locale', Mage::getStoreConfig('general/locale/code'));
        $section->addItem('Default currency', Mage::getStoreConfig('currency/options/base'));

        $section->addItem('Compiler enabled', defined('COMPILER_INCLUDE_PATH') ? 'yes' : 'no');

        try {
            $section->addItem('Cache backend class', get_class(Mage::app()->getCache()->getBackend()));
        } catch (Exception $e) {
            $section->addItem('Cache backend class', '[unavailable]');
            $section->addError('Unable to read cache backend: ' . $e->getMessage());
        }

        try {
            $sessionSave = (string)Mage::getConfig()->getNode('global/session_save');
            $section->addItem('Session save', $sessionSave !== '' ? $sessionSave : '[default]');
        } catch (Exception $e) {
            $section->addItem('Session save', '[unavailable]');
        }
    }
}