<?php

namespace LANFest\WebDriverBinaryDownloader\Analysers;

class EnvironmentAnalyser
{
    /**
     * @var \LANFest\WebDriverBinaryDownloader\Interfaces\ConfigInterface
     */
    private $pluginConfig;
    
    /**
     * @var \LANFest\WebDriverBinaryDownloader\Analysers\PlatformAnalyser
     */
    private $platformAnalyser;
    
    /**
     * @var \LANFest\WebDriverBinaryDownloader\Resolvers\VersionResolver
     */
    private $versionResolver;

    /**
     * @param \LANFest\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig
     * @param \Composer\IO\IOInterface $cliIO
     */
    public function __construct(
        \LANFest\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig,
        \Composer\IO\IOInterface $cliIO = null
    ) {
        $this->pluginConfig = $pluginConfig;
        
        $this->platformAnalyser = new \LANFest\WebDriverBinaryDownloader\Analysers\PlatformAnalyser();
        $this->versionResolver = new \LANFest\WebDriverBinaryDownloader\Resolvers\VersionResolver($cliIO);
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
