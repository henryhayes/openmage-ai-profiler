<?php

class RewriteContextExtractor extends AbstractAiContextExtractor
{
    public function extract(AiContext $context, array $data)
    {
        $this->extractRewrites($context, $data);
        $this->extractRewriteMap($context, $data);
    }
    protected function extractRewrites(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'rewrites');

        if (!$section) {
            return;
        }

        $summaryKeys = array(
            'Summary / aliases with rewrites',
            'Summary / total rewrite declarations',
            'Summary / model rewrite declarations',
            'Summary / block rewrite declarations',
            'Summary / helper rewrite declarations',
            'Summary / aliases with conflicts',
            'Summary / missing declared class files',
            'Summary / missing winning class files',
        );

        foreach ($summaryKeys as $key) {
            $value = $this->item($section, $key);
            if ($value !== '[unknown]') {
                $context->addItem('Rewrite Architecture', str_replace('Summary / ', '', $key), $value);
            }
        }

        $this->extractRewriteHighlights($context, $section);
    }

    protected function extractRewriteHighlights(AiContext $context, array $section)
    {
        $rewrites = array();
        $currentRewrite = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Rewrite') {
                if ($currentRewrite !== null) {
                    $rewrites[] = $currentRewrite;
                }

                $currentRewrite = array(
                    'rewrite' => $value,
                    'type' => '',
                    'alias' => '',
                    'original_class' => '',
                    'winning_class' => '',
                    'winning_class_file_exists' => '',
                    'declaration_count' => '',
                    'conflict' => '',
                    'declarations' => array(),
                );

                continue;
            }

            if ($currentRewrite === null) {
                continue;
            }

            if ($key === 'Type') {
                $currentRewrite['type'] = $value;
            } elseif ($key === 'Alias') {
                $currentRewrite['alias'] = $value;
            } elseif ($key === 'Original class') {
                $currentRewrite['original_class'] = $value;
            } elseif ($key === 'Winning class') {
                $currentRewrite['winning_class'] = $value;
            } elseif ($key === 'Winning class file exists') {
                $currentRewrite['winning_class_file_exists'] = $value;
            } elseif ($key === 'Declaration count') {
                $currentRewrite['declaration_count'] = $value;
            } elseif ($key === 'Conflict') {
                $currentRewrite['conflict'] = $value;
            } elseif (strpos($key, 'Declaration / ') === 0) {
                $currentRewrite['declarations'][] = $value;
            }
        }

        if ($currentRewrite !== null) {
            $rewrites[] = $currentRewrite;
        }

        $this->addRewriteConflictHighlights($context, $rewrites);
        $this->addRewriteMissingClassHighlights($context, $rewrites);
        $this->addRewriteCustomWinningHighlights($context, $rewrites);
    }

    protected function addRewriteConflictHighlights(AiContext $context, array $rewrites)
    {
        $count = 0;
        $limit = 20;

        foreach ($rewrites as $rewrite) {
            if ($rewrite['conflict'] !== 'yes') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Rewrite Conflicts',
                    $rewrite['rewrite'],
                    'alias=' . $rewrite['alias']
                    . '; winning=' . $rewrite['winning_class']
                    . '; declarations=' . implode(' | ', $rewrite['declarations'])
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Rewrite Conflicts',
                'Truncated',
                'Only the first ' . $limit . ' rewrite conflicts are shown in this short AI context. See full profile for all rewrite data.'
            );
        }
    }

    protected function addRewriteMissingClassHighlights(AiContext $context, array $rewrites)
    {
        $missingDeclaredCount = 0;
        $missingWinningCount = 0;
        $limit = 20;

        foreach ($rewrites as $rewrite) {
            if ($rewrite['winning_class_file_exists'] === 'no') {
                $missingWinningCount++;

                if ($missingWinningCount <= $limit) {
                    $context->addItem(
                        'Rewrite Missing Winning Classes',
                        $rewrite['rewrite'],
                        'alias=' . $rewrite['alias']
                        . '; winning=' . $rewrite['winning_class']
                        . '; original=' . $rewrite['original_class']
                    );
                }
            }

            foreach ($rewrite['declarations'] as $declaration) {
                if (strpos($declaration, 'classFileExists=no') === false) {
                    continue;
                }

                $missingDeclaredCount++;

                if ($missingDeclaredCount <= $limit) {
                    $context->addItem(
                        'Rewrite Missing Declared Classes',
                        $rewrite['rewrite'],
                        $declaration
                    );
                }
            }
        }

        if ($missingWinningCount > $limit) {
            $context->addItem(
                'Rewrite Missing Winning Classes',
                'Truncated',
                'Only the first ' . $limit . ' missing winning rewrite classes are shown in this short AI context. See full profile for all rewrite data.'
            );
        }

        if ($missingDeclaredCount > $limit) {
            $context->addItem(
                'Rewrite Missing Declared Classes',
                'Truncated',
                'Only the first ' . $limit . ' missing declared rewrite classes are shown in this short AI context. See full profile for all rewrite data.'
            );
        }
    }

    protected function addRewriteCustomWinningHighlights(AiContext $context, array $rewrites)
    {
        $count = 0;
        $limit = 40;

        foreach ($rewrites as $rewrite) {
            if (!$this->isCustomRewriteClass($rewrite['winning_class'])) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Rewrite Custom Winning Classes',
                    $rewrite['rewrite'],
                    'alias=' . $rewrite['alias']
                    . '; original=' . $rewrite['original_class']
                    . '; winning=' . $rewrite['winning_class']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Rewrite Custom Winning Classes',
                'Truncated',
                'Only the first ' . $limit . ' custom winning rewrite classes are shown in this short AI context. See full profile for all rewrite data.'
            );
        }
    }

    protected function isCustomRewriteClass($class)
    {
        if ($class === '' || $class === '[unknown]') {
            return false;
        }

        $corePrefixes = array(
            'Mage_',
            'Enterprise_',
            'Varien_',
            'Zend_',
        );

        foreach ($corePrefixes as $prefix) {
            if (strpos($class, $prefix) === 0) {
                return false;
            }
        }

        return true;
    }

    protected function extractRewriteMap(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'rewrite_map');

        if (!$section) {
            return;
        }

        $summaryKeys = array(
            'Summary / resolved rewrite aliases',
            'Summary / custom winning classes',
            'Summary / missing winning class files',
        );

        foreach ($summaryKeys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem('Rewrite Map', str_replace('Summary / ', '', $key), $value);
            }
        }
    }
    
}
