<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\WebDriverBinaryDownloader\Managers;

use Composer\Package\PackageInterface;

class DownloadManager
{
    /**
     * @var \Composer\Downloader\DownloadManager
     */
    private $downloadManager;
    
    /**
     * @var \Composer\Cache
     */
    private $cacheManager;

    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Factories\DriverPackageFactory
     */
    private $driverPkgFactory;

    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Utils\SystemUtils
     */
    private $systemUtils;
    
    /**
     * @param \Composer\Downloader\DownloadManager $downloadManager
     * @param \Composer\Cache $cacheManager
     * @param \Vaimo\WebDriverBinaryDownloader\Factories\DriverPackageFactory $driverPkgFactory
     */
    public function __construct(
        \Composer\Downloader\DownloadManager $downloadManager,
        \Composer\Cache $cacheManager,
        \Vaimo\WebDriverBinaryDownloader\Factories\DriverPackageFactory $driverPkgFactory
    ) {
        $this->downloadManager = $downloadManager;
        $this->cacheManager = $cacheManager;
        $this->driverPkgFactory = $driverPkgFactory;
        
        $this->systemUtils = new \Vaimo\WebDriverBinaryDownloader\Utils\SystemUtils();
    }
    
    public function downloadRelease(array $versions, $retryCount = 0)
    {
        $targetDir = $this->systemUtils->composePath(
            $this->cacheManager->getRoot(),
            reset($versions)
        );
        
        while ($version = array_shift($versions)) {
            $package = $this->driverPkgFactory->create($version, $targetDir);
            
            try {
                return $this->downloadPackage($package, $targetDir, $retryCount);
            } catch (\Composer\Downloader\TransportException $exception) {
                if ($exception->getStatusCode() === 404 && $versions) {
                    continue;
                }

                $errorMessage = sprintf(
                    'Transport failure %s while downloading v%s: %s',
                    $exception->getStatusCode(),
                    $version,
                    $exception->getMessage()
                );
                
                throw new \Exception($errorMessage);
            } catch (\Exception $exception) {
                $errorMessage = sprintf(
                    'Unexpected error while downloading v%s: %s',
                    $version,
                    $exception->getMessage()
                );

                throw new \Exception($errorMessage);
            }
        }

        throw new \Exception('Failed to download requested driver');
    }
    
    private function downloadPackage(PackageInterface $package, $targetDir, $retryCount = 0)
    {
        do {
            try {
                /** @var \Composer\Downloader\DownloaderInterface $downloader */
                $downloader = $this->downloadManager->getDownloaderForInstalledPackage($package);

                /**
                 * Some downloader types have the option to mute the output,
                 * which is why there is the third call argument (not present
                 * in interface footprint).
                 */
                $downloader->download($package, $targetDir, false);

                break;
            } catch (\Exception $exception) {
                if (!$retryCount) {
                    throw $exception;
                }
            }
        } while ($retryCount-- > 0);
        
        return $package;
    }
}
