<?php

class Profiler
{
    protected $registry;
    protected $report;
    protected $context;

    public function __construct(CollectorRegistry $registry, Report $report, Context $context)
    {
        $this->registry = $registry;
        $this->report = $report;
        $this->context = $context;
    }

    public function run()
    {
        foreach ($this->registry->getCollectors() as $collector) {
            $start = microtime(true);

            echo 'Running collector: ' . $collector->getTitle() . PHP_EOL;

            try {
                $before = count($this->report->getSections());

                $collector->collect($this->report, $this->context);

                $after = count($this->report->getSections());
                $sections = $this->report->getSections();

                if ($after > $before) {
                    $sections[$after - 1]->setDuration(round(microtime(true) - $start, 4));
                }

                echo 'Completed collector: ' . $collector->getTitle() . PHP_EOL;
            } catch (Exception $e) {
                $section = new Section($collector->getTitle());
                $section->setPurpose($collector->getDescription());
                $section->setSource('Collector exception');
                $section->setConfidence('Low');
                $section->setDuration(round(microtime(true) - $start, 4));
                $section->addError($e->getMessage());

                $this->report->addSection($section);

                echo 'Failed collector: ' . $collector->getTitle() . PHP_EOL;
            }
        }

        return $this->report;
    }
}