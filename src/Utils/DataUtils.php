<?php

namespace LANFest\WebDriverBinaryDownloader\Utils;

class DataUtils
{
    public function extractValue(array $data, $key, $default = null)
    {
        if (isset($data[$key])) {
            return $data[$key];
        }
        
        return $default;
    }
    
    public function assureArrayValue($value)
    {
        if (!is_array($value)) {
            return array_filter(
                array($value)
            );
        }
        
        return $value;
    }
}
