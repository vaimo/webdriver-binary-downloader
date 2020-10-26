<?php

namespace Lanfest\WebDriverBinaryDownloader\Utils;

class SystemUtils
{
    public function recursiveGlob($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge(
                $files,
                $this->recursiveGlob(
                    $this->composePath($dir, basename($pattern)),
                    $flags
                )
            );
        }

        return $files;
    }

    public function composePath()
    {
        $pathSegments = array_map(function ($item) {
            return rtrim($item, DIRECTORY_SEPARATOR);
        }, func_get_args());

        return implode(
            DIRECTORY_SEPARATOR,
            array_filter($pathSegments)
        );
    }
}
