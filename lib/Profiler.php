<?php

class Profiler
{
    protected $registry;
    protected $report;

    public function __construct(CollectorRegistry $registry, Report $report)
    {
        $this->registry = $registry;
        $this->report = $report;
    }

    public function run()
    {
        foreach ($this->registry->getCollectors() as $collector) {
            $start = microtime(true);

            try {
                $before = count($this->report->getSections());
                $collector->collect($this->report);
                $after = count($this->report->getSections());

                $sections = $this->report->getSections();

                if ($after > $before) {
                    $sections[$after - 1]->setDuration(round(microtime(true) - $start, 4));
                }
            } catch (Exception $e) {
                $section = new Section($collector->getTitle());
                $section->setPurpose($collector->getDescription());
                $section->setSource('Collector exception');
                $section->setConfidence('0%');
                $section->setDuration(round(microtime(true) - $start, 4));
                $section->addError($e->getMessage());
                $this->report->addSection($section);
            }
        }

        return $this->report;
    }
}
