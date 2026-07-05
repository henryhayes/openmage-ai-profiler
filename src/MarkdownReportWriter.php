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

        $currentCategory = null;

        foreach ($data['sections'] as $section) {
            $category = isset($section['collector_category']) ? $section['collector_category'] : 'General';

            if ($category !== $currentCategory) {
                $out[] = '';
                $out[] = '---';
                $out[] = '';
                $out[] = '# ' . $category;
                $currentCategory = $category;
            }

            $name = isset($section['collector_name']) && $section['collector_name'] !== ''
                ? $section['collector_name']
                : 'Unknown Collector';

            $out[] = '';
            $out[] = '## ' . $name;
            $out[] = '';

            $out[] = '**Code:** ' . $section['collector_code'];
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
                $out[] = '- **' . trim($item['key']) . ':** ' . $item['value'];
            }

            foreach ($section['errors'] as $error) {
                $out[] = '- **ERROR:** ' . $error;
            }
        }

        file_put_contents($file, implode("\n", $out));
    }
}