<?php

class Section
{
    protected $title;
    protected $purpose = '';
    protected $source = '';
    protected $confidence = '';
    protected $duration = 0;
    protected $items = array();
    protected $errors = array();

    public function __construct($title)
    {
        $this->title = $title;
    }

    public function setPurpose($value) { $this->purpose = $value; }
    public function setSource($value) { $this->source = $value; }
    public function setConfidence($value) { $this->confidence = $value; }
    public function setDuration($value) { $this->duration = $value; }

    public function addItem($key, $value)
    {
        $this->items[] = array('key' => $key, 'value' => $value);
    }

    public function addError($message)
    {
        $this->errors[] = $message;
    }

    public function toArray()
    {
        return array(
            'title' => $this->title,
            'purpose' => $this->purpose,
            'source' => $this->source,
            'confidence' => $this->confidence,
            'duration' => $this->duration,
            'items' => $this->items,
            'errors' => $this->errors,
        );
    }
}