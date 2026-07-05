<?php

class EavCollector extends AbstractCollector
{
    public function getCode() { return 'eav'; }
    public function getTitle() { return 'EAV'; }
    public function getCategory() { return 'Architecture'; }
    public function getDescription() { return 'Reports EAV entity types, attribute sets, attribute groups and important attributes.'; }
    public function getSince() { return '0.8.0'; }
    public function getDependencies() { return array('magento_bootstrap', 'database'); }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Reports EAV entity types, attribute sets, attribute groups and important catalog/customer/category attributes that affect Magento development work.',
            'Magento EAV resource tables and Mage EAV models',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so EAV information is unavailable.');
            return;
        }

        try {
            $resource = Mage::getSingleton('core/resource');
            $connection = $resource->getConnection('core_read');

            $entityTypes = $this->loadEntityTypes($connection, $resource);
            $setsByEntityType = $this->loadAttributeSets($connection, $resource);
            $groupsBySet = $this->loadAttributeGroups($connection, $resource);
            $attributesByEntityType = $this->loadAttributes($connection, $resource);

            $section->addItem('Summary / entity types', count($entityTypes));
            $section->addItem('Summary / attribute sets', $this->countNestedRows($setsByEntityType));
            $section->addItem('Summary / attribute groups', $this->countNestedRows($groupsBySet));
            $section->addItem('Summary / attributes', $this->countNestedRows($attributesByEntityType));

            foreach ($entityTypes as $entityTypeId => $entityType) {
                $entityTypeCode = $entityType['entity_type_code'];

                $section->addItem('Entity type', $entityTypeCode);
                $section->addItem('  Entity type ID', $entityTypeId);
                $section->addItem('  Entity model', $this->valueOrNone($entityType['entity_model']));
                $section->addItem('  Attribute model', $this->valueOrNone($entityType['attribute_model']));
                $section->addItem('  Entity table', $this->valueOrNone($entityType['entity_table']));
                $section->addItem('  Value table prefix', $this->valueOrNone($entityType['value_table_prefix']));

                $sets = isset($setsByEntityType[$entityTypeId]) ? $setsByEntityType[$entityTypeId] : array();
                $attributes = isset($attributesByEntityType[$entityTypeId]) ? $attributesByEntityType[$entityTypeId] : array();

                $section->addItem('  Attribute set count', count($sets));
                $section->addItem('  Attribute count', count($attributes));

                foreach ($sets as $set) {
                    $section->addItem('  Attribute set', $set['attribute_set_name']);
                    $section->addItem('    Attribute set ID', $set['attribute_set_id']);
                    $section->addItem('    Sort order', $this->valueOrNone($set['sort_order']));

                    $groups = isset($groupsBySet[$set['attribute_set_id']]) ? $groupsBySet[$set['attribute_set_id']] : array();
                    $section->addItem('    Attribute groups', $this->formatGroupList($groups));
                }

                $importantAttributes = $this->filterImportantAttributes($entityTypeCode, $attributes);

                $section->addItem('  Important attributes reported', count($importantAttributes));

                foreach ($importantAttributes as $attribute) {
                    $section->addItem('  Attribute', $attribute['attribute_code']);
                    $section->addItem('    Attribute ID', $attribute['attribute_id']);
                    $section->addItem('    Backend type', $attribute['backend_type']);
                    $section->addItem('    Frontend input', $this->valueOrNone($attribute['frontend_input']));
                    $section->addItem('    Backend model', $this->valueOrNone($attribute['backend_model']));
                    $section->addItem('    Source model', $this->valueOrNone($attribute['source_model']));
                    $section->addItem('    Frontend model', $this->valueOrNone($attribute['frontend_model']));
                    $section->addItem('    Required', $this->yesNo($attribute['is_required']));
                    $section->addItem('    User defined', $this->yesNo($attribute['is_user_defined']));
                    $section->addItem('    Global scope', $this->formatScope($attribute));
                    $section->addItem('    Visible', $this->yesNo($attribute['is_visible']));
                    $section->addItem('    Searchable', $this->yesNo($attribute['is_searchable']));
                    $section->addItem('    Filterable', $this->yesNo($attribute['is_filterable']));
                    $section->addItem('    Comparable', $this->yesNo($attribute['is_comparable']));
                    $section->addItem('    Used for promo rules', $this->yesNo($attribute['is_used_for_promo_rules']));
                    $section->addItem('    Used in product listing', $this->yesNo($attribute['used_in_product_listing']));
                    $section->addItem('    Used for sorting', $this->yesNo($attribute['used_for_sort_by']));
                    $section->addItem('    Apply to product types', $this->valueOrAll($attribute['apply_to']));
                }
            }
        } catch (Exception $e) {
            $section->addError($e->getMessage());
        }
    }

    protected function loadEntityTypes($connection, $resource)
    {
        $table = $resource->getTableName('eav/entity_type');

        $rows = $connection->fetchAll(
            'SELECT entity_type_id, entity_type_code, entity_model, attribute_model, entity_table, value_table_prefix '
            . 'FROM ' . $connection->quoteIdentifier($table) . ' ORDER BY entity_type_code'
        );

        $result = array();

        foreach ($rows as $row) {
            $result[$row['entity_type_id']] = $row;
        }

        return $result;
    }

    protected function loadAttributeSets($connection, $resource)
    {
        $table = $resource->getTableName('eav/attribute_set');

        $rows = $connection->fetchAll(
            'SELECT attribute_set_id, entity_type_id, attribute_set_name, sort_order '
            . 'FROM ' . $connection->quoteIdentifier($table) . ' ORDER BY entity_type_id, sort_order, attribute_set_name'
        );

        $result = array();

        foreach ($rows as $row) {
            $entityTypeId = $row['entity_type_id'];

            if (!isset($result[$entityTypeId])) {
                $result[$entityTypeId] = array();
            }

            $result[$entityTypeId][] = $row;
        }

        return $result;
    }

    protected function loadAttributeGroups($connection, $resource)
    {
        $table = $resource->getTableName('eav/attribute_group');

        $rows = $connection->fetchAll(
            'SELECT attribute_group_id, attribute_set_id, attribute_group_name, sort_order '
            . 'FROM ' . $connection->quoteIdentifier($table) . ' ORDER BY attribute_set_id, sort_order, attribute_group_name'
        );

        $result = array();

        foreach ($rows as $row) {
            $setId = $row['attribute_set_id'];

            if (!isset($result[$setId])) {
                $result[$setId] = array();
            }

            $result[$setId][] = $row;
        }

        return $result;
    }

    protected function loadAttributes($connection, $resource)
    {
        $attributeTable = $resource->getTableName('eav/attribute');
        $catalogTable = $resource->getTableName('catalog/eav_attribute');

        $catalogColumns = $connection->describeTable($catalogTable);
        $selectColumns = array(
            'a.attribute_id',
            'a.entity_type_id',
            'a.attribute_code',
            'a.backend_type',
            'a.frontend_input',
            'a.backend_model',
            'a.source_model',
            'a.frontend_model',
            'a.is_required',
            'a.is_user_defined'
        );

        foreach ($this->getCatalogAttributeColumns() as $column) {
            if (isset($catalogColumns[$column])) {
                $selectColumns[] = 'ca.' . $column;
            } else {
                $selectColumns[] = 'NULL AS ' . $column;
            }
        }

        $rows = $connection->fetchAll(
            'SELECT ' . implode(', ', $selectColumns) . ' '
            . 'FROM ' . $connection->quoteIdentifier($attributeTable) . ' a '
            . 'LEFT JOIN ' . $connection->quoteIdentifier($catalogTable) . ' ca ON ca.attribute_id = a.attribute_id '
            . 'ORDER BY a.entity_type_id, a.attribute_code'
        );

        $result = array();

        foreach ($rows as $row) {
            $entityTypeId = $row['entity_type_id'];

            if (!isset($result[$entityTypeId])) {
                $result[$entityTypeId] = array();
            }

            $result[$entityTypeId][] = $row;
        }

        return $result;
    }

    protected function getCatalogAttributeColumns()
    {
        return array(
            'is_global',
            'is_visible',
            'is_searchable',
            'is_filterable',
            'is_comparable',
            'is_used_for_promo_rules',
            'used_in_product_listing',
            'used_for_sort_by',
            'apply_to'
        );
    }

    protected function filterImportantAttributes($entityTypeCode, $attributes)
    {
        $importantCodes = $this->getImportantAttributeCodes($entityTypeCode);
        $result = array();

        foreach ($attributes as $attribute) {
            if (isset($importantCodes[$attribute['attribute_code']])) {
                $result[] = $attribute;
                continue;
            }

            if ((int)$attribute['is_user_defined'] === 1) {
                $result[] = $attribute;
                continue;
            }

            if ((int)$attribute['is_searchable'] === 1
                || (int)$attribute['is_filterable'] > 0
                || (int)$attribute['used_in_product_listing'] === 1
                || (int)$attribute['used_for_sort_by'] === 1
            ) {
                $result[] = $attribute;
            }
        }

        return $result;
    }

    protected function getImportantAttributeCodes($entityTypeCode)
    {
        $codes = array();

        if ($entityTypeCode === 'catalog_product') {
            $codes = array(
                'sku', 'name', 'url_key', 'status', 'visibility', 'type_id', 'attribute_set_id',
                'price', 'special_price', 'special_from_date', 'special_to_date', 'tax_class_id',
                'weight', 'manufacturer', 'color', 'news_from_date', 'news_to_date',
                'meta_title', 'meta_keyword', 'meta_description', 'description', 'short_description',
                'image', 'small_image', 'thumbnail'
            );
        }

        if ($entityTypeCode === 'catalog_category') {
            $codes = array(
                'name', 'url_key', 'url_path', 'is_active', 'include_in_menu', 'display_mode',
                'landing_page', 'is_anchor', 'available_sort_by', 'default_sort_by',
                'page_layout', 'custom_layout_update', 'meta_title', 'meta_keywords',
                'meta_description', 'description', 'image', 'thumbnail'
            );
        }

        if ($entityTypeCode === 'customer') {
            $codes = array(
                'email', 'firstname', 'lastname', 'group_id', 'created_at', 'updated_at',
                'dob', 'gender', 'taxvat', 'website_id', 'store_id'
            );
        }

        if ($entityTypeCode === 'customer_address') {
            $codes = array(
                'firstname', 'lastname', 'company', 'street', 'city', 'region', 'region_id',
                'postcode', 'country_id', 'telephone', 'fax', 'vat_id'
            );
        }

        return array_fill_keys($codes, true);
    }

    protected function formatGroupList($groups)
    {
        $names = array();

        foreach ($groups as $group) {
            $names[] = $group['attribute_group_name'];
        }

        if (count($names) === 0) {
            return '[none]';
        }

        return implode(', ', $names);
    }

    protected function countNestedRows($rows)
    {
        $count = 0;

        foreach ($rows as $group) {
            $count += count($group);
        }

        return $count;
    }

    protected function yesNo($value)
    {
        if ($value === null || $value === '') {
            return '[n/a]';
        }

        return ((int)$value === 1) ? 'yes' : 'no';
    }

    protected function formatScope($attribute)
    {
        if (!isset($attribute['is_global']) || $attribute['is_global'] === null || $attribute['is_global'] === '') {
            return '[n/a]';
        }

        if ((string)$attribute['is_global'] === '0') {
            return 'store view';
        }

        if ((string)$attribute['is_global'] === '1') {
            return 'global';
        }

        if ((string)$attribute['is_global'] === '2') {
            return 'website';
        }

        return (string)$attribute['is_global'];
    }

    protected function valueOrNone($value)
    {
        $value = trim((string)$value);
        return $value !== '' ? $value : '[none]';
    }

    protected function valueOrAll($value)
    {
        $value = trim((string)$value);
        return $value !== '' ? $value : '[all]';
    }
}
