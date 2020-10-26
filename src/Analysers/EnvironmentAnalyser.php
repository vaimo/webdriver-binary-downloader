<?php

namespace Lanfest\WebDriverBinaryDownloader\Analysers;

class EnvironmentAnalyser
{
    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Interfaces\ConfigInterface
     */
    private $pluginConfig;
    
    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Analysers\PlatformAnalyser
     */
    private $platformAnalyser;
    
    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Resolvers\VersionResolver
     */
    private $versionResolver;

    /**
     * @param \Lanfest\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig
     * @param \Composer\IO\IOInterface $cliIO
     */
    public function __construct(
        \Lanfest\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig,
        \Composer\IO\IOInterface $cliIO = null
    ) {
        $this->pluginConfig = $pluginConfig;
        
        $this->platformAnalyser = new \Lanfest\WebDriverBinaryDownloader\Analysers\PlatformAnalyser();
        $this->versionResolver = new \Lanfest\WebDriverBinaryDownloader\Resolvers\VersionResolver($cliIO);
    }

    public function resolveBrowserVersion()
    {
        $platformCode = $this->platformAnalyser->getPlatformCode();
        $binaryPaths = $this->pluginConfig->getBrowserBinaryPaths();

        if (!isset($binaryPaths[$platformCode])) {
            return '';
        }

        return $this->versionResolver->pollForExecutableVersion(
            $binaryPaths[$platformCode],
            $this->pluginConfig->getBrowserVersionPollingConfig()
        );
    }
}
