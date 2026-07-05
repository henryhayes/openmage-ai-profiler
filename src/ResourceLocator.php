<?php

class ResourceLocator
{
    protected $root;

    public function __construct($root)
    {
        $this->root = rtrim($root, '/\\');
    }

    public function app()
    {
        return $this->root . DIRECTORY_SEPARATOR . 'app';
    }

    public function code()
    {
        return $this->app() . DIRECTORY_SEPARATOR . 'code';
    }

    public function etc()
    {
        return $this->app() . DIRECTORY_SEPARATOR . 'etc';
    }

    public function design()
    {
        return $this->app() . DIRECTORY_SEPARATOR . 'design';
    }

    public function frontendDesign()
    {
        return $this->design() . DIRECTORY_SEPARATOR . 'frontend';
    }

    public function skin()
    {
        return $this->root . DIRECTORY_SEPARATOR . 'skin';
    }

    public function frontendSkin()
    {
        return $this->skin() . DIRECTORY_SEPARATOR . 'frontend';
    }

    public function media()
    {
        return $this->root . DIRECTORY_SEPARATOR . 'media';
    }

    public function var()
    {
        return $this->root . DIRECTORY_SEPARATOR . 'var';
    }

    public function modules()
    {
        return $this->etc() . DIRECTORY_SEPARATOR . 'modules';
    }
}