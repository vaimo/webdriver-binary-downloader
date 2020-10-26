<?php

namespace LANFest\WebDriverBinaryDownloader\Factories;

use LANFest\WebDriverBinaryDownloader\Interfaces\ConfigInterface;

class DownloadManagerFactory
{
    /**
     * @var \LANFest\WebDriverBinaryDownloader\Composer\Context
     */
    private $composerContext;

    /**
     * @var \Composer\IO\IOInterface
     */
    private $cliIO;

    /**
     * @var \LANFest\WebDriverBinaryDownloader\Utils\SystemUtils
     */
    private $systemUtils;

    /**
     * @param \LANFest\WebDriverBinaryDownloader\Composer\Context $composerContext
     */
    public function __construct(
        \LANFest\WebDriverBinaryDownloader\Composer\Context $composerContext,
        \Composer\IO\IOInterface $cliIO
    ) {
        $this->composerContext = $composerContext;
        $this->cliIO = $cliIO;
        
        $this->systemUtils = new \LANFest\WebDriverBinaryDownloader\Utils\SystemUtils();
    }

    public function create(ConfigInterface $pluginConfig)
    {
        $composer = $this->composerContext->getLocalComposer();
        $packages = $this->composerContext->getActivePackages();
        
        $packageResolver = new \LANFest\WebDriverBinaryDownloader\Resolvers\PackageResolver(
            array($composer->getPackage())
        );
        
        $pluginPackage = $packageResolver->resolveForNamespace(
            $packages,
            get_class($pluginConfig)
        );

        return new \LANFest\WebDriverBinaryDownloader\Managers\DownloadManager(
            $pluginPackage,
            $composer->getDownloadManager(),
            $composer->getInstallationManager(),
            $this->createCacheManager($composer, $pluginPackage->getName()),
            new \LANFest\WebDriverBinaryDownloader\Factories\DriverPackageFactory(),
            $pluginConfig
        );
    }

    private function createCacheManager(\Composer\Composer $composer, $cacheName)
    {
        $composerConfig = $composer->getConfig();

        $cacheDir = $composerConfig->get('cache-dir');

        return new \Composer\Cache(
            $this->cliIO,
            $this->systemUtils->composePath($cacheDir, 'files', $cacheName, 'downloads')
        );
    }
}
