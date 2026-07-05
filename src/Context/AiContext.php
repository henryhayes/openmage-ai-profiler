<?php

class AiContext
{
    protected $sections = array();

    public function addSection($title)
    {
        if (!isset($this->sections[$title])) {
            $this->sections[$title] = array();
        }
    }

    public function addItem($section, $key, $value)
    {
        $this->addSection($section);

        $this->sections[$section][] = array(
            'key' => $key,
            'value' => $value,
        );
    }

    public function getSections()
    {
        return $this->sections;
    }
}