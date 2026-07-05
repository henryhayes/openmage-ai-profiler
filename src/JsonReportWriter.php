<?php

class JsonReportWriter
{
    public function write(Report $report, $file)
    {
        file_put_contents($file, json_encode($report->toArray(), JSON_PRETTY_PRINT));
    }
}
