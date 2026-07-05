<?php

interface CollectorInterface
{
    public function getCode();

    public function getTitle();

    public function getDescription();

    public function getVersion();

    public function getSince();

    public function getDependencies();

    public function collect(Report $report, ProfilerContext $context);
}