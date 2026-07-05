<?php

class MarkdownReportWriter
{
    public function write(Report $report, $file)
    {
        $data = $report->toArray();
        $out = array();

        $out[] = '# OpenMage AI Profiler Report';
        $out[] = '';

        foreach ($data['metadata'] as $key => $value) {
            $out[] = '- **' . $key . ':** ' . $value;
        }

        foreach ($data['sections'] as $section) {
            $out[] = '';
            $out[] = '## ' . $section['title'];
            $out[] = '';
            $out[] = '**Purpose:** ' . $section['purpose'];
            $out[] = '';
            $out[] = '**Source:** ' . $section['source'];
            $out[] = '';
            $out[] = '**Confidence:** ' . $section['confidence'];
            $out[] = '';
            $out[] = '**Duration:** ' . $section['duration'] . 's';
            $out[] = '';

            foreach ($section['items'] as $item) {
                $out[] = '- **' . $item['key'] . ':** ' . $item['value'];
            }

            foreach ($section['errors'] as $error) {
                $out[] = '- **ERROR:** ' . $error;
            }
        }

        file_put_contents($file, implode("\n", $out));
    }
}
