<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\WebDriverBinaryDownloader\Interfaces;

use Vaimo\WebDriverBinaryDownloader\Interfaces\ConfigInterface as Config;

interface InstallerInterface
{
    /**
     * @param Config $pluginConfig
     */
    public function executeWithConfig(Config $pluginConfig);
}
