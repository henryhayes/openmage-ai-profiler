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

    public function codeCore()
    {
        return $this->code() . DIRECTORY_SEPARATOR . 'core';
    }

    public function codeCommunity()
    {
        return $this->code() . DIRECTORY_SEPARATOR . 'community';
    }

    public function codeLocal()
    {
        return $this->code() . DIRECTORY_SEPARATOR . 'local';
    }

    public function locale()
    {
        return $this->app() . DIRECTORY_SEPARATOR . 'locale';
    }

    public function cache()
    {
        return $this->var() . DIRECTORY_SEPARATOR . 'cache';
    }

    public function session()
    {
        return $this->var() . DIRECTORY_SEPARATOR . 'session';
    }

    public function log()
    {
        return $this->var() . DIRECTORY_SEPARATOR . 'log';
    }
}