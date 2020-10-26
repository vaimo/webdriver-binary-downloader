<?php

namespace LANFest\WebDriverBinaryDownloader;

use LANFest\WebDriverBinaryDownloader\Interfaces\ConfigInterface;
use LANFest\WebDriverBinaryDownloader\Composer\Config;

class Installer implements \LANFest\WebDriverBinaryDownloader\Interfaces\InstallerInterface
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
     * @param \Composer\Composer $composerRuntime
     * @param \Composer\IO\IOInterface $cliIO
     */
    public function __construct(
        \Composer\Composer $composerRuntime,
        \Composer\IO\IOInterface $cliIO
    ) {
        $this->composerRuntime = $composerRuntime;
        $this->cliIO = $cliIO;
    }
    
    public function executeWithConfig(ConfigInterface $pluginConfig)
    {
        $composerCtxFactory = new \LANFest\WebDriverBinaryDownloader\Factories\ComposerContextFactory(
            $this->composerRuntime
        );

        $composerCtx = $composerCtxFactory->create();

        $downloadStrategy = new \LANFest\WebDriverBinaryDownloader\Strategies\DownloadStrategy($composerCtx);

        if (!$downloadStrategy->shouldAllow()) {
            return;
        }
        
        $composerConfig = $this->composerRuntime->getConfig();
        
        $binaryDir = $composerConfig->get(Config::BIN_DIR);
        
        $projectAnalyser = new \LANFest\WebDriverBinaryDownloader\Analysers\ProjectAnalyser(
            $pluginConfig,
            $this->cliIO->isDebug() ? $this->cliIO : null
        );
        
        $packageManager = new \LANFest\WebDriverBinaryDownloader\Managers\PackageManager(
            $pluginConfig,
            $composerConfig->get(Config::VENDOR_DIR)
        );

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
        
        $dlManagerFactory = new \LANFest\WebDriverBinaryDownloader\Factories\DownloadManagerFactory(
            $composerCtx,
            $this->cliIO
        );
        
        $downloadManager = $dlManagerFactory->create($pluginConfig);
        
        try {
            $package = $downloadManager->downloadRelease(array($version), 5);
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
}
