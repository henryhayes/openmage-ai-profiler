<?php

interface CollectorInterface
{
    public function getCode();

    public function getTitle();

    public function getDescription();

    public function collect(Report $report);
}