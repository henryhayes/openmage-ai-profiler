<?php

class StoreCollector extends AbstractCollector
{
    public function getCode()
    {
        return 'stores';
    }

    public function getTitle()
    {
        return 'Stores';
    }

    public function getCategory()
    {
        return 'Magento';
    }
    
    public function getDescription()
    {
        return 'Website, store group and store view structure.';
    }

    public function getSince()
    {
        return '0.3.0';
    }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Reports Magento website, store group and store view hierarchy.',
            'Mage::app()->getWebsites()',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so store information is unavailable.');
            return;
        }

        try {
            foreach (Mage::app()->getWebsites() as $website) {
                $section->addItem(
                    'Website',
                    $website->getId() . ' / ' . $website->getCode() . ' / ' . $website->getName()
                );

                foreach ($website->getGroups() as $group) {
                    $section->addItem(
                        '  Store group',
                        $group->getId() . ' / ' . $group->getName() . ' / root_category_id=' . $group->getRootCategoryId()
                    );

                    foreach ($group->getStores() as $store) {
                        $storeId = $store->getId();

                        $section->addItem(
                            '    Store view',
                            $storeId . ' / ' . $store->getCode() . ' / ' . $store->getName()
                        );

                        $section->addItem(
                            '      Active',
                            $store->getIsActive() ? 'yes' : 'no'
                        );

                        $section->addItem(
                            '      Base URL',
                            Mage::getStoreConfig('web/unsecure/base_url', $storeId)
                        );

                        $section->addItem(
                            '      Secure base URL',
                            Mage::getStoreConfig('web/secure/base_url', $storeId)
                        );

                        $section->addItem(
                            '      Locale',
                            Mage::getStoreConfig('general/locale/code', $storeId)
                        );

                        $section->addItem(
                            '      Timezone',
                            Mage::getStoreConfig('general/locale/timezone', $storeId)
                        );

                        $section->addItem(
                            '      Base currency',
                            Mage::getStoreConfig('currency/options/base', $storeId)
                        );

                        $section->addItem(
                            '      Default currency',
                            Mage::getStoreConfig('currency/options/default', $storeId)
                        );

                        $section->addItem(
                            '      Allowed currencies',
                            Mage::getStoreConfig('currency/options/allow', $storeId)
                        );

                        $section->addItem(
                            '      Design package',
                            Mage::getStoreConfig('design/package/name', $storeId)
                        );

                        $section->addItem(
                            '      Theme default',
                            Mage::getStoreConfig('design/theme/default', $storeId)
                        );

                        $section->addItem(
                            '      Theme layout',
                            Mage::getStoreConfig('design/theme/layout', $storeId)
                        );

                        $section->addItem(
                            '      Theme template',
                            Mage::getStoreConfig('design/theme/template', $storeId)
                        );

                        $section->addItem(
                            '      Theme skin',
                            Mage::getStoreConfig('design/theme/skin', $storeId)
                        );
                    }
                }
            }
        } catch (Exception $e) {
            $section->addError($e->getMessage());
        }
    }
}