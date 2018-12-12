<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\WebDriverBinaryDownloader\Installer;

class Utils
{
    public function stringFromTemplate($template, array $values)
    {
        $variables = array_combine(
            array_map(function ($name) {
                return sprintf('{{%s}}', $name);
            }, array_keys($values)),
            $values
        );

        return str_replace(array_keys($variables), $variables, $template);
    }
    
    public function getHeaders($url)
    {
        $headers = get_headers($url);

        return array_combine(
            array_map(function ($value) {
                return strtok($value, ':');
            }, $headers),
            array_map(function ($value) {
                return trim(substr($value, strpos($value, ':')), ': ');
            }, $headers)
        );
    }

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
        $segments = func_get_args();
        
        return implode(DIRECTORY_SEPARATOR, $segments);
    }
}
