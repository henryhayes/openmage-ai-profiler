<?php

abstract class AbstractCollector implements CollectorInterface
{
    public function getDescription()
    {
        return '';
    }

    public function getVersion()
    {
        return '1.0.0';
    }

    public function getSince()
    {
        return '0.1.0';
    }

    public function getDependencies()
    {
        return array();
    }

    protected function createSection(Report $report, $purpose, $source, $confidence)
    {
        $section = new Section();

        $section->setCollectorName($this->getTitle());
        $section->setCollectorCode($this->getCode());
        $section->setCollectorVersion($this->getVersion());
        $section->setCollectorSince($this->getSince());
        $section->setPurpose($purpose);
        $section->setSource($source);
        $section->setConfidence($confidence);

        $report->addSection($section);

        return $section;
    }
}