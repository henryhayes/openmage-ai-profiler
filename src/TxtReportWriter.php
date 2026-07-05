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

        $currentCategory = null;

        foreach ($data['sections'] as $section) {
            $category = isset($section['collector_category'])
                ? $section['collector_category']
                : 'General';

            if ($category !== $currentCategory) {
                $out[] = '';
                $out[] = '';
                $out[] = '############################################################';
                $out[] = strtoupper($category);
                $out[] = '############################################################';

                $currentCategory = $category;
            }

            $out[] = '';
            $out[] = '';
            $out[] = '============================================================';
            $out[] = 'COLLECTOR';
            $out[] = '============================================================';
            $out[] = '';

            $out[] = 'Category:';
            $out[] = $category;
            $out[] = '';

            $out[] = 'Name:';
            $out[] = $section['collector_name'];
            $out[] = '';

            $out[] = 'Code:';
            $out[] = $section['collector_code'];
            $out[] = '';

            $out[] = 'Version:';
            $out[] = $section['collector_version'];
            $out[] = '';

            $out[] = 'Since:';
            $out[] = $section['collector_since'];
            $out[] = '';

            $out[] = 'Purpose:';
            $out[] = $section['purpose'];
            $out[] = '';

            $out[] = 'Source:';
            $out[] = $section['source'];
            $out[] = '';

            $out[] = 'Confidence:';
            $out[] = $section['confidence'];
            $out[] = '';

            $out[] = 'Duration:';
            $out[] = $section['duration'] . ' seconds';
            $out[] = '';

            $out[] = '============================================================';
            $out[] = 'DATA';
            $out[] = '============================================================';
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