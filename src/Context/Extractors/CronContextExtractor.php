<?php

class CronContextExtractor extends AbstractAiContextExtractor
{
    public function extract(AiContext $context, array $data)
    {
        $this->extractCron($context, $data);
    }
    protected function extractCron(AiContext $context, array $data)
    {
        $section = $this->findSection($data, 'cron');

        if (!$section) {
            return;
        }

        $summaryKeys = array(
            'Summary / total cron jobs',
            'Summary / jobs with inline schedule',
            'Summary / jobs with config schedule path',
            'Summary / jobs without schedule',
            'Summary / custom cron jobs',
            'Summary / missing cron class files',
        );

        foreach ($summaryKeys as $key) {
            $value = $this->item($section, $key);

            if ($value !== '[unknown]') {
                $context->addItem(
                    'Cron Architecture',
                    str_replace('Summary / ', '', $key),
                    $value
                );
            }
        }

        $this->extractCronHighlights($context, $section);
    }
    
    protected function extractCronHighlights(AiContext $context, array $section)
    {
        $cronJobs = $this->parseCronRows($section);

        $this->addCronCustomHighlights($context, $cronJobs);
        $this->addCronWithoutScheduleHighlights($context, $cronJobs);
        $this->addCronConfigPathHighlights($context, $cronJobs);
        $this->addCronMissingClassHighlights($context, $cronJobs);
        $this->addCronHighImpactHighlights($context, $cronJobs);
    }

    protected function parseCronRows(array $section)
    {
        $cronJobs = array();
        $currentJob = null;

        foreach ($section['items'] as $item) {
            $key = trim($item['key']);
            $value = trim((string)$item['value']);

            if ($key === 'Cron job') {
                if ($currentJob !== null) {
                    $cronJobs[] = $currentJob;
                }

                $currentJob = array(
                    'job_code' => $value,
                    'schedule' => '',
                    'config_schedule_path' => '',
                    'model' => '',
                    'model_alias' => '',
                    'method' => '',
                    'resolved_class' => '',
                    'custom' => '',
                    'class_file_exists' => '',
                    'class_file' => '',
                );

                continue;
            }

            if ($currentJob === null) {
                continue;
            }

            if ($key === 'Schedule') {
                $currentJob['schedule'] = $value;
            } elseif ($key === 'Config schedule path') {
                $currentJob['config_schedule_path'] = $value;
            } elseif ($key === 'Model') {
                $currentJob['model'] = $value;
            } elseif ($key === 'Model alias') {
                $currentJob['model_alias'] = $value;
            } elseif ($key === 'Method') {
                $currentJob['method'] = $value;
            } elseif ($key === 'Resolved class') {
                $currentJob['resolved_class'] = $value;
            } elseif ($key === 'Custom') {
                $currentJob['custom'] = $value;
            } elseif ($key === 'Class file exists') {
                $currentJob['class_file_exists'] = $value;
            } elseif ($key === 'Class file') {
                $currentJob['class_file'] = $value;
            }
        }

        if ($currentJob !== null) {
            $cronJobs[] = $currentJob;
        }

        return $cronJobs;
    }

