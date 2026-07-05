<?php

class MagentoBootstrapCollector extends AbstractCollector
{
    public function getCode()
    {
        return 'magento_bootstrap';
    }

    public function getTitle()
    {
        return 'Magento Bootstrap';
    }

    public function getDescription()
    {
        return 'Detects and bootstraps the Magento/OpenMage application.';
    }

    public function getSince()
    {
        return '0.2.0';
    }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Magento Bootstrap',
            'Determines whether the target root is a Magento/OpenMage installation and attempts a safe read-only bootstrap.',
            'app/Mage.php and Mage runtime',
            'High'
        );

        $root = $context->getMagentoRoot();
        $mageFile = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';

        $section->addItem('Target root', $root);
        $section->addItem('Mage.php path', $mageFile);
        $section->addItem('Mage.php exists', file_exists($mageFile) ? 'yes' : 'no');

        if (!file_exists($mageFile)) {
            $context->setMagentoAvailable(false);
            $context->setMageBootstrapped(false);

            $section->addError('app/Mage.php was not found. The target root does not appear to be a Magento/OpenMage installation.');
            return;
        }

        $context->setMagentoAvailable(true);

        if (class_exists('Mage', false)) {
            $context->setMageBootstrapped(true);
            $section->addItem('Mage class already loaded', 'yes');
        } else {
            $section->addItem('Mage class already loaded', 'no');

            require_once $mageFile;

            if (!class_exists('Mage', false)) {
                $context->setMageBootstrapped(false);
                $section->addError('Mage class was not available after requiring app/Mage.php.');
                return;
            }
        }

        try {
            Mage::app('admin');

            $context->setMageBootstrapped(true);
            $context->set('mage_version', Mage::getVersion());
            $context->set('mage_edition', method_exists('Mage', 'getEdition') ? Mage::getEdition() : 'Unknown');
            $context->set('mage_root', Mage::getBaseDir());

            $section->addItem('Bootstrap status', 'success');
            $section->addItem('Mage::getVersion()', Mage::getVersion());
            $section->addItem('Edition', method_exists('Mage', 'getEdition') ? Mage::getEdition() : 'Unknown');
            $section->addItem('Mage base dir', Mage::getBaseDir());
            $section->addItem('Default timezone', Mage::getStoreConfig('general/locale/timezone'));
        } catch (Exception $e) {
            $context->setMageBootstrapped(false);
            $section->addItem('Bootstrap status', 'failed');
            $section->addError($e->getMessage());
        }
    }
}