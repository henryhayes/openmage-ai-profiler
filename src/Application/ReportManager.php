<?php

class ReportManager
{
    protected $outputDir;

    public function __construct($outputDir)
    {
        $this->outputDir = rtrim($outputDir, '/\\');
    }

    public function ensureOutputDirectory()
    {
        if (is_dir($this->outputDir)) {
            return true;
        }

        return mkdir($this->outputDir, 0755, true);
    }

    public function getOutputDir()
    {
        return $this->outputDir;
    }

    public function writeAll(Report $report, array $options = array())
    {
        $written = array();

        $written[] = $this->writeTextProfile($report);
        $written[] = $this->writeJsonProfile($report);

        $aiContextBuilder = new AiContextBuilder();
        $aiContext = $aiContextBuilder->build($report);

        $written[] = $this->writeAiContext($aiContext);
        $written[] = $this->writeAiPrompt($aiContext);

        if (!empty($options['markdown'])) {
            $written[] = $this->writeMarkdownProfile($report);
        }

        return $written;
    }

    protected function writeTextProfile(Report $report)
    {
        $file = $this->path('ai-project-profile.txt');

        $writer = new TxtReportWriter();
        $writer->write($report, $file);

        return $file;
    }

    protected function writeJsonProfile(Report $report)
    {
        $file = $this->path('ai-project-profile.json');

        $writer = new JsonReportWriter();
        $writer->write($report, $file);

        return $file;
    }

    protected function writeMarkdownProfile(Report $report)
    {
        $file = $this->path('ai-project-profile.md');

        $writer = new MarkdownReportWriter();
        $writer->write($report, $file);

        return $file;
    }

    protected function writeAiContext(AiContext $context)
    {
        $file = $this->path('ai-project-context.txt');

        $writer = new AiContextWriter();
        $writer->write($context, $file);

        return $file;
    }

    protected function writeAiPrompt(AiContext $context)
    {
        $file = $this->path('ai-chatgpt-prompt.txt');

        $writer = new AiPromptWriter();
        $writer->write($context, $file);

        return $file;
    }

    protected function path($filename)
    {
        return $this->outputDir . DIRECTORY_SEPARATOR . $filename;
    }
}