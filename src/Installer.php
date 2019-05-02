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
    private $cliIO;
    
    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Utils\SystemUtils
     */
    private $systemUtils;
    
    /**
     * @param \Composer\Composer $composerRuntime
     * @param \Composer\IO\IOInterface $cliIO
     */
    public function __construct(
        \Composer\Composer $composerRuntime,
        \Composer\IO\IOInterface $cliIO
    ) {
        $this->composerRuntime = $composerRuntime;
        $this->cliIO = $cliIO;
        
        $this->systemUtils = new \Vaimo\WebDriverBinaryDownloader\Utils\SystemUtils();
    }
    
    public function executeWithConfig(ConfigInterface $pluginConfig)
    {
        $composerConfig = $this->composerRuntime->getConfig();
        
        $binaryDir = $composerConfig->get('bin-dir');
        
        $projectAnalyser = new \Vaimo\WebDriverBinaryDownloader\Analysers\ProjectAnalyser(
            $pluginConfig, 
            $this->cliIO->isDebug() ? $this->cliIO : null 
        );
        
        $packageManager = new \Vaimo\WebDriverBinaryDownloader\Managers\PackageManager($pluginConfig);

        $driverName = $pluginConfig->getDriverName();
        
        if (!$projectAnalyser->resolvePlatformSupport()) {
            if ($this->cliIO->isVerbose()) {
                $this->cliIO->write(
                    sprintf('SKIPPING %s setup: platform not supported', $driverName)
                );
            }
            
            return;
        }
        
        $version = $projectAnalyser->resolveRequiredDriverVersion();

        $currentVersion = $projectAnalyser->resolveInstalledDriverVersion($binaryDir);

        if (strpos($currentVersion, $version) === 0) {
            if ($this->cliIO->isVerbose()) {
                $this->cliIO->write(
                    sprintf('Required version (v%s) already installed', $version)
                );
            }

            return;
        }
        
        $this->cliIO->write(
            sprintf('<info>Installing <comment>%s</comment> (v%s)</info>', $driverName, $version)
        );

        $repositoryManager = $this->composerRuntime->getRepositoryManager();
        $localRepository = $repositoryManager->getLocalRepository();

        $packageResolver = new \Vaimo\WebDriverBinaryDownloader\Resolvers\PackageResolver();
        
        $pluginPackage = $packageResolver->resolvePackageForNamespace(
            $localRepository->getCanonicalPackages(),
            get_class($pluginConfig)
        );
        
        $virtualPkgFactory = new \Vaimo\WebDriverBinaryDownloader\Factories\DriverPackageFactory(
            $pluginPackage,
            $pluginConfig
        );
        
        $downloadManager = new \Vaimo\WebDriverBinaryDownloader\Managers\DownloadManager(
            $this->composerRuntime->getDownloadManager(),
            $this->createCacheManager($pluginPackage->getName()),
            $virtualPkgFactory
        );
        
        try {
            $package = $downloadManager->downloadRelease(array($version), 2);
        } catch (\Exception $exception) {
            $this->cliIO->write(
                sprintf('<error>%s</error>', $exception->getMessage())
            );
            
            return;
        }
  
        try {
            $packageManager->installBinaries($package, $binaryDir);

            $this->cliIO->write('');
            $this->cliIO->write('<info>Done</info>');
        } catch (\Exception $exception) {
            $this->cliIO->write(
                sprintf('<error>%s</error>', $exception->getMessage())
            );
        }
    }

    private function createCacheManager($cacheName)
    {
        $composerConfig = $this->composerRuntime->getConfig();

        $cacheDir = $composerConfig->get('cache-dir');
        
        return new \Composer\Cache(
            $this->cliIO,
            $this->systemUtils->composePath($cacheDir, 'files', $cacheName, 'downloads')
        );
    }
}
