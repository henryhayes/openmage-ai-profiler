<?php

class TxtReportWriter
{
    public function write(Report $report, $file)
    {
        $data = $report->toArray();
        $out = array();

        $out[] = '============================================================';
        $out[] = 'OPENMAGE AI PROFILER';
        $out[] = '============================================================';

        foreach ($data['metadata'] as $key => $value) {
            $out[] = $key . ': ' . $value;
        }

        foreach ($data['sections'] as $section) {
            $out[] = '';
            $out[] = '';
            $out[] = '============================================================';
            $out[] = strtoupper($section['title']);
            $out[] = '============================================================';
            $out[] = 'Purpose: ' . $section['purpose'];
            $out[] = 'Source: ' . $section['source'];
            $out[] = 'Confidence: ' . $section['confidence'];
            $out[] = 'Duration: ' . $section['duration'] . 's';
            $out[] = '';

            foreach ($section['items'] as $item) {
                $out[] = $item['key'] . ': ' . $item['value'];
            }

            foreach ($section['errors'] as $error) {
                $out[] = 'ERROR: ' . $error;
            }
        }

        file_put_contents($file, implode("\n", $out));
    }
}

