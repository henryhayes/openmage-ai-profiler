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
        return 'Information about this profiler execution.';
    }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'Describes how and where the profiler was executed.',
            'Profiler runtime context',
            'High'
        );

        $section->addItem('Project root', $context->getProjectRoot());
        $section->addItem('Magento root', $context->getMagentoRoot());
        $section->addItem('Working directory', getcwd());
        $section->addItem('Current user', function_exists('get_current_user') ? get_current_user() : '[unknown]');
        $section->addItem('Default timezone', date_default_timezone_get());
    }
}