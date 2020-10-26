<?php

namespace Lanfest\WebDriverBinaryDownloader\Factories;

use Lanfest\WebDriverBinaryDownloader\Interfaces\ConfigInterface;

class DownloadManagerFactory
{
    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Composer\Context
     */
    private $composerContext;

    /**
     * @var \Composer\IO\IOInterface
     */
    private $cliIO;

    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Utils\SystemUtils
     */
    private $systemUtils;

    /**
     * @param \Lanfest\WebDriverBinaryDownloader\Composer\Context $composerContext
     */
    public function __construct(
        \Lanfest\WebDriverBinaryDownloader\Composer\Context $composerContext,
        \Composer\IO\IOInterface $cliIO
    ) {
        $this->composerContext = $composerContext;
        $this->cliIO = $cliIO;
        
        $this->systemUtils = new \Lanfest\WebDriverBinaryDownloader\Utils\SystemUtils();
    }

    public function create(ConfigInterface $pluginConfig)
    {
        $composer = $this->composerContext->getLocalComposer();
        $packages = $this->composerContext->getActivePackages();
        
        $packageResolver = new \Lanfest\WebDriverBinaryDownloader\Resolvers\PackageResolver(
            array($composer->getPackage())
        );
        
        $pluginPackage = $packageResolver->resolveForNamespace(
            $packages,
            get_class($pluginConfig)
        );

        return new \Lanfest\WebDriverBinaryDownloader\Managers\DownloadManager(
            $pluginPackage,
            $composer->getDownloadManager(),
            $composer->getInstallationManager(),
            $this->createCacheManager($composer, $pluginPackage->getName()),
            new \Lanfest\WebDriverBinaryDownloader\Factories\DriverPackageFactory(),
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
