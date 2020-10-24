<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\WebDriverBinaryDownloader\Managers;

use Composer\Package\PackageInterface;
use Vaimo\WebDriverBinaryDownloader\Interfaces\ConfigInterface;

class DownloadManager
{
    /**
     * @var \Composer\Package\CompletePackage
     */
    private $ownerPackage;
    
    /**
     * @var \Composer\Downloader\DownloadManager
     */
    private $downloadManager;

    /**
     * @var \Composer\Installer\InstallationManager
     */
    private $installationManager;
    
    /**
     * @var \Composer\Cache
     */
    private $cacheManager;

    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Factories\DriverPackageFactory
     */
    private $driverPkgFactory;

    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Interfaces\ConfigInterface
     */
    private $pluginConfig;

    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Analysers\PlatformAnalyser
     */
    private $platformAnalyser;
    
    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Utils\SystemUtils
     */
    private $systemUtils;

    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Utils\DataUtils
     */
    private $dataUtils;

    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Utils\StringUtils
     */
    private $stringUtils;
    
    /**
     * @param \Composer\Package\CompletePackage $ownerPackage
     * @param \Composer\Downloader\DownloadManager $downloadManager
     * @param \Composer\Installer\InstallationManager $installationManager
     * @param \Composer\Cache $cacheManager
     * @param \Vaimo\WebDriverBinaryDownloader\Factories\DriverPackageFactory $driverPkgFactory
     * @param \Vaimo\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig
     */
    public function __construct(
        \Composer\Package\CompletePackage $ownerPackage,
        \Composer\Downloader\DownloadManager $downloadManager,
        \Composer\Installer\InstallationManager $installationManager,
        \Composer\Cache $cacheManager,
        \Vaimo\WebDriverBinaryDownloader\Factories\DriverPackageFactory $driverPkgFactory,
        \Vaimo\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig
    ) {
        $this->ownerPackage = $ownerPackage;
        $this->downloadManager = $downloadManager;
        $this->installationManager = $installationManager;
        $this->cacheManager = $cacheManager;
        $this->driverPkgFactory = $driverPkgFactory;
        $this->pluginConfig = $pluginConfig;
        
        $this->platformAnalyser = new \Vaimo\WebDriverBinaryDownloader\Analysers\PlatformAnalyser();
        $this->systemUtils = new \Vaimo\WebDriverBinaryDownloader\Utils\SystemUtils();
        $this->dataUtils = new \Vaimo\WebDriverBinaryDownloader\Utils\DataUtils();
        $this->stringUtils = new \Vaimo\WebDriverBinaryDownloader\Utils\StringUtils();
    }
    
    public function downloadRelease(array $versions)
    {
        $executableName = $this->dataUtils->extractValue(
            $this->pluginConfig->getExecutableFileNames(),
            $this->platformAnalyser->getPlatformCode(),
            ''
        );

        $ownerName = $this->ownerPackage->getName();
        
        if (!$executableName) {
            $platformName = $this->platformAnalyser->getPlatformName();

            throw new \Vaimo\WebDriverBinaryDownloader\Exceptions\PlatformNotSupportedException(
                sprintf('The package %s does not support platform: %s', $ownerName, $platformName)
            );
        }

        $name = sprintf('%s-virtual', $ownerName);
        
        $relativePath = $this->systemUtils->composePath(
            $this->ownerPackage->getName(),
            'downloads'
        );

        $fullPath = $this->systemUtils->composePath(
            $this->installationManager->getInstallPath($this->ownerPackage),
            'downloads'
        );

        while ($version = array_shift($versions)) {
            $package = $this->driverPkgFactory->create(
                $name,
                $this->getDownloadUrl($version),
                $version,
                $relativePath,
                array($executableName)
            );

            $downloader = $this->downloadManager->getDownloaderForPackage($package);

            if ($downloader === null) {
                continue;
            }

            /**
             * Some downloader types have the option to mute the output,
             * which is why there is the third call argument (not present
             * in interface footprint).
             */
            $downloader->download($package, $fullPath);
            
            return $package;
        }

        throw new \Exception('Failed to download requested driver');
    }

    private function getDownloadUrl($version)
    {
        $requestConfig = $this->pluginConfig->getRequestUrlConfig();

        $platformCode = $this->platformAnalyser->getPlatformCode();

        $remoteFiles = $this->pluginConfig->getRemoteFileNames();

        if (!isset($remoteFiles[$platformCode])) {
            throw new \Exception('Failed to resolve a file for the platform. Download driver manually');
        }

        $driverHashes = $this->pluginConfig->getDriverVersionHashMap();

        $fileHash = $this->dataUtils->extractValue($driverHashes, $version, '');

        $variables = array(
            'version' => $version,
            'hash' => $fileHash,
            'major' => $this->stringUtils->strTokOffset($version, 1),
            'major-minor' => $this->stringUtils->strTokOffset($version, 2)
        );

        $fileName = $this->stringUtils->stringFromTemplate(
            $remoteFiles[$platformCode],
            $variables
        );

        return $this->stringUtils->stringFromTemplate(
            $requestConfig[ConfigInterface::REQUEST_DOWNLOAD],
            array_replace($variables, array('file' => $fileName))
        );
    }
}
