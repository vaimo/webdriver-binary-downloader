<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\WebDriverBinaryDownloader;

use Vaimo\WebDriverBinaryDownloader\Interfaces\ConfigInterface;

class Installer implements \Vaimo\WebDriverBinaryDownloader\Interfaces\InstallerInterface
{
    /**
     * @var \Composer\Composer
     */
    private $composerRuntime;

    /**
     * @var \Composer\IO\IOInterface
     */
    private $io;
    
    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Installer\Utils 
     */
    private $utils;
    
    /**
     * @param \Composer\Composer $composerRuntime
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(
        \Composer\Composer $composerRuntime,
        \Composer\IO\IOInterface $io
    ) {
        $this->composerRuntime = $composerRuntime;
        $this->io = $io;
        
        $this->utils = new \Vaimo\WebDriverBinaryDownloader\Installer\Utils();
    }
    
    public function executeWithConfig(ConfigInterface $pluginConfig)
    {
        $composerConfig = $this->composerRuntime->getConfig();
        
        $binaryDir = $composerConfig->get('bin-dir');
        
        $projectAnalyser = new \Vaimo\WebDriverBinaryDownloader\Analysers\ProjectAnalyser($pluginConfig);
        $packageManager = new \Vaimo\WebDriverBinaryDownloader\Installer\PackageManager($pluginConfig);

        $driverName = $pluginConfig->getDriverName();
        
        if (!$projectAnalyser->resolvePlatformSupport()) {
            if ($this->io->isVerbose()) {
                $this->io->write(
                    sprintf('SKIPPING %s setup: platform not supported', $driverName)
                );
            }
            
            return;
        }
        
        $version = $projectAnalyser->resolveRequiredDriverVersion();

        $currentVersion = $projectAnalyser->resolveInstalledDriverVersion($binaryDir);

        if (strpos($currentVersion, $version) === 0) {
            if ($this->io->isVerbose()) {
                $this->io->write(
                    sprintf('Required version (v%s) already installed', $version)
                );
            }

            return;
        }
        
        $this->io->write(
            sprintf('<info>Installing <comment>%s</comment> (v%s)</info>', $driverName, $version)
        );

        $repositoryManager = $this->composerRuntime->getRepositoryManager();
        $localRepository = $repositoryManager->getLocalRepository();
        
        $pluginPackage = $projectAnalyser->resolvePackageForNamespace(
            $localRepository->getCanonicalPackages(),
            get_class($pluginConfig)
        );
        
        $downloadManager = new \Vaimo\WebDriverBinaryDownloader\Installer\DownloadManager(
            $this->composerRuntime->getDownloadManager(),
            $pluginPackage,
            $this->createCacheManager($pluginPackage->getName()),
            $pluginConfig
        );
        
        try {
            $package = $downloadManager->downloadRelease([$version], 2);
        } catch (\Exception $exception) {
            $this->io->write(
                sprintf('<error>%s</error>', $exception->getMessage())
            );
            
            return;
        } 
  
        try {
            $packageManager->installBinaries($package, $binaryDir);

            $this->io->write('');
            $this->io->write('<info>Done</info>');
        } catch (\Exception $exception) {
            $this->io->write(
                sprintf('<error>%s</error>', $exception->getMessage())
            );
        }
    }

    private function createCacheManager($cacheName)
    {
        $composerConfig = $this->composerRuntime->getConfig();

        $cacheDir = $composerConfig->get('cache-dir');
        
        return new \Composer\Cache(
            $this->io,
            $this->utils->composePath($cacheDir, 'files', $cacheName, 'downloads')
        );
    }
}
