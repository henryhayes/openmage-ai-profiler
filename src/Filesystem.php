<?php

class Filesystem
{
    public function directoryExists($path)
    {
        return is_dir($path);
    }

    public function fileExists($path)
    {
        return is_file($path);
    }

    public function countFiles($path, array $extensions = array())
    {
        if (!is_dir($path)) {
            return 0;
        }

        $count = 0;
        $extensions = $this->normaliseExtensions($extensions);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (!$this->extensionMatches($file->getFilename(), $extensions)) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    public function directorySize($path)
    {
        if (!is_dir($path)) {
            return 0;
        }

        $size = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    public function formatBytes($bytes)
    {
        $bytes = (float)$bytes;

        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return (int)$bytes . ' bytes';
    }

    public function listDirectories($path)
    {
        $directories = array();

        if (!is_dir($path)) {
            return $directories;
        }

        $items = scandir($path);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                $directories[] = $item;
            }
        }

        sort($directories);

        return $directories;
    }

    protected function normaliseExtensions(array $extensions)
    {
        $result = array();

        foreach ($extensions as $extension) {
            $extension = strtolower(ltrim($extension, '.'));
            if ($extension !== '') {
                $result[] = $extension;
            }
        }

        return $result;
    }

    protected function extensionMatches($filename, array $extensions)
    {
        if (count($extensions) === 0) {
            return true;
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($extension, $extensions, true);
    }
}