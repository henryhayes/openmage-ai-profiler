<?php

class ReportManager
{
    protected $outputDir;

    protected $projectRoot;

    public function __construct($outputDir, $projectRoot = null)
    {
        $this->outputDir = rtrim($outputDir, '/\\');
        $this->projectRoot = $projectRoot !== null ? rtrim($projectRoot, '/\\') : null;
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

        if (!empty($options['markdown']) || !empty($options['codex'])) {
            $written[] = $this->writeMarkdownProfile($report);
        }

        if (!empty($options['codex'])) {
            $written[] = $this->writeCodexAgents($aiContext);
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

    protected function writeCodexAgents(AiContext $context)
    {
        if ($this->projectRoot === null || $this->projectRoot === '') {
            $file = $this->path('AGENTS.md');
            $aiDirectory = '.';
            $localInstructionsFile = null;
        } else {
            $file = $this->projectRoot . DIRECTORY_SEPARATOR . 'AGENTS.md';
            $aiDirectory = $this->relativePath(
                $this->projectRoot,
                $this->outputDir
            );

            $localInstructionsFile = $this->projectRoot
                . DIRECTORY_SEPARATOR
                . 'AGENTS.local.md';
        }

        $writer = new CodexAgentsWriter();
        $writer->write(
            $context,
            $file,
            $aiDirectory,
            $localInstructionsFile
        );

        return $file;
    }

    protected function relativePath($from, $to)
    {
        $from = str_replace('\\', '/', rtrim($from, '/\\'));
        $to = str_replace('\\', '/', rtrim($to, '/\\'));

        if ($from === $to) {
            return '.';
        }

        if (strpos($to . '/', $from . '/') === 0) {
            return ltrim(substr($to, strlen($from)), '/');
        }

        return $to;
    }

    protected function path($filename)
    {
        return $this->outputDir . DIRECTORY_SEPARATOR . $filename;
    }
}