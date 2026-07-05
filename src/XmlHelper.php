<?php

class XmlHelper
{
    public function fileExists($file)
    {
        return is_file($file);
    }

    public function loadFile($file)
    {
        if (!is_file($file)) {
            return null;
        }

        libxml_use_internal_errors(true);

        $xml = simplexml_load_file($file);

        libxml_clear_errors();

        if ($xml === false) {
            return null;
        }

        return $xml;
    }

    public function countChildren($node)
    {
        if (!$node) {
            return 0;
        }

        return count($node->children());
    }

    public function nodeExists($node, $path)
    {
        if (!$node) {
            return false;
        }

        $result = $node->xpath($path);

        return is_array($result) && count($result) > 0;
    }

    public function countXpath($node, $path)
    {
        if (!$node) {
            return 0;
        }

        $result = $node->xpath($path);

        return is_array($result) ? count($result) : 0;
    }

    public function xpath($node, $path)
    {
        if (!$node) {
            return array();
        }

        $result = $node->xpath($path);

        return is_array($result) ? $result : array();
    }

    public function stringValue($node, $path, $default = '')
    {
        if (!$node) {
            return $default;
        }

        $result = $node->xpath($path);

        if (!is_array($result) || count($result) === 0) {
            return $default;
        }

        return trim((string)$result[0]);
    }
}