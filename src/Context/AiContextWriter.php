<?php

class AiContextWriter
{
    public function write(AiContext $context, $file)
    {
        $out = array();

        $out[] = '============================================================';
        $out[] = 'OPENMAGE AI CONTEXT';
        $out[] = '============================================================';
        $out[] = '';
        $out[] = 'This file is a distilled AI-readable summary generated from';
        $out[] = 'the full OpenMage AI Profiler report.';
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