<?php

class AiPromptWriter
{
    public function write(AiContext $context, $file)
    {
        $out = array();

        $out[] = '============================================================';
        $out[] = 'OPENMAGE AI DEVELOPMENT PROMPT';
        $out[] = '============================================================';
        $out[] = '';
        $out[] = 'You are assisting with a Magento 1.x / OpenMage project.';
        $out[] = '';
        $out[] = 'Use this file as the initial development context for the project.';
        $out[] = 'If a full profiler report is also provided, treat that report as the';
        $out[] = 'authoritative technical inventory.';
        $out[] = '';
        $out[] = 'When helping with this project:';
        $out[] = '';
        $out[] = '- Assume Magento 1.x / OpenMage, not Magento 2.';
        $out[] = '- Assume PHP 7.4 compatibility unless the project profile says otherwise.';
        $out[] = '- Prefer backwards-compatible changes.';
        $out[] = '- Prefer modifying existing custom modules over creating new modules.';
        $out[] = '- Never suggest editing Magento core files directly.';
        $out[] = '- Always include exact file paths.';
        $out[] = '- Always provide complete code where practical.';
        $out[] = '- Explain any architectural decision briefly.';
        $out[] = '- Be careful with rewrites, observers, layout XML and theme fallback.';
        $out[] = '- Treat the installation as production unless told otherwise.';
        $out[] = '';
        $out[] = 'Recommended usage:';
        $out[] = '';
        $out[] = '1. Upload this file to ChatGPT or another AI assistant.';
        $out[] = '2. For detailed work, also upload one of:';
        $out[] = '   - ai-project-profile.txt';
        $out[] = '   - ai-project-profile.md';
        $out[] = '   - ai-project-profile.json';
        $out[] = '3. Then ask your development question.';
        $out[] = '';
        $out[] = 'Suggested first message:';
        $out[] = '';
        $out[] = '"Use the attached OpenMage AI Profiler context as the architectural';
        $out[] = 'reference for this project. When answering, include exact file paths';
        $out[] = 'and complete code changes."';
        $out[] = '';

        foreach ($context->getSections() as $sectionTitle => $items) {
            $out[] = '';
            $out[] = '------------------------------------------------------------';
            $out[] = strtoupper($sectionTitle);
            $out[] = '------------------------------------------------------------';
            $out[] = '';

            foreach ($items as $item) {
                $out[] = $item['key'] . ': ' . $item['value'];
            }
        }

        file_put_contents($file, implode("\n", $out));
    }
}