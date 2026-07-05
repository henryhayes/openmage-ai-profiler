<?php

class CommandLine
{
    protected $options = array();

    public function __construct(array $argv)
    {
        foreach ($argv as $argument) {

            if (substr($argument, 0, 2) !== '--') {
                continue;
            }

            $argument = substr($argument, 2);

            if (strpos($argument, '=') !== false) {
                list($key, $value) = explode('=', $argument, 2);
            } else {
                $key = $argument;
                $value = true;
            }

            $this->options[$key] = $value;
        }
    }

    public function has($key)
    {
        return isset($this->options[$key]);
    }

    public function get($key, $default = null)
    {
        return isset($this->options[$key])
            ? $this->options[$key]
            : $default;
    }

    public function all()
    {
        return $this->options;
    }
}