<?php

class ProfilerContext
{
    protected $projectRoot;
    protected $magentoRoot;
    protected $isMagentoAvailable = false;
    protected $mageBootstrapped = false;

    protected $magentoVersion = null;
    protected $magentoEdition = null;
    protected $magentoBaseDir = null;

    protected $data = array();

    public function __construct($projectRoot)
    {
        $this->projectRoot = rtrim($projectRoot, '/\\');
        $this->magentoRoot = $this->projectRoot;
    }

    public function getProjectRoot()
    {
        return $this->projectRoot;
    }

    public function setMagentoRoot($path)
    {
        $this->magentoRoot = rtrim($path, '/\\');
    }

    public function getMagentoRoot()
    {
        return $this->magentoRoot;
    }

    public function setMagentoAvailable($value)
    {
        $this->isMagentoAvailable = (bool)$value;
    }

    public function isMagentoAvailable()
    {
        return $this->isMagentoAvailable;
    }

    public function setMageBootstrapped($value)
    {
        $this->mageBootstrapped = (bool)$value;
    }

    public function isMageBootstrapped()
    {
        return $this->mageBootstrapped;
    }

    public function setMagentoVersion($value)
    {
        $this->magentoVersion = $value;
    }

    public function getMagentoVersion()
    {
        return $this->magentoVersion;
    }

    public function setMagentoEdition($value)
    {
        $this->magentoEdition = $value;
    }

    public function getMagentoEdition()
    {
        return $this->magentoEdition;
    }

    public function setMagentoBaseDir($value)
    {
        $this->magentoBaseDir = $value;
    }

    public function getMagentoBaseDir()
    {
        return $this->magentoBaseDir;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    public function all()
    {
        return $this->data;
    }
}