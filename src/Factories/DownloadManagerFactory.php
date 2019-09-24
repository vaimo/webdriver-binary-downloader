<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\WebDriverBinaryDownloader\Factories;

use Vaimo\WebDriverBinaryDownloader\Interfaces\ConfigInterface;

class DownloadManagerFactory
{
    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Composer\Context
     */
    private $composerContext;

    /**
     * @var \Composer\IO\IOInterface
     */
    private $cliIO;

    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Utils\SystemUtils
     */
    private $systemUtils;

    /**
     * @param \Vaimo\WebDriverBinaryDownloader\Composer\Context $composerContext
     */
    public function __construct(
        \Vaimo\WebDriverBinaryDownloader\Composer\Context $composerContext,
        \Composer\IO\IOInterface $cliIO
    ) {
        $this->composerContext = $composerContext;
        $this->cliIO = $cliIO;
        
        $this->systemUtils = new \Vaimo\WebDriverBinaryDownloader\Utils\SystemUtils();
    }

    public function create(ConfigInterface $pluginConfig)
    {
        $composer = $this->composerContext->getLocalComposer();
        $packages = $this->composerContext->getActivePackages();
        
        $packageResolver = new \Vaimo\WebDriverBinaryDownloader\Resolvers\PackageResolver();
        
        $pluginPackage = $packageResolver->resolvePackageForNamespace(
            $packages,
            get_class($pluginConfig)
        );

        return new \Vaimo\WebDriverBinaryDownloader\Managers\DownloadManager(
            $pluginPackage,
            $composer->getDownloadManager(),
            $composer->getInstallationManager(),
            $this->createCacheManager($composer, $pluginPackage->getName()),
            new \Vaimo\WebDriverBinaryDownloader\Factories\DriverPackageFactory(),
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
