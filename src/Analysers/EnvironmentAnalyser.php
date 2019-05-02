<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\WebDriverBinaryDownloader\Analysers;

class EnvironmentAnalyser
{
    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Interfaces\ConfigInterface
     */
    private $pluginConfig;
    
    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Analysers\PlatformAnalyser
     */
    private $platformAnalyser;
    
    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Resolvers\VersionResolver
     */
    private $versionResolver;

    /**
     * @param \Vaimo\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig
     */
    public function __construct(
        \Vaimo\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig
    ) {
        $this->pluginConfig = $pluginConfig;
        
        $this->platformAnalyser = new \Vaimo\WebDriverBinaryDownloader\Analysers\PlatformAnalyser();
        $this->versionResolver = new \Vaimo\WebDriverBinaryDownloader\Resolvers\VersionResolver();
    }

    public function resolveBrowserVersion()
    {
        $platformCode = $this->platformAnalyser->getPlatformCode();
        $binaryPaths = $this->pluginConfig->getBrowserBinaryPaths();

        if (!isset($binaryPaths[$platformCode])) {
            return '';
        }

        return $this->versionResolver->pollForVersion(
            $binaryPaths[$platformCode],
            $this->pluginConfig->getBrowserVersionPollingConfig()
        );
    }
}