    protected function addCronCustomHighlights(AiContext $context, array $cronJobs)
    {
        $count = 0;
        $limit = 40;

        foreach ($cronJobs as $cronJob) {
            if ($cronJob['custom'] !== 'yes') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Cron Custom Jobs',
                    $cronJob['job_code'],
                    'schedule=' . $cronJob['schedule']
                    . '; configPath=' . $cronJob['config_schedule_path']
                    . '; model=' . $cronJob['model']
                    . '; resolved=' . $cronJob['resolved_class']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Cron Custom Jobs',
                'Truncated',
                'Only the first ' . $limit . ' custom cron jobs are shown in this short AI context. See full profile for all cron data.'
            );
        }
    }

    protected function addCronWithoutScheduleHighlights(AiContext $context, array $cronJobs)
    {
        $count = 0;
        $limit = 40;

        foreach ($cronJobs as $cronJob) {
            if ($cronJob['schedule'] !== '[none]' || $cronJob['config_schedule_path'] !== '[none]') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Cron Jobs Without Schedule',
                    $cronJob['job_code'],
                    'model=' . $cronJob['model']
                    . '; resolved=' . $cronJob['resolved_class']
                    . '; method=' . $cronJob['method']
                    . '; custom=' . $cronJob['custom']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Cron Jobs Without Schedule',
                'Truncated',
                'Only the first ' . $limit . ' cron jobs without schedules are shown in this short AI context. See full profile for all cron data.'
            );
        }
    }

    protected function addCronConfigPathHighlights(AiContext $context, array $cronJobs)
    {
        $count = 0;
        $limit = 40;

        foreach ($cronJobs as $cronJob) {
            if ($cronJob['config_schedule_path'] === '[none]' || $cronJob['config_schedule_path'] === '') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Cron Config Schedule Paths',
                    $cronJob['job_code'],
                    'configPath=' . $cronJob['config_schedule_path']
                    . '; model=' . $cronJob['model']
                    . '; resolved=' . $cronJob['resolved_class']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Cron Config Schedule Paths',
                'Truncated',
                'Only the first ' . $limit . ' cron jobs using config schedule paths are shown in this short AI context. See full profile for all cron data.'
            );
        }
    }

    protected function addCronMissingClassHighlights(AiContext $context, array $cronJobs)
    {
        $count = 0;
        $limit = 40;

        foreach ($cronJobs as $cronJob) {
            if ($cronJob['class_file_exists'] !== 'no') {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Cron Missing Classes',
                    $cronJob['job_code'],
                    'model=' . $cronJob['model']
                    . '; modelAlias=' . $cronJob['model_alias']
                    . '; resolved=' . $cronJob['resolved_class']
                    . '; method=' . $cronJob['method']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Cron Missing Classes',
                'Truncated',
                'Only the first ' . $limit . ' cron jobs with missing class files are shown in this short AI context. See full profile for all cron data.'
            );
        }
    }

    protected function addCronHighImpactHighlights(AiContext $context, array $cronJobs)
    {
        $count = 0;
        $limit = 60;

        foreach ($cronJobs as $cronJob) {
            if (!$this->isHighImpactCronJob($cronJob)) {
                continue;
            }

            $count++;

            if ($count <= $limit) {
                $context->addItem(
                    'Cron High Impact Jobs',
                    $cronJob['job_code'],
                    'schedule=' . $cronJob['schedule']
                    . '; configPath=' . $cronJob['config_schedule_path']
                    . '; model=' . $cronJob['model']
                    . '; resolved=' . $cronJob['resolved_class']
                    . '; method=' . $cronJob['method']
                    . '; custom=' . $cronJob['custom']
                );
            }
        }

        if ($count > $limit) {
            $context->addItem(
                'Cron High Impact Jobs',
                'Truncated',
                'Only the first ' . $limit . ' high-impact cron jobs are shown in this short AI context. See full profile for all cron data.'
            );
        }
    }

    protected function isHighImpactCronJob(array $cronJob)
    {
        if ($cronJob['custom'] === 'yes') {
            return true;
        }

        if ($cronJob['class_file_exists'] === 'no') {
            return true;
        }

        if ($cronJob['schedule'] === '[none]' && $cronJob['config_schedule_path'] === '[none]') {
            return true;
        }

        $haystack = strtolower(
            $cronJob['job_code']
            . ' ' . $cronJob['model']
            . ' ' . $cronJob['resolved_class']
            . ' ' . $cronJob['method']
        );

        $needles = array(
            'catalog',
            'customer',
            'email',
            'export',
            'import',
            'index',
            'mail',
            'm2epro',
            'newsletter',
            'order',
            'price',
            'product',
            'quickbooks',
            'queue',
            'report',
            'sales',
            'sitemap',
            'stock',
            'sync',
        );

        foreach ($needles as $needle) {
            if (strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
    
}
