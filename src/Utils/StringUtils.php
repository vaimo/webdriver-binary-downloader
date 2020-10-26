<?php

namespace Lanfest\WebDriverBinaryDownloader\Utils;

class StringUtils
{
    public function stringFromTemplate($template, array $values)
    {
        $variables = array_combine(
            array_map(function ($name) {
                return sprintf('{{%s}}', $name);
            }, array_keys($values)),
            $values
        );

        return str_replace(
            array_keys($variables),
            $variables,
            $template
        );
    }

    public function strTokOffset($value, $offset)
    {
        try {
            $cutOff = strpos($value, '.', $offset);
        } catch (\Exception $exception) {
            $cutOff = 0;
        }
        
        return substr($value, 0, $cutOff ?: strlen($value));
    }
}
