<?php

class CollectorRegistry
{
    protected $collectors = array();

    public function register(CollectorInterface $collector)
    {
        $this->collectors[$collector->getCode()] = $collector;
    }

    public function getCollectors()
    {
        return $this->collectors;
    }
}