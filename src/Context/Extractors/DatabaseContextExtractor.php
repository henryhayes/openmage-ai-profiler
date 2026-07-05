<?php

class DatabaseContextExtractor extends AbstractAiContextExtractor
{
    public function extract(AiContext $context, array $data)
    {
        $this->extractDatabase($context, $data);
    }
    protected function extractDatabase(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'database');

        if (!$section) {
            return;
        }

        $keys = array(
            'Host',
            'Database name',
            'Table prefix',
            'Server version',
            'Table count',
            'Setup resources',
        );

        foreach ($keys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('Database Architecture', $key, $value);
            }
        }

        $this->extractDatabaseHighlights($context, $section);
    }

    protected function extractDatabaseHighlights(AiContext $context, array $section)
    {
        $setupResources = $this->parseDatabaseSetupResources($section);

        $this->addDatabaseSetupResourceCounts($context, $setupResources);
        $this->addDatabaseNonCoreSetupResources($context, $setupResources);
        $this->addDatabaseSetupVersionMismatches($context, $setupResources);
        $this->addDatabaseSetupMissingVersions($context, $setupResources);
        $this->addDatabaseHighImpactSetupResources($context, $setupResources);
    }

    protected function parseDatabaseSetupResources(array $section)
    {
        $setupResources = array();

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key !== 'Setup resource') {
                continue;
            }

            $setupResources[] = $this->parseDatabaseSetupResourceValue($value);
        }

        return $setupResources;
    }

    protected function parseDatabaseSetupResourceValue($value)
    {
        $parts = explode(';', $value);
        $code = trim(array_shift($parts));

        $resource = array(
            'code' => $code,
            'version' => '',
            'data_version' => '',
            'raw' => $value,
        );

        foreach ($parts as $part) {
            $part = trim($part);

            if (strpos($part, 'version=') === 0) {
                $resource['version'] = trim(substr($part, strlen('version=')));
            } elseif (strpos($part, 'data_version=') === 0) {
                $resource['data_version'] = trim(substr($part, strlen('data_version=')));
            }
        }

        return $resource;
    }

    protected function addDatabaseSetupResourceCounts(AiContext $context, array $setupResources)
    {
        $core = 0;
        $nonCore = 0;
        $missingVersion = 0;
        $versionMismatch = 0;

        foreach ($setupResources as $resource) {
            if ($this->isCoreSetupResource($resource['code'])) {
                $core++;
            } else {
                $nonCore++;
            }

            if ($this->isMissingSetupVersion($resource['version']) || $this->isMissingSetupVersion($resource['data_version'])) {
                $missingVersion++;
            }

            if (
                !$this->isMissingSetupVersion($resource['version'])
                && !$this->isMissingSetupVersion($resource['data_version'])
                && $resource['version'] !== $resource['data_version']
            ) {
                $versionMismatch++;
            }
        }

        $context->addItem('Database Setup Resource Summary', 'core setup resources', $core);
        $context->addItem('Database Setup Resource Summary', 'non-core setup resources', $nonCore);
        $context->addItem('Database Setup Resource Summary', 'resources with missing versions', $missingVersion);
        $context->addItem('Database Setup Resource Summary', 'resources with version/data_version mismatch', $versionMismatch);
    }

    protected function addDatabaseNonCoreSetupResources(AiContext $context, array $setupResources)
    {
        $count = 0;
        $limit = 80;

        foreach ($setupResources as $resource) {
            if ($this->isCoreSetupResource($resource['code'])) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Database Non-Core Setup Resources',
                    $resource['code'],
                    'version=' . $resource['version'] . '; dataVersion=' . $resource['data_version']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Database Non-Core Setup Resources',
                'Truncated',
                'Only the first ' . $limit . ' non-core setup resources are shown in this short AI context. See full profile for all database setup resource data.'
            );
        }
    }

    protected function addDatabaseSetupVersionMismatches(AiContext $context, array $setupResources)
    {
        $count = 0;
        $limit = 40;

        foreach ($setupResources as $resource) {
            if ($this->isMissingSetupVersion($resource['version']) || $this->isMissingSetupVersion($resource['data_version'])) {
                continue;
            }

            if ($resource['version'] === $resource['data_version']) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Database Setup Version Mismatches',
                    $resource['code'],
                    'version=' . $resource['version'] . '; dataVersion=' . $resource['data_version']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Database Setup Version Mismatches',
                'Truncated',
                'Only the first ' . $limit . ' setup resources with version mismatches are shown in this short AI context. See full profile for all database setup resource data.'
            );
        }
    }

    protected function addDatabaseSetupMissingVersions(AiContext $context, array $setupResources)
    {
        $count = 0;
        $limit = 40;

        foreach ($setupResources as $resource) {
            if (!$this->isMissingSetupVersion($resource['version']) && !$this->isMissingSetupVersion($resource['data_version'])) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Database Setup Missing Versions',
                    $resource['code'],
                    'version=' . $resource['version'] . '; dataVersion=' . $resource['data_version']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Database Setup Missing Versions',
                'Truncated',
                'Only the first ' . $limit . ' setup resources with missing versions are shown in this short AI context. See full profile for all database setup resource data.'
            );
        }
    }

    protected function addDatabaseHighImpactSetupResources(AiContext $context, array $setupResources)
    {
        $count = 0;
        $limit = 80;

        foreach ($setupResources as $resource) {
            if (!$this->isHighImpactSetupResource($resource)) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Database High Impact Setup Resources',
                    $resource['code'],
                    'version=' . $resource['version'] . '; dataVersion=' . $resource['data_version']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Database High Impact Setup Resources',
                'Truncated',
                'Only the first ' . $limit . ' high-impact setup resources are shown in this short AI context. See full profile for all database setup resource data.'
            );
        }
    }

    protected function isCoreSetupResource($code)
    {
        $coreResources = array(
            'admin_setup',
            'adminnotification_setup',
            'api_setup',
            'api2_setup',
            'backup_setup',
            'bundle_setup',
            'captcha_setup',
            'catalog_setup',
            'catalogindex_setup',
            'cataloginventory_setup',
            'catalogrule_setup',
            'catalogsearch_setup',
            'checkout_setup',
            'cms_setup',
            'compiler_setup',
            'contacts_setup',
            'core_setup',
            'cron_setup',
            'customer_setup',
            'dataflow_setup',
            'directory_setup',
            'downloadable_setup',
            'eav_setup',
            'giftmessage_setup',
            'googleanalytics_setup',
            'googlecheckout_setup',
            'importexport_setup',
            'index_setup',
            'install_setup',
            'log_setup',
            'newsletter_setup',
            'oauth_setup',
            'paygate_setup',
            'payment_setup',
            'paypal_setup',
            'paypaluk_setup',
            'persistent_setup',
            'poll_setup',
            'productalert_setup',
            'rating_setup',
            'reports_setup',
            'review_setup',
            'rss_setup',
            'rule_setup',
            'sales_setup',
            'salesrule_setup',
            'sendfriend_setup',
            'shipping_setup',
            'sitemap_setup',
            'tag_setup',
            'tax_setup',
            'usa_setup',
            'weee_setup',
            'widget_setup',
            'wishlist_setup',
            'xmlconnect_setup',
        );

        return in_array($code, $coreResources);
    }

    protected function isMissingSetupVersion($version)
    {
        return $version === '' || $version === '[none]' || $version === '[unknown]' || $version === 'null';
    }

    protected function isHighImpactSetupResource(array $resource)
    {
        if (!$this->isCoreSetupResource($resource['code'])) {
            return true;
        }

        if ($this->isMissingSetupVersion($resource['version']) || $this->isMissingSetupVersion($resource['data_version'])) {
            return true;
        }

        if ($resource['version'] !== $resource['data_version']) {
            return true;
        }

        $needles = array(
            'catalog',
            'checkout',
            'customer',
            'eav',
            'index',
            'payment',
            'sales',
            'salesrule',
            'shipping',
            'tax',
        );

        foreach ($needles as $needle) {
            if (strpos($resource['code'], $needle) !== false) {
                return true;
            }
        }

        return false;
    }
    
}
