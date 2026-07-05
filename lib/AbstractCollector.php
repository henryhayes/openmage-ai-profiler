<?php

abstract class AbstractCollector implements CollectorInterface
{
    public function getDescription()
    {
        return '';
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