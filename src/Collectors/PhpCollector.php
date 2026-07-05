<?php

class PhpCollector extends AbstractCollector
{
    public function getCode()
    {
        return 'php';
    }

    public function getTitle()
    {
        return 'PHP';
    }

    public function getDescription()
    {
        return 'PHP runtime, configuration and extension information.';
    }

    public function collect(Report $report, ProfilerContext $context)
    {
        $section = $this->createSection(
            $report,
            'PHP',
            'Identifies the PHP runtime, configuration, limits and loaded extensions.',
            'PHP runtime',
            'High'
        );

        $section->addItem('PHP version', PHP_VERSION);
        $section->addItem('PHP SAPI', php_sapi_name());
        $section->addItem('PHP binary', defined('PHP_BINARY') ? PHP_BINARY : '[unknown]');
        $section->addItem('Operating system', php_uname());
        $section->addItem('Memory limit', ini_get('memory_limit'));
        $section->addItem('Max execution time', ini_get('max_execution_time'));
        $section->addItem('Upload max filesize', ini_get('upload_max_filesize'));
        $section->addItem('Post max size', ini_get('post_max_size'));
        $section->addItem('Max input vars', ini_get('max_input_vars'));

        $section->addItem('Loaded php.ini', php_ini_loaded_file() ? php_ini_loaded_file() : '[none]');
        $section->addItem('Additional ini files', php_ini_scanned_files() ? php_ini_scanned_files() : '[none]');

        $section->addItem('OPcache loaded', extension_loaded('Zend OPcache') ? 'yes' : 'no');
        $section->addItem('Xdebug loaded', extension_loaded('xdebug') ? 'yes' : 'no');
        $section->addItem('APCu loaded', extension_loaded('apcu') ? 'yes' : 'no');
        $section->addItem('Redis extension loaded', extension_loaded('redis') ? 'yes' : 'no');
        $section->addItem('ionCube loaded', extension_loaded('ionCube Loader') ? 'yes' : 'no');

        $section->addItem('Loaded extensions', implode(', ', get_loaded_extensions()));
    }
}