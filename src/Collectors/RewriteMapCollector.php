<?php

class RewriteMapCollector extends AbstractCollector
{
    public function getCode() { return 'rewrite_map'; }
    public function getTitle() { return 'Rewrite Map'; }
    public function getCategory() { return 'Architecture'; }
    public function getDescription() { return 'Reports resolved Magento model, block and helper rewrite winners.'; }
    public function getSince() { return '0.6.0'; }
    public function getDependencies() { return array('magento_bootstrap', 'rewrites'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Builds a practical resolved rewrite map showing which class wins for each Magento alias.',
            'Mage merged configuration',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so rewrite map information is unavailable.');
            return;
        }

        $types = array('models', 'blocks', 'helpers');

        $totalAliases = 0;
        $customWinners = 0;
        $missingWinningFiles = 0;

        foreach ($types as $type) {
            $rewrites = $this->collectRewritesForType($type);

            foreach ($rewrites as $alias => $class) {
                $totalAliases++;

                $file = $this->classToFile($class);
                $exists = ($file !== '' && file_exists($file)) ? 'yes' : 'no';
                $custom = $this->isCustomClass($class) ? 'yes' : 'no';

                if ($custom === 'yes') {
                    $customWinners++;
                }

                if ($exists === 'no') {
                    $missingWinningFiles++;
                }

                $section->addItem('Rewrite', $type . ' / ' . $alias);
                $section->addItem('  Winning class', $class);
                $section->addItem('  Custom', $custom);
                $section->addItem('  Class file exists', $exists);
                $section->addItem('  Class file', $file !== '' ? $file : '[unknown]');
            }
        }

        $section->addItem('Summary / resolved rewrite aliases', $totalAliases);
        $section->addItem('Summary / custom winning classes', $customWinners);
        $section->addItem('Summary / missing winning class files', $missingWinningFiles);
    }

    protected function collectRewritesForType($type)
    {
        $result = array();
        $nodes = Mage::getConfig()->getNode('global/' . $type);

        if (!$nodes) {
            return $result;
        }

        foreach ($nodes->children() as $groupName => $groupNode) {
            if (!$groupNode->rewrite) {
                continue;
            }

            foreach ($groupNode->rewrite->children() as $aliasName => $classNode) {
                $alias = $groupName . '/' . $aliasName;
                $class = trim((string)$classNode);

                if ($class !== '') {
                    $result[$alias] = $class;
                }
            }
        }

        ksort($result);

        return $result;
    }

    protected function classToFile($class)
    {
        if ($class === '' || $class === '[unknown]' || $class === '[none]') {
            return '';
        }

        $relativeFile = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';

        $locations = array(
            Mage::getBaseDir('code') . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . $relativeFile,
            Mage::getBaseDir('code') . DIRECTORY_SEPARATOR . 'community' . DIRECTORY_SEPARATOR . $relativeFile,
            Mage::getBaseDir('code') . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . $relativeFile,
            Mage::getBaseDir('lib') . DIRECTORY_SEPARATOR . $relativeFile,
        );

        foreach ($locations as $file) {
            if (file_exists($file)) {
                return $file;
            }
        }

        return '';
    }

    protected function isCustomClass($class)
    {
        if ($class === '' || $class === '[unknown]' || $class === '[none]') {
            return false;
        }

        $customPrefixes = array(
            'Radiotronics_',
            'HenryHayes_',
            'Symbix_',
            'OpenSearch',
            'Ethan_',
            'Tvcom_',
            'Tvmenu_',
        );

        foreach ($customPrefixes as $prefix) {
            if (strpos($class, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }
}