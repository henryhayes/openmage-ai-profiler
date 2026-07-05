<?php

class IndexCollector extends AbstractCollector
{
    public function getCode() { return 'indexes'; }
    public function getTitle() { return 'Indexes'; }
    public function getCategory() { return 'Operations'; }
    public function getDescription() { return 'Reports Magento indexer processes and status.'; }
    public function getSince() { return '0.6.0'; }
    public function getDependencies() { return array('magento_bootstrap'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Reports Magento indexer processes, status and update mode.',
            'Mage_Index model collection',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so index information is unavailable.');
            return;
        }

        $collection = Mage::getModel('index/process')->getCollection();

        $total = 0;
        $requireReindex = 0;
        $processing = 0;
        $manual = 0;
        $realtime = 0;

        foreach ($collection as $process) {
            $total++;

            $status = $process->getStatus();
            $mode = $process->getMode();

            if ($status === Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX) {
                $requireReindex++;
            }

            if ($status === Mage_Index_Model_Process::STATUS_RUNNING) {
                $processing++;
            }

            if ((string)$mode === (string)Mage_Index_Model_Process::MODE_MANUAL) {
                $manual++;
            }

            if ((string)$mode === (string)Mage_Index_Model_Process::MODE_REAL_TIME) {
                $realtime++;
            }

            $section->addItem('Indexer', $process->getIndexerCode());
            $section->addItem('  Name', $process->getIndexer()->getName());
            $section->addItem('  Status', $status);
            $section->addItem('  Mode', $mode);
            $section->addItem('  Last started', $process->getStartedAt() ? $process->getStartedAt() : '[none]');
            $section->addItem('  Last ended', $process->getEndedAt() ? $process->getEndedAt() : '[none]');
        }

        $section->addItem('Summary / total indexers', $total);
        $section->addItem('Summary / require reindex', $requireReindex);
        $section->addItem('Summary / processing', $processing);
        $section->addItem('Summary / manual mode', $manual);
        $section->addItem('Summary / realtime mode', $realtime);
    }
}