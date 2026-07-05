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

    protected function createSection(Report $report, $title, $purpose, $source, $confidence)
    {
        $section = new Section($title);
        $section->setPurpose($purpose);
        $section->setSource($source);
        $section->setConfidence($confidence);

        $report->addSection($section);

        return $section;
    }
}