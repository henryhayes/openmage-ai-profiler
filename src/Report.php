<?php

class Report
{
    protected $metadata = array();
    protected $sections = array();

    public function setMetadata($key, $value)
    {
        $this->metadata[$key] = $value;
    }

    public function addSection(Section $section)
    {
        $this->sections[] = $section;
    }

    public function getSections()
    {
        return $this->sections;
    }

    public function toArray()
    {
        $sections = array();

        foreach ($this->sections as $section) {
            $sections[] = $section->toArray();
        }

        return array(
            'metadata' => $this->metadata,
            'sections' => $sections,
        );
    }
}