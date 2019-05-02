<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\WebDriverBinaryDownloader\Analysers;

use Vaimo\WebDriverBinaryDownloader\Interfaces\ConfigInterface;
use Vaimo\WebDriverBinaryDownloader\Interfaces\PlatformAnalyserInterface as Platform;

class ProjectAnalyser
{
    /**
     * @var \Composer\Package\Version\VersionParser
     */
    private $versionParser;
    
    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Interfaces\ConfigInterface
     */
    private $pluginConfig;

    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Analysers\EnvironmentAnalyser
     */
    private $environmentAnalyser;

    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Analysers\PlatformAnalyser
     */
    private $platformAnalyser;

    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Installer\VersionResolver
     */
    private $versionResolver;

    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Analysers\PackageAnalyser 
     */
    private $packageAnalyser;
    
    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Installer\Utils
     */
    private $utils;

    /**
     * @var \Composer\Package\CompletePackage
     */
    private $ownerPackage;
    
    /**
     * @param \Vaimo\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig
     */
    public function __construct(
        \Vaimo\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig
    ) {
        $this->pluginConfig = $pluginConfig;

        $this->environmentAnalyser = new \Vaimo\WebDriverBinaryDownloader\Analysers\EnvironmentAnalyser($pluginConfig);

        $this->versionParser = new \Composer\Package\Version\VersionParser();

        $this->platformAnalyser = new \Vaimo\WebDriverBinaryDownloader\Analysers\PlatformAnalyser();
        $this->versionResolver = new \Vaimo\WebDriverBinaryDownloader\Installer\VersionResolver();
        $this->packageAnalyser = new \Vaimo\WebDriverBinaryDownloader\Analysers\PackageAnalyser();
        
        $this->utils = new \Vaimo\WebDriverBinaryDownloader\Installer\Utils();
    }
    
    public function resolvePlatformSupport()
    {
        $platformCode = $this->platformAnalyser->getPlatformCode();
        
        $fileNames = $this->pluginConfig->getExecutableFileNames();

        return (bool)($fileNames[$platformCode] ?? false);
    }
    
    public function resolveInstalledDriverVersion($binaryDir)
    {
        $platformCode = $this->platformAnalyser->getPlatformCode();

        $executableNames = $this->pluginConfig->getExecutableFileNames();
        $remoteFiles = $this->pluginConfig->getRemoteFileNames();

        if (!isset($executableNames[$platformCode], $remoteFiles[$platformCode])) {
            throw new \Exception('Failed to resolve a file for the platform. Download driver manually');
        }

        $executableName = $executableNames[$platformCode];
        $executableRenames = $this->pluginConfig->getExecutableFileRenames();
        
        $driverPath = realpath(
            $this->utils->composePath($binaryDir, $executableRenames[$executableName] ?? $executableName)
        );
        
        $binaries = [$driverPath];
        
        if ($platformCode === Platform::TYPE_WIN64 || $platformCode === Platform::TYPE_WIN32) {
            $binaries = array_merge($binaries, array_map(function ($item) {
                return str_replace('\\', '\\\\', $item);
            }, $binaries));
        }
        
        $installedVersion = $this->versionResolver->pollForVersion(
            $binaries,
            $this->pluginConfig->getDriverVersionPollingConfig()
        );
        
        $versionMap = $this->pluginConfig->getBrowserDriverVersionMap();

        foreach ($versionMap as $driverVersion) {
            if (!is_array($driverVersion)) {
                $driverVersion = [$driverVersion];
            }
            
            if (in_array($installedVersion, $driverVersion)) {
                $installedVersion = reset($driverVersion);
            }  
        }

        return $installedVersion;
    }

    public function resolveRequiredDriverVersion()
    {
        $preferences = $this->pluginConfig->getPreferences();
        $requestConfig = $this->pluginConfig->getRequestUrlConfig();

        $version = $preferences['version'];
        
        if (!$preferences['version']) {
            $version = $this->resolveBrowserDriverVersion(
                $this->environmentAnalyser->resolveBrowserVersion()
            );

            $versionCheckUrls = $requestConfig[ConfigInterface::REQUEST_VERSION] ?? [];
            
            if (!is_array($versionCheckUrls)) {
                $versionCheckUrls = [$versionCheckUrls];
            }

            foreach ($versionCheckUrls as $versionCheckUrl) {
                if (!$version) {
                    break;
                }

                $version = trim(
                    @file_get_contents($versionCheckUrl)
                );
            }

            if (!$version) {
                $versionMap = array_filter($this->pluginConfig->getBrowserDriverVersionMap());
                $version = reset($versionMap);
                
                if (is_array($version)) {
                    $version = reset($version);
                }
            }
        }

        try {
            $this->versionParser->parseConstraints($version);
        } catch (\UnexpectedValueException $exception) {
            throw new \Exception(sprintf('Incorrect version string: "%s"', $version));
        }
        
        return $version;
    }

    private function resolveBrowserDriverVersion($browserVersion)
    {
        $chromeVersion = $browserVersion;

        if (!$chromeVersion) {
            return '';
        }

        $majorVersion = strtok($chromeVersion, '.');

        $driverVersionMap = $this->pluginConfig->getBrowserDriverVersionMap();

        foreach ($driverVersionMap as $browserMajor => $driverVersion) {
            if ($majorVersion < $browserMajor) {
                continue;
            }

            return is_array($driverVersion) ? reset($driverVersion) : $driverVersion;
        }

        return '';
    }

    public function resolvePackageForNamespace(array $packages, $namespace)
    {
        if ($this->ownerPackage === null) {
            foreach ($packages as $package) {
                if (!$this->packageAnalyser->isPluginPackage($package)) {
                    continue;
                }

                if (!$this->packageAnalyser->ownsNamespace($package, $namespace)) {
                    continue;
                }
                
                $this->ownerPackage = $package;

                break;
            }
        }

        if (!$this->ownerPackage) {
            throw new \Exception('Failed to detect the plugin package');
        }

        return $this->ownerPackage;
    }
}
