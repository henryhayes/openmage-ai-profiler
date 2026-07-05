<?php

class DatabaseCollector extends AbstractCollector
{
    public function getCode() { return 'database'; }
    public function getTitle() { return 'Database'; }
    public function getCategory() { return 'Operations'; }
    public function getDescription() { return 'Reports database connection, server version and table summary.'; }
    public function getSince() { return '0.6.0'; }
    public function getDependencies() { return array('magento_bootstrap'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Reports Magento database connection metadata, table count and table prefix.',
            'Magento resource connection',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so database information is unavailable.');
            return;
        }

        try {
            $resource = Mage::getSingleton('core/resource');
            $connection = $resource->getConnection('core_read');

            $config = Mage::getConfig()->getResourceConnectionConfig('default_setup');
            $tablePrefix = (string)Mage::getConfig()->getTablePrefix();

            $serverVersion = $connection->fetchOne('SELECT VERSION()');
            $databaseName = (string)$config->dbname;

            $tables = $connection->fetchCol('SHOW TABLES');
            $tableCount = count($tables);

            $section->addItem('Host', $this->maskHost((string)$config->host));
            $section->addItem('Database name', $databaseName !== '' ? $databaseName : '[unknown]');
            $section->addItem('Username present', ((string)$config->username !== '') ? 'yes' : 'no');
            $section->addItem('Password present', ((string)$config->password !== '') ? 'yes' : 'no');
            $section->addItem('Table prefix', $tablePrefix !== '' ? $tablePrefix : '[none]');
            $section->addItem('Server version', $serverVersion);
            $section->addItem('Table count', $tableCount);

            $coreResourceTable = $resource->getTableName('core/resource');

            if (in_array($coreResourceTable, $tables)) {
                $rows = $connection->fetchAll(
                    'SELECT code, version, data_version FROM ' . $connection->quoteIdentifier($coreResourceTable) . ' ORDER BY code'
                );

                $section->addItem('Setup resources', count($rows));

                foreach ($rows as $row) {
                    $section->addItem(
                        'Setup resource',
                        $row['code'] . '; version=' . $row['version'] . '; data_version=' . $row['data_version']
                    );
                }
            } else {
                $section->addItem('Setup resources', '[core_resource table not found]');
            }
        } catch (Exception $e) {
            $section->addError($e->getMessage());
        }
    }

    protected function maskHost($host)
    {
        if ($host === '' || $host === 'localhost' || $host === '127.0.0.1') {
            return $host;
        }

        return '[configured]';
    }
}