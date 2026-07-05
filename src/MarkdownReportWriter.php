<?php

class MarkdownReportWriter
{
    public function write(Report $report, $file)
    {
        $data = $report->toArray();
        $out = array();

        $out[] = '# OpenMage AI Profiler Report';
        $out[] = '';
        $out[] = 'Generated technical profile for Magento 1.x / OpenMage AI-assisted development.';
        $out[] = '';
        $out[] = '## Metadata';
        $out[] = '';
        $out[] = '| Key | Value |';
        $out[] = '| --- | --- |';

        foreach ($data['metadata'] as $key => $value) {
            $out[] = '| ' . $this->escapeTable($key) . ' | ' . $this->escapeTable($value) . ' |';
        }

        $out[] = '';
        $out[] = '## Table of Contents';
        $out[] = '';

        $currentCategory = null;

        foreach ($data['sections'] as $section) {
            $category = isset($section['collector_category']) ? $section['collector_category'] : 'General';
            $name = isset($section['collector_name']) && $section['collector_name'] !== '' ? $section['collector_name'] : 'Unknown Collector';

            if ($category !== $currentCategory) {
                $out[] = '- [' . $category . '](#' . $this->anchor($category) . ')';
                $currentCategory = $category;
            }

            $out[] = '  - [' . $name . '](#' . $this->anchor($category . '-' . $name) . ')';
        }

        $currentCategory = null;

        foreach ($data['sections'] as $section) {
            $category = isset($section['collector_category']) ? $section['collector_category'] : 'General';

            if ($category !== $currentCategory) {
                $out[] = '';
                $out[] = '---';
                $out[] = '';
                $out[] = '<a id="' . $this->anchor($category) . '"></a>';
                $out[] = '';
                $out[] = '## ' . $category;
                $currentCategory = $category;
            }

            $name = isset($section['collector_name']) && $section['collector_name'] !== '' ? $section['collector_name'] : 'Unknown Collector';

            $out[] = '';
            $out[] = '<a id="' . $this->anchor($category . '-' . $name) . '"></a>';
            $out[] = '';
            $out[] = '### ' . $name;
            $out[] = '';
            $out[] = '| Field | Value |';
            $out[] = '| --- | --- |';
            $out[] = '| Code | `' . $this->escapeTable($section['collector_code']) . '` |';
            $out[] = '| Version | `' . $this->escapeTable($section['collector_version']) . '` |';
            $out[] = '| Since | `' . $this->escapeTable($section['collector_since']) . '` |';
            $out[] = '| Purpose | ' . $this->escapeTable($section['purpose']) . ' |';
            $out[] = '| Source | ' . $this->escapeTable($section['source']) . ' |';
            $out[] = '| Confidence | ' . $this->escapeTable($section['confidence']) . ' |';
            $out[] = '| Duration | `' . $this->escapeTable($section['duration']) . 's` |';
            $out[] = '';
            $out[] = '#### Data';
            $out[] = '';

            foreach ($section['items'] as $item) {
                $key = isset($item['key']) ? trim($item['key']) : '';
                $value = isset($item['value']) ? $item['value'] : '';
                $out[] = '- **' . $this->escapeText($key) . ':** ' . $this->formatValue($value);
            }

            if (!empty($section['errors'])) {
                $out[] = '';
                $out[] = '#### Errors';
                $out[] = '';

                foreach ($section['errors'] as $error) {
                    $out[] = '- **ERROR:** ' . $this->escapeText($error);
                }
            }
        }

        file_put_contents($file, implode("\n", $out) . "\n");
    }

    protected function formatValue($value)
    {
        $value = (string)$value;

        if ($value === '') {
            return '`[empty]`';
        }

        if (strpos($value, "\n") !== false) {
            return "\n\n```text\n" . $value . "\n```";
        }

        return $this->escapeText($value);
    }

    protected function escapeTable($value)
    {
        $value = str_replace("\n", '<br>', (string)$value);
        $value = str_replace('|', '\\|', $value);
        return $this->escapeText($value);
    }

    protected function escapeText($value)
    {
        return str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), (string)$value);
    }

    protected function anchor($text)
    {
        $text = strtolower((string)$text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');

        return $text !== '' ? $text : 'section';
    }
}
