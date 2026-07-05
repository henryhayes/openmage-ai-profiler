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

    public function collect(Report $report, Context $context)
    {
        $section = $this->createSection(
            $report,
            'Environment',
            'Identifies the PHP runtime and basic execution environment.',
            'PHP runtime',
            'High'
        );

        $section->addItem('Project root', $context->getProjectRoot());
        $section->addItem('Magento root', $context->getMagentoRoot());
        $section->addItem('PHP version', PHP_VERSION);
        $section->addItem('PHP SAPI', php_sapi_name());
        $section->addItem('Operating system', php_uname());
        $section->addItem('Memory limit', ini_get('memory_limit'));
        $section->addItem('Max execution time', ini_get('max_execution_time'));
        $section->addItem('Loaded extensions', implode(', ', get_loaded_extensions()));
        $section->addItem('Hostname', gethostname());
        $section->addItem('Current user', function_exists('get_current_user') ? get_current_user() : '[unknown]');
        $section->addItem('Working directory', getcwd());
        $section->addItem('PHP binary', defined('PHP_BINARY') ? PHP_BINARY : '[unknown]');
        $section->addItem('Default timezone', date_default_timezone_get());
        $section->addItem('Loaded php.ini', php_ini_loaded_file() ? php_ini_loaded_file() : '[none]');
        $section->addItem('Additional ini files', php_ini_scanned_files() ? php_ini_scanned_files() : '[none]');
        $section->addItem('OPcache loaded', extension_loaded('Zend OPcache') ? 'yes' : 'no');
        $section->addItem('Xdebug loaded', extension_loaded('xdebug') ? 'yes' : 'no');
        $section->addItem('APCu loaded', extension_loaded('apcu') ? 'yes' : 'no');
        $section->addItem('Redis extension loaded', extension_loaded('redis') ? 'yes' : 'no');
        $section->addItem('ionCube loaded', extension_loaded('ionCube Loader') ? 'yes' : 'no');
    }
}