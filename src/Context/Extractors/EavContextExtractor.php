<?php

class EavContextExtractor extends AbstractAiContextExtractor
{
    public function extract(AiContext $context, array $data)
    {
        $this->extractEav($context, $data);
    }
    protected function extractEav(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'eav');

        if (!$section) {
            return;
        }

        $summaryKeys = array(
            'Summary / entity types',
            'Summary / attribute sets',
            'Summary / attribute groups',
            'Summary / attributes',
        );

        foreach ($summaryKeys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('EAV Architecture', str_replace('Summary / ', '', $key), $value);
            }
        }

        $this->extractEavHighlights($context, $section);
    }
    
    protected function extractEavHighlights(AiContext $context, array $section)
    {
        $rows = $this->parseEavRows($section);

        $this->addEavEntityTypeHighlights($context, $rows['entity_types']);
        $this->addEavAttributeSetHighlights($context, $rows['attribute_sets']);
        $this->addEavCustomAttributeHighlights($context, $rows['attributes']);
        $this->addEavModelAttributeHighlights($context, $rows['attributes']);
        $this->addEavSearchFilterHighlights($context, $rows['attributes']);
        $this->addEavListingSortingHighlights($context, $rows['attributes']);
        $this->addEavRequiredCustomHighlights($context, $rows['attributes']);
    }

    protected function parseEavRows(array $section)
    {
        $entityTypes = array();
        $attributeSets = array();
        $attributes = array();

        $currentEntityType = null;
        $currentAttribute = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Entity type') {
                if ($currentAttribute !== null) {
                    $attributes[] = $currentAttribute;
                    $currentAttribute = null;
                }

                $currentEntityType = array(
                    'entity_type' => $value,
                    'entity_type_id' => '',
                    'entity_model' => '',
                    'attribute_model' => '',
                    'entity_table' => '',
                    'value_table_prefix' => '',
                    'attribute_set_count' => '',
                    'attribute_count' => '',
                    'important_attributes_reported' => '',
                );

                $entityTypes[] = $currentEntityType;
                continue;
            }

            if (!count($entityTypes)) {
                continue;
            }

            $entityIndex = count($entityTypes) - 1;

            if ($key === 'Attribute set') {
                if ($currentAttribute !== null) {
                    $attributes[] = $currentAttribute;
                    $currentAttribute = null;
                }

                $attributeSets[] = array(
                    'entity_type' => $entityTypes[$entityIndex]['entity_type'],
                    'attribute_set' => $value,
                    'attribute_set_id' => '',
                    'sort_order' => '',
                    'attribute_groups' => '',
                );

                continue;
            }

            if ($key === 'Attribute') {
                if ($currentAttribute !== null) {
                    $attributes[] = $currentAttribute;
                }

                $currentAttribute = array(
                    'entity_type' => $entityTypes[$entityIndex]['entity_type'],
                    'attribute_code' => $value,
                    'attribute_id' => '',
                    'backend_type' => '',
                    'frontend_input' => '',
                    'backend_model' => '',
                    'source_model' => '',
                    'frontend_model' => '',
                    'required' => '',
                    'user_defined' => '',
                    'global_scope' => '',
                    'visible' => '',
                    'searchable' => '',
                    'filterable' => '',
                    'comparable' => '',
                    'used_for_promo_rules' => '',
                    'used_in_product_listing' => '',
                    'used_for_sorting' => '',
                    'apply_to_product_types' => '',
                );

                continue;
            }

            if ($currentAttribute !== null) {
                $this->applyEavAttributeField($currentAttribute, $key, $value);
                continue;
            }

            if (count($attributeSets)) {
                $setIndex = count($attributeSets) - 1;

                if ($key === 'Attribute set ID') {
                    $attributeSets[$setIndex]['attribute_set_id'] = $value;
                    continue;
                }

                if ($key === 'Sort order') {
                    $attributeSets[$setIndex]['sort_order'] = $value;
                    continue;
                }

                if ($key === 'Attribute groups') {
                    $attributeSets[$setIndex]['attribute_groups'] = $value;
                    continue;
                }
            }

            if ($key === 'Entity type ID') {
                $entityTypes[$entityIndex]['entity_type_id'] = $value;
            } elseif ($key === 'Entity model') {
                $entityTypes[$entityIndex]['entity_model'] = $value;
            } elseif ($key === 'Attribute model') {
                $entityTypes[$entityIndex]['attribute_model'] = $value;
            } elseif ($key === 'Entity table') {
                $entityTypes[$entityIndex]['entity_table'] = $value;
            } elseif ($key === 'Value table prefix') {
                $entityTypes[$entityIndex]['value_table_prefix'] = $value;
            } elseif ($key === 'Attribute set count') {
                $entityTypes[$entityIndex]['attribute_set_count'] = $value;
            } elseif ($key === 'Attribute count') {
                $entityTypes[$entityIndex]['attribute_count'] = $value;
            } elseif ($key === 'Important attributes reported') {
                $entityTypes[$entityIndex]['important_attributes_reported'] = $value;
            }
        }

        if ($currentAttribute !== null) {
            $attributes[] = $currentAttribute;
        }

        return array(
            'entity_types' => $entityTypes,
            'attribute_sets' => $attributeSets,
            'attributes' => $attributes,
        );
    }

    protected function applyEavAttributeField(array &$attribute, $key, $value)
    {
        if ($key === 'Attribute ID') {
            $attribute['attribute_id'] = $value;
        } elseif ($key === 'Backend type') {
            $attribute['backend_type'] = $value;
        } elseif ($key === 'Frontend input') {
            $attribute['frontend_input'] = $value;
        } elseif ($key === 'Backend model') {
            $attribute['backend_model'] = $value;
        } elseif ($key === 'Source model') {
            $attribute['source_model'] = $value;
        } elseif ($key === 'Frontend model') {
            $attribute['frontend_model'] = $value;
        } elseif ($key === 'Required') {
            $attribute['required'] = $value;
        } elseif ($key === 'User defined') {
            $attribute['user_defined'] = $value;
        } elseif ($key === 'Global scope') {
            $attribute['global_scope'] = $value;
        } elseif ($key === 'Visible') {
            $attribute['visible'] = $value;
        } elseif ($key === 'Searchable') {
            $attribute['searchable'] = $value;
        } elseif ($key === 'Filterable') {
            $attribute['filterable'] = $value;
        } elseif ($key === 'Comparable') {
            $attribute['comparable'] = $value;
        } elseif ($key === 'Used for promo rules') {
            $attribute['used_for_promo_rules'] = $value;
        } elseif ($key === 'Used in product listing') {
            $attribute['used_in_product_listing'] = $value;
        } elseif ($key === 'Used for sorting') {
            $attribute['used_for_sorting'] = $value;
        } elseif ($key === 'Apply to product types') {
            $attribute['apply_to_product_types'] = $value;
        }
    }

    protected function addEavEntityTypeHighlights(AiContext $context, array $entityTypes)
    {
        foreach ($entityTypes as $entityType) {
            $context->addItem(
                'EAV Entity Types',
                $entityType['entity_type'],
                'entityModel=' . $entityType['entity_model']
                . '; attributeModel=' . $entityType['attribute_model']
                . '; table=' . $entityType['entity_table']
                . '; attributeSets=' . $entityType['attribute_set_count']
                . '; attributes=' . $entityType['attribute_count']
                . '; importantReported=' . $entityType['important_attributes_reported']
            );
        }
    }

    protected function addEavAttributeSetHighlights(AiContext $context, array $attributeSets)
    {
        $count = 0;
        $limit = 80;

        foreach ($attributeSets as $attributeSet) {
            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'EAV Attribute Sets',
                    $attributeSet['entity_type'] . ' / ' . $attributeSet['attribute_set'],
                    'id=' . $attributeSet['attribute_set_id']
                    . '; groups=' . $this->summariseEavGroups($attributeSet['attribute_groups'])
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'EAV Attribute Sets',
                'Truncated',
                'Only the first ' . $limit . ' EAV attribute sets are shown in this short AI context. See full profile for all EAV data.'
            );
        }
    }

    protected function summariseEavGroups($groups)
    {
        if ($groups === '' || $groups === '[unknown]' || $groups === '[none]') {
            return $groups;
        }

        $parts = explode(',', $groups);
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, 'strlen');

        if (count($parts) <= 12) {
            return implode(', ', $parts);
        }

        return implode(', ', array_slice($parts, 0, 12)) . ', ...';
    }

    protected function addEavCustomAttributeHighlights(AiContext $context, array $attributes)
    {
        $sections = array(
            'catalog_product' => 'EAV Custom Product Attributes',
            'catalog_category' => 'EAV Custom Category Attributes',
            'customer' => 'EAV Custom Customer Attributes',
            'customer_address' => 'EAV Custom Customer Address Attributes',
        );

        $counts = array();

        foreach ($attributes as $attribute) {
            if ($attribute['user_defined'] !== 'yes') {
                continue;
            }

            $section = 'EAV Custom Other Attributes';

            if (isset($sections[$attribute['entity_type']])) {
                $section = $sections[$attribute['entity_type']];
            }

            if (!isset($counts[$section])) {
                $counts[$section] = 0;
            }

            $counts[$section]++;

            if ($counts[$section] <= 80) {
                $context->addItem(
                    $section,
                    $attribute['attribute_code'],
                    $this->formatEavAttributeSummary($attribute)
                );
            }
        }

        foreach ($counts as $section => $count) {
            if ($count > 80) {
                $context->addItem(
                    $section,
                    'Truncated',
                    'Only the first 80 custom attributes are shown in this short AI context. See full profile for all EAV data.'
                );
            }
        }
    }

    protected function addEavModelAttributeHighlights(AiContext $context, array $attributes)
    {
        $count = 0;
        $limit = 100;

        foreach ($attributes as $attribute) {
            if (!$this->hasImportantEavModel($attribute)) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'EAV Attributes With Models',
                    $attribute['entity_type'] . ' / ' . $attribute['attribute_code'],
                    'backend=' . $attribute['backend_model']
                    . '; source=' . $attribute['source_model']
                    . '; frontend=' . $attribute['frontend_model']
                    . '; input=' . $attribute['frontend_input']
                    . '; userDefined=' . $attribute['user_defined']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'EAV Attributes With Models',
                'Truncated',
                'Only the first ' . $limit . ' attributes with backend/source/frontend models are shown in this short AI context. See full profile for all EAV data.'
            );
        }
    }

    protected function hasImportantEavModel(array $attribute)
    {
        if ($this->isMeaningfulEavModel($attribute['backend_model'])) {
            return true;
        }

        if ($this->isMeaningfulEavModel($attribute['source_model'])) {
            return true;
        }

        if ($this->isMeaningfulEavModel($attribute['frontend_model'])) {
            return true;
        }

        return false;
    }

    protected function isMeaningfulEavModel($model)
    {
        if ($model === '' || $model === '[none]' || $model === '[unknown]' || $model === '[n/a]') {
            return false;
        }

        return true;
    }

    protected function addEavSearchFilterHighlights(AiContext $context, array $attributes)
    {
        $count = 0;
        $limit = 100;

        foreach ($attributes as $attribute) {
            if ($attribute['entity_type'] !== 'catalog_product') {
                continue;
            }

            if (
                $attribute['searchable'] !== 'yes'
                && $attribute['filterable'] !== 'yes'
                && $attribute['comparable'] !== 'yes'
                && $attribute['used_for_promo_rules'] !== 'yes'
            ) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'EAV Search Filter Promo Attributes',
                    $attribute['attribute_code'],
                    'input=' . $attribute['frontend_input']
                    . '; scope=' . $attribute['global_scope']
                    . '; searchable=' . $attribute['searchable']
                    . '; filterable=' . $attribute['filterable']
                    . '; comparable=' . $attribute['comparable']
                    . '; promoRules=' . $attribute['used_for_promo_rules']
                    . '; userDefined=' . $attribute['user_defined']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'EAV Search Filter Promo Attributes',
                'Truncated',
                'Only the first ' . $limit . ' product search/filter/comparable/promo attributes are shown in this short AI context. See full profile for all EAV data.'
            );
        }
    }

    protected function addEavListingSortingHighlights(AiContext $context, array $attributes)
    {
        $count = 0;
        $limit = 100;

        foreach ($attributes as $attribute) {
            if ($attribute['entity_type'] !== 'catalog_product') {
                continue;
            }

            if ($attribute['used_in_product_listing'] !== 'yes' && $attribute['used_for_sorting'] !== 'yes') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'EAV Product Listing Sorting Attributes',
                    $attribute['attribute_code'],
                    'input=' . $attribute['frontend_input']
                    . '; scope=' . $attribute['global_scope']
                    . '; listing=' . $attribute['used_in_product_listing']
                    . '; sorting=' . $attribute['used_for_sorting']
                    . '; userDefined=' . $attribute['user_defined']
                    . '; appliesTo=' . $attribute['apply_to_product_types']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'EAV Product Listing Sorting Attributes',
                'Truncated',
                'Only the first ' . $limit . ' product listing/sorting attributes are shown in this short AI context. See full profile for all EAV data.'
            );
        }
    }

    protected function addEavRequiredCustomHighlights(AiContext $context, array $attributes)
    {
        $count = 0;
        $limit = 80;

        foreach ($attributes as $attribute) {
            if ($attribute['required'] !== 'yes' || $attribute['user_defined'] !== 'yes') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'EAV Required Custom Attributes',
                    $attribute['entity_type'] . ' / ' . $attribute['attribute_code'],
                    $this->formatEavAttributeSummary($attribute)
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'EAV Required Custom Attributes',
                'Truncated',
                'Only the first ' . $limit . ' required custom attributes are shown in this short AI context. See full profile for all EAV data.'
            );
        }
    }

    protected function formatEavAttributeSummary(array $attribute)
    {
        return 'id=' . $attribute['attribute_id']
            . '; type=' . $attribute['backend_type']
            . '; input=' . $attribute['frontend_input']
            . '; required=' . $attribute['required']
            . '; scope=' . $attribute['global_scope']
            . '; visible=' . $attribute['visible']
            . '; appliesTo=' . $attribute['apply_to_product_types'];
    }
    
}
