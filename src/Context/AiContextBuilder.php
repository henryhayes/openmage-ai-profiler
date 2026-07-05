<?php

class AiContextBuilder
{
    protected $extractors = array();

    public function __construct(array $extractors = null)
    {
        if ($extractors === null) {
            $extractors = $this->createDefaultExtractors();
        }

        $this->extractors = $extractors;
    }

    public function build(Report $report)
    {
        $context = new AiContext();
        $data = $report->toArray();

        foreach ($this->extractors as $extractor) {
            if (!$extractor instanceof AiContextExtractorInterface) {
                throw new Exception('Invalid AI context extractor: ' . get_class($extractor));
            }

            $extractor->extract($context, $data);
        }

        return $context;
    }

    protected function createDefaultExtractors()
    {
        return array(
            new CoreContextExtractor(),
            new ModuleContextExtractor(),
            new ThemeContextExtractor(),
            new RewriteContextExtractor(),
            new ObserverContextExtractor(),
            new CronContextExtractor(),
            new OperationsContextExtractor(),
            new DatabaseContextExtractor(),
            new EavContextExtractor(),
            new LayoutContextExtractor(),
            new RouterContextExtractor(),
            new ControllerContextExtractor(),
            new GuidanceContextExtractor(),
        );
    }
}
