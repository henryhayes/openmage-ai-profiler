<?php

class CronCollector extends AbstractCollector
{
    public function getCode()
    {
        return 'cron';
    }

    public function getTitle()
    {
        return 'Cron';
    }

    public function getCategory()
    {
        return 'Architecture';
    }

    public function getDescription()
    {
        return 'Reports Magento cron job declarations from merged configuration.';
    }

    public function getSince()
    {
        return '0.5.0';
    }

    public function getDependencies()
    {
        return array('magento_bootstrap', 'modules');
    }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Reports Magento cron job declarations, schedules and target model methods.',
            'Mage merged configuration',
            'High'
        );

        if (!$context->isMageBootstrapped()) {
            $section->addError('Magento was not bootstrapped, so cron information is unavailable.');
            return;
        }

        $jobsNode = Mage::getConfig()->getNode('crontab/jobs');

        if (!$jobsNode) {
            $section->addItem('Summary / total cron jobs', 0);
            return;
        }

        $rows = array();

        $totalJobs = 0;
        $jobsWithSchedule = 0;
        $jobsWithConfigPath = 0;
        $jobsWithoutSchedule = 0;
        $missingClassFiles = 0;
        $customJobs = 0;

        foreach ($jobsNode->children() as $jobCode => $jobNode) {
            $totalJobs++;

            $schedule = $this->getSchedule($jobNode);
            $configPath = $this->getConfigPath($jobNode);
            $model = $this->getModel($jobNode);

            if ($schedule !== '[none]') {
                $jobsWithSchedule++;
            }

            if ($configPath !== '[none]') {
                $jobsWithConfigPath++;
            }

            if ($schedule === '[none]' && $configPath === '[none]') {
                $jobsWithoutSchedule++;
            }

            $classAlias = $this->getModelAlias($model);
            $method = $this->getModelMethod($model);
            $resolvedClass = $this->resolveModelClass($classAlias);
            $classFile = $this->classToFile($resolvedClass);
            $classFileExists = ($classFile !== '' && file_exists($classFile)) ? 'yes' : 'no';

            if ($classFileExists === 'no') {
                $missingClassFiles++;
            }

            $isCustom = $this->isCustomClass($resolvedClass) ? 'yes' : 'no';

            if ($isCustom === 'yes') {
                $customJobs++;
            }

            $rows[] = array(
                'job_code' => (string)$jobCode,
                'schedule' => $schedule,
                'config_path' => $configPath,
                'model' => $model,
                'model_alias' => $classAlias,
                'method' => $method,
                'resolved_class' => $resolvedClass,
                'custom' => $isCustom,
                'class_file_exists' => $classFileExists,
                'class_file' => $classFile !== '' ? $classFile : '[unknown]',
            );
        }

        usort($rows, array($this, 'sortRows'));

        $section->addItem('Summary / total cron jobs', $totalJobs);
        $section->addItem('Summary / jobs with inline schedule', $jobsWithSchedule);
        $section->addItem('Summary / jobs with config schedule path', $jobsWithConfigPath);
        $section->addItem('Summary / jobs without schedule', $jobsWithoutSchedule);
        $section->addItem('Summary / custom cron jobs', $customJobs);
        $section->addItem('Summary / missing cron class files', $missingClassFiles);

        foreach ($rows as $row) {
            $section->addItem('Cron job', $row['job_code']);
            $section->addItem('  Schedule', $row['schedule']);
            $section->addItem('  Config schedule path', $row['config_path']);
            $section->addItem('  Model', $row['model']);
            $section->addItem('  Model alias', $row['model_alias']);
            $section->addItem('  Method', $row['method']);
            $section->addItem('  Resolved class', $row['resolved_class']);
            $section->addItem('  Custom', $row['custom']);
            $section->addItem('  Class file exists', $row['class_file_exists']);
            $section->addItem('  Class file', $row['class_file']);
        }
    }

    protected function getSchedule($jobNode)
    {
        if ($jobNode->schedule && $jobNode->schedule->cron_expr) {
            $value = trim((string)$jobNode->schedule->cron_expr);

            if ($value !== '') {
                return $value;
            }
        }

        return '[none]';
    }

    protected function getConfigPath($jobNode)
    {
        if ($jobNode->schedule && $jobNode->schedule->config_path) {
            $value = trim((string)$jobNode->schedule->config_path);

            if ($value !== '') {
                return $value;
            }
        }

        return '[none]';
    }

    protected function getModel($jobNode)
    {
        if ($jobNode->run && $jobNode->run->model) {
            $value = trim((string)$jobNode->run->model);

            if ($value !== '') {
                return $value;
            }
        }

        return '[none]';
    }

    protected function getModelAlias($model)
    {
        if ($model === '' || $model === '[none]') {
            return '[none]';
        }

        $parts = explode('::', $model, 2);

        return trim($parts[0]);
    }

    protected function getModelMethod($model)
    {
        if ($model === '' || $model === '[none]') {
            return '[none]';
        }

        $parts = explode('::', $model, 2);

        if (isset($parts[1]) && trim($parts[1]) !== '') {
            return trim($parts[1]);
        }

        return '[none]';
    }

    protected function resolveModelClass($alias)
    {
        if ($alias === '' || $alias === '[none]') {
            return '[unknown]';
        }

        if (strpos($alias, '/') === false) {
            return $alias;
        }

        try {
            return Mage::getConfig()->getModelClassName($alias);
        } catch (Exception $e) {
            return '[unknown]';
        }
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

    protected function sortRows($a, $b)
    {
        return strcmp($a['job_code'], $b['job_code']);
    }
}