<?php

class DatabaseSchemaCollector extends AbstractCollector
{
    public function getCode() { return 'database_schema'; }
    public function getTitle() { return 'Database Schema'; }
    public function getCategory() { return 'Operations'; }
    public function getDescription() { return 'Reports database table schemas, columns, indexes and custom table indicators.'; }
    public function getSince() { return '0.10.0'; }
    public function getDependencies() { return array('magento_bootstrap', 'database'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Summarises Magento database schema with emphasis on custom or non-core tables.',
            'SHOW TABLES, DESCRIBE and SHOW INDEX through Magento core_read connection',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so database schema information is unavailable.');
            return;
        }

        try {
            $resource = Mage::getSingleton('core/resource');
            $connection = $resource->getConnection('core_read');
            $tables = $connection->fetchCol('SHOW TABLES');

            sort($tables);

            $customTables = array();
            $tableSummaries = array();
            $totalColumns = 0;

            foreach ($tables as $table) {
                $columns = $connection->describeTable($table);
                $indexes = $connection->fetchAll('SHOW INDEX FROM ' . $connection->quoteIdentifier($table));
                $totalColumns += count($columns);

                $isCustom = $this->isLikelyCustomTable($table);

                if ($isCustom) {
                    $customTables[] = $table;
                }

                $tableSummaries[] = array(
                    'table' => $table,
                    'columns' => count($columns),
                    'indexes' => $this->countDistinctIndexes($indexes),
                    'custom' => $isCustom ? 'yes' : 'no',
                    'columns_raw' => $columns,
                );
            }

            foreach ($tableSummaries as $row) {
                $section->addItem('Database table', $row['table']);
                $section->addItem('  Columns', $row['columns']);
                $section->addItem('  Indexes', $row['indexes']);
                $section->addItem('  Likely custom table', $row['custom']);

                if ($row['custom'] === 'yes') {
                    $section->addItem('  Column names', implode(', ', array_keys($row['columns_raw'])));
                }
            }

            $section->addItem('Summary / tables', count($tables));
            $section->addItem('Summary / columns', $totalColumns);
            $section->addItem('Summary / likely custom tables', count($customTables));
            $section->addItem('Summary / likely custom table names', count($customTables) ? implode(', ', $customTables) : '[none]');
        } catch (Exception $e) {
            $section->addError($e->getMessage());
        }
    }

    protected function countDistinctIndexes(array $indexes)
    {
        $names = array();

        foreach ($indexes as $index) {
            if (isset($index['Key_name'])) {
                $names[$index['Key_name']] = true;
            }
        }

        return count($names);
    }

    protected function isLikelyCustomTable($table)
    {
        $corePrefixes = array(
            'admin',
            'api',
            'catalog',
            'cataloginventory',
            'catalogrule',
            'checkout',
            'cms',
            'core',
            'cron',
            'customer',
            'dataflow',
            'directory',
            'downloadable',
            'eav',
            'enterprise',
            'gift',
            'google',
            'import',
            'index',
            'log',
            'newsletter',
            'oauth',
            'paypal',
            'persistent',
            'poll',
            'rating',
            'report',
            'review',
            'sales',
            'salesrule',
            'shipping',
            'sitemap',
            'tag',
            'tax',
            'weee',
            'widget',
            'wishlist',
            'xmlconnect',
        );

        foreach ($corePrefixes as $prefix) {
            if ($table === $prefix || strpos($table, $prefix . '_') === 0) {
                return false;
            }
        }

        return true;
    }
}
