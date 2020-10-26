<?php

namespace Lanfest\WebDriverBinaryDownloader\Interfaces;

use Lanfest\WebDriverBinaryDownloader\Interfaces\ConfigInterface as Config;

interface InstallerInterface
{
    /**
     * @param Config $pluginConfig
     */
    public function executeWithConfig(Config $pluginConfig);
}
