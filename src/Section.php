<?php

class Section
{
    protected $collectorName;
    protected $collectorCode;
    protected $collectorVersion;
    protected $collectorSince;
    protected $purpose = '';
    protected $source = '';
    protected $confidence = '';
    protected $duration = 0;
    protected $items = array();
    protected $errors = array();

    public function __construct()
    {
        
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
            'collector_name' => $this->collectorName,
            'collector_code' => $this->collectorCode,
            'collector_version' => $this->collectorVersion,
            'collector_since' => $this->collectorSince,
            'purpose' => $this->purpose,
            'source' => $this->source,
            'confidence' => $this->confidence,
            'duration' => $this->duration,
            'items' => $this->items,
            'errors' => $this->errors,
        );
    }
    
    public function setCollectorName($value)
    {
        $this->collectorName = $value;
    }

    public function setCollectorCode($value)
    {
        $this->collectorCode = $value;
    }

    public function setCollectorVersion($value)
    {
        $this->collectorVersion = $value;
    }

    public function setCollectorSince($value)
    {
        $this->collectorSince = $value;
    }
}