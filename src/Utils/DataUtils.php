<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\WebDriverBinaryDownloader\Utils;

class DataUtils
{
    public function extractValue(array $data, $key, $default = null)
    {
        if (isset($data)) {
            return $data[$key];
        }
        
        return $default;
    }
}
