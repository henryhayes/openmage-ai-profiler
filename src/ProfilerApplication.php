<?php

class ProfilerApplication
{
    protected $registry;
    protected $report;

    /**
     * @var ProfilerContext
     */
    protected $context;

    public function __construct(CollectorRegistry $registry, Report $report, ProfilerContext $context)
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

                if ($after > $before) {
                    $section = $this->report->getLastSection();

                    if ($section instanceof Section) {
                        $section->setDuration(round(microtime(true) - $start, 4));
                    }
                }

                echo 'Completed collector: ' . $collector->getTitle() . PHP_EOL;
            } catch (Exception $e) {
                $section = $this->createFailedCollectorSection(
                    $collector,
                    $e,
                    round(microtime(true) - $start, 4)
                );

                $this->report->addSection($section);

                echo 'Failed collector: ' . $collector->getTitle() . PHP_EOL;
            }
        }

        return $this->report;
    }

    protected function createFailedCollectorSection(CollectorInterface $collector, Exception $exception, $duration)
    {
        $section = new Section();

        $section->setCollectorCategory($collector->getCategory());
        $section->setCollectorName($collector->getTitle());
        $section->setCollectorCode($collector->getCode());
        $section->setCollectorVersion($collector->getVersion());
        $section->setCollectorSince($collector->getSince());

        $section->setPurpose($collector->getDescription());
        $section->setSource('Collector exception');
        $section->setConfidence('Low');
        $section->setDuration($duration);

        $section->addError($exception->getMessage());

        return $section;
    }
}