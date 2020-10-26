<?php

namespace LANFest\WebDriverBinaryDownloader\Interfaces;

use LANFest\WebDriverBinaryDownloader\Interfaces\ConfigInterface as Config;

interface InstallerInterface
{
    /**
     * @param Config $pluginConfig
     */
    public function executeWithConfig(Config $pluginConfig);
}
