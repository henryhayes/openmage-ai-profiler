<?php

class EnvironmentCollector extends AbstractCollector
{
    public function getCode()
    {
        return 'environment';
    }

    public function getTitle()
    {
        return 'Environment';
    }

    public function getDescription()
    {
        return 'Basic PHP and execution environment information.';
    }

    public function collect(Report $report)
    {
        $section = $this->createSection(
            $report,
            'Environment',
            'Identifies the PHP runtime and basic execution environment.',
            'PHP runtime',
            '100%'
        );

        $section->addItem('PHP version', PHP_VERSION);
        $section->addItem('PHP SAPI', php_sapi_name());
        $section->addItem('Operating system', php_uname());
        $section->addItem('Memory limit', ini_get('memory_limit'));
        $section->addItem('Max execution time', ini_get('max_execution_time'));
        $section->addItem('Loaded extensions', implode(', ', get_loaded_extensions()));
    }
}