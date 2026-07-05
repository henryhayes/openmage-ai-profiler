<?php

class OperationsContextExtractor extends AbstractAiContextExtractor
{
    public function extract(AiContext $context, array $data)
    {
        $this->extractIndexes($context, $data);
        $this->extractCache($context, $data);
    }
    protected function extractIndexes(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'indexes');

        if (!$section) {
            return;
        }

        $summaryKeys = array(
            'Summary / total indexers',
            'Summary / require reindex',
            'Summary / processing',
            'Summary / manual mode',
            'Summary / realtime mode',
        );

        foreach ($summaryKeys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('Index Architecture', str_replace('Summary / ', '', $key), $value);
            }
        }

        $this->extractIndexHighlights($context, $section);
    }

    protected function extractCache(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'cache');

        if (!$section) {
            return;
        }

        $keys = array(
            'Backend class',
            'Cache prefix',
            'Session save',
            'Summary / cache types',
            'Summary / enabled cache types',
            'Summary / disabled cache types',
        );

        foreach ($keys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('Cache Architecture', str_replace('Summary / ', '', $key), $value);
            }
        }

        $this->extractCacheHighlights($context, $section);
    }

    protected function extractIndexHighlights(AiContext $context, array $section)
    {
        $indexers = $this->parseIndexRows($section);

        $this->addIndexStatusCounts($context, $indexers);
        $this->addIndexModeCounts($context, $indexers);
        $this->addIndexManualIndexers($context, $indexers);
        $this->addIndexRequireReindexHighlights($context, $indexers);
        $this->addIndexProcessingHighlights($context, $indexers);
        $this->addIndexStaleDateHighlights($context, $indexers);
        $this->addIndexHighImpactHighlights($context, $indexers);
    }

    protected function parseIndexRows(array $section)
    {
        $indexers = array();
        $currentIndexer = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Indexer') {
                if ($currentIndexer !== null) {
                    $indexers[] = $currentIndexer;
                }

                $currentIndexer = array(
                    'code' => $value,
                    'name' => '',
                    'status' => '',
                    'mode' => '',
                    'last_started' => '',
                    'last_ended' => '',
                );

                continue;
            }

            if ($currentIndexer === null) {
                continue;
            }

            if ($key === 'Name') {
                $currentIndexer['name'] = $value;
            } elseif ($key === 'Status') {
                $currentIndexer['status'] = $value;
            } elseif ($key === 'Mode') {
                $currentIndexer['mode'] = $value;
            } elseif ($key === 'Last started') {
                $currentIndexer['last_started'] = $value;
            } elseif ($key === 'Last ended') {
                $currentIndexer['last_ended'] = $value;
            }
        }

        if ($currentIndexer !== null) {
            $indexers[] = $currentIndexer;
        }

        return $indexers;
    }

    protected function addIndexStatusCounts(AiContext $context, array $indexers)
    {
        $counts = array();

        foreach ($indexers as $indexer) {
            $status = $indexer['status'];

            if ($status === '') {
                $status = '[unknown]';
            }

            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }

            $counts[$status]++;
        }

        ksort($counts);

        foreach ($counts as $status => $count) {
            $context->addItem('Index Status Counts', $status, $count);
        }
    }

    protected function addIndexModeCounts(AiContext $context, array $indexers)
    {
        $counts = array();

        foreach ($indexers as $indexer) {
            $mode = $indexer['mode'];

            if ($mode === '') {
                $mode = '[unknown]';
            }

            if (!isset($counts[$mode])) {
                $counts[$mode] = 0;
            }

            $counts[$mode]++;
        }

        ksort($counts);

        foreach ($counts as $mode => $count) {
            $context->addItem('Index Mode Counts', $mode, $count);
        }
    }

    protected function addIndexManualIndexers(AiContext $context, array $indexers)
    {
        $count = 0;
        $limit = 40;

        foreach ($indexers as $indexer) {
            if ($indexer['mode'] !== 'manual') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Index Manual Indexers',
                    $indexer['code'],
                    'name=' . $indexer['name']
                    . '; status=' . $indexer['status']
                    . '; lastStarted=' . $indexer['last_started']
                    . '; lastEnded=' . $indexer['last_ended']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Index Manual Indexers',
                'Truncated',
                'Only the first ' . $limit . ' manual indexers are shown in this short AI context. See full profile for all index data.'
            );
        }
    }

    protected function addIndexRequireReindexHighlights(AiContext $context, array $indexers)
    {
        $count = 0;
        $limit = 40;

        foreach ($indexers as $indexer) {
            if ($indexer['status'] !== 'require_reindex') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Indexers Requiring Reindex',
                    $indexer['code'],
                    'name=' . $indexer['name']
                    . '; mode=' . $indexer['mode']
                    . '; lastStarted=' . $indexer['last_started']
                    . '; lastEnded=' . $indexer['last_ended']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Indexers Requiring Reindex',
                'Truncated',
                'Only the first ' . $limit . ' indexers requiring reindex are shown in this short AI context. See full profile for all index data.'
            );
        }
    }

    protected function addIndexProcessingHighlights(AiContext $context, array $indexers)
    {
        $count = 0;
        $limit = 40;

        foreach ($indexers as $indexer) {
            if ($indexer['status'] !== 'processing') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Indexers Processing',
                    $indexer['code'],
                    'name=' . $indexer['name']
                    . '; mode=' . $indexer['mode']
                    . '; lastStarted=' . $indexer['last_started']
                    . '; lastEnded=' . $indexer['last_ended']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Indexers Processing',
                'Truncated',
                'Only the first ' . $limit . ' processing indexers are shown in this short AI context. See full profile for all index data.'
            );
        }
    }

    protected function addIndexStaleDateHighlights(AiContext $context, array $indexers)
    {
        $count = 0;
        $limit = 40;

        foreach ($indexers as $indexer) {
            if (!$this->isPotentiallyStaleIndexer($indexer)) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Index Potentially Stale Dates',
                    $indexer['code'],
                    'name=' . $indexer['name']
                    . '; status=' . $indexer['status']
                    . '; mode=' . $indexer['mode']
                    . '; lastStarted=' . $indexer['last_started']
                    . '; lastEnded=' . $indexer['last_ended']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Index Potentially Stale Dates',
                'Truncated',
                'Only the first ' . $limit . ' potentially stale indexers are shown in this short AI context. See full profile for all index data.'
            );
        }
    }

    protected function addIndexHighImpactHighlights(AiContext $context, array $indexers)
    {
        $count = 0;
        $limit = 60;

        foreach ($indexers as $indexer) {
            if (!$this->isHighImpactIndexer($indexer)) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Index High Impact Indexers',
                    $indexer['code'],
                    'name=' . $indexer['name']
                    . '; status=' . $indexer['status']
                    . '; mode=' . $indexer['mode']
                    . '; lastStarted=' . $indexer['last_started']
                    . '; lastEnded=' . $indexer['last_ended']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Index High Impact Indexers',
                'Truncated',
                'Only the first ' . $limit . ' high-impact indexers are shown in this short AI context. See full profile for all index data.'
            );
        }
    }

    protected function isPotentiallyStaleIndexer(array $indexer)
    {
        if ($indexer['last_started'] === '' || $indexer['last_started'] === '[never]') {
            return true;
        }

        if (strpos($indexer['last_started'], '2016-') === 0) {
            return true;
        }

        if (strpos($indexer['last_started'], '2017-') === 0) {
            return true;
        }

        if (strpos($indexer['last_started'], '2018-') === 0) {
            return true;
        }

        if (strpos($indexer['last_started'], '2019-') === 0) {
            return true;
        }

        if (strpos($indexer['last_started'], '2020-') === 0) {
            return true;
        }

        return false;
    }

    protected function isHighImpactIndexer(array $indexer)
    {
        if ($indexer['status'] === 'require_reindex' || $indexer['status'] === 'processing') {
            return true;
        }

        if ($this->isPotentiallyStaleIndexer($indexer)) {
            return true;
        }

        $needles = array(
            'catalog',
            'category',
            'inventory',
            'price',
            'product',
            'search',
            'stock',
            'url',
        );

        $haystack = strtolower($indexer['code'] . ' ' . $indexer['name']);

        foreach ($needles as $needle) {
            if (strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function extractCacheHighlights(AiContext $context, array $section)
    {
        $cacheTypes = $this->parseCacheRows($section);

        $this->addCacheEnabledTypes($context, $cacheTypes);
        $this->addCacheDisabledTypes($context, $cacheTypes);
        $this->addCacheOperationalRisks($context, $section, $cacheTypes);
    }

    protected function parseCacheRows(array $section)
    {
        $cacheTypes = array();
        $currentType = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Cache type') {
                if ($currentType !== null) {
                    $cacheTypes[] = $currentType;
                }

                $currentType = array(
                    'type' => $value,
                    'status' => '',
                );

                continue;
            }

            if ($currentType === null) {
                continue;
            }

            if ($key === 'Status') {
                $currentType['status'] = $value;
            }
        }

        if ($currentType !== null) {
            $cacheTypes[] = $currentType;
        }

        return $cacheTypes;
    }

    protected function addCacheEnabledTypes(AiContext $context, array $cacheTypes)
    {
        $count = 0;

        foreach ($cacheTypes as $cacheType) {
            if ($cacheType['status'] !== 'enabled') {
                continue;
            }

            $count++;

            $context->addItem(
                'Cache Enabled Types',
                $cacheType['type'],
                'status=' . $cacheType['status']
            );
        }

        if ($count === 0) {
            $context->addItem('Cache Enabled Types', 'none', 'No enabled cache types were reported.');
        }
    }

    protected function addCacheDisabledTypes(AiContext $context, array $cacheTypes)
    {
        $count = 0;

        foreach ($cacheTypes as $cacheType) {
            if ($cacheType['status'] !== 'disabled') {
                continue;
            }

            $count++;

            $context->addItem(
                'Cache Disabled Types',
                $cacheType['type'],
                'status=' . $cacheType['status']
            );
        }

        if ($count === 0) {
            $context->addItem('Cache Disabled Types', 'none', 'No disabled cache types were reported.');
        }
    }

    protected function addCacheOperationalRisks(AiContext $context, array $section, array $cacheTypes)
    {
        $enabled = $this->item($section, 'Summary / enabled cache types');
        $disabled = $this->item($section, 'Summary / disabled cache types');
        $total = $this->item($section, 'Summary / cache types');
        $backend = $this->item($section, 'Backend class');
        $prefix = $this->item($section, 'Cache prefix');
        $sessionSave = $this->item($section, 'Session save');

        if ($total !== '[unknown]' && $enabled === '0') {
            $context->addItem(
                'Cache Operational Risks',
                'all cache types disabled',
                'enabled=' . $enabled . '; disabled=' . $disabled . '; total=' . $total
            );
        }

        if ($backend === 'Zend_Cache_Backend_File') {
            $context->addItem(
                'Cache Operational Risks',
                'file cache backend',
                'backend=' . $backend . '; prefix=' . $prefix
            );
        }

        if ($prefix === '[none]' || $prefix === '') {
            $context->addItem(
                'Cache Operational Risks',
                'cache prefix missing',
                'prefix=' . $prefix
            );
        }

        if ($sessionSave === '[unknown]' || $sessionSave === '') {
            $context->addItem(
                'Cache Operational Risks',
                'session save unknown',
                'sessionSave=' . $sessionSave
            );
        }
    }

}
