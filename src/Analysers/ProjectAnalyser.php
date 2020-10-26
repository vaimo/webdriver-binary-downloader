<?php

namespace Lanfest\WebDriverBinaryDownloader\Analysers;

use Lanfest\WebDriverBinaryDownloader\Interfaces\ConfigInterface;
use Lanfest\WebDriverBinaryDownloader\Interfaces\PlatformAnalyserInterface as Platform;

class ProjectAnalyser
{
    /**
     * @var \Composer\Package\Version\VersionParser
     */
    private $versionParser;

    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Interfaces\ConfigInterface
     */
    private $pluginConfig;

    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Analysers\EnvironmentAnalyser
     */
    private $environmentAnalyser;

    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Analysers\PlatformAnalyser
     */
    private $platformAnalyser;

    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Resolvers\VersionResolver
     */
    private $versionResolver;

    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Utils\SystemUtils
     */
    private $systemUtils;

    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Utils\DataUtils
     */
    private $dataUtils;

    /**
     * @var string
     */
    private $browserVersion;

    /**
     * @param \Lanfest\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig
     * @param \Composer\IO\IOInterface $cliIO
     */
    public function __construct(
        \Lanfest\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig,
        \Composer\IO\IOInterface $cliIO = null
    ) {
        $this->pluginConfig = $pluginConfig;

        $this->environmentAnalyser = new \Lanfest\WebDriverBinaryDownloader\Analysers\EnvironmentAnalyser(
            $pluginConfig,
            $cliIO
        );

        $this->versionParser = new \Composer\Package\Version\VersionParser();

        $this->platformAnalyser = new \Lanfest\WebDriverBinaryDownloader\Analysers\PlatformAnalyser();
        $this->versionResolver = new \Lanfest\WebDriverBinaryDownloader\Resolvers\VersionResolver();

        $this->systemUtils = new \Lanfest\WebDriverBinaryDownloader\Utils\SystemUtils();
        $this->dataUtils = new \Lanfest\WebDriverBinaryDownloader\Utils\DataUtils();
    }

    public function resolvePlatformSupport()
    {
        $platformCode = $this->platformAnalyser->getPlatformCode();

        $fileNames = $this->pluginConfig->getExecutableFileNames();

        return (bool)(
            $this->dataUtils->extractValue($fileNames, $platformCode, false)
        );
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
            $this->systemUtils->composePath(
                $binaryDir,
                $this->dataUtils->extractValue($executableRenames, $executableName, $executableName)
            )
        );

        $binaries = array($driverPath);

        if ($platformCode === Platform::TYPE_WIN64 || $platformCode === Platform::TYPE_WIN32) {
            $binaries = array_merge(
                $binaries,
                array_map(function ($item) {
                    return str_replace('\\', '\\\\', $item);
                }, $binaries)
            );
        }

        $installedVersion = $this->versionResolver->pollForExecutableVersion(
            $binaries,
            $this->pluginConfig->getDriverVersionPollingConfig()
        );
        
        $versionMap = $this->pluginConfig->getBrowserDriverVersionMap();

        foreach ($versionMap as $driverVersion) {
            if (!is_array($driverVersion)) {
                $driverVersion = array($driverVersion);
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

        $version = $this->dataUtils->extractValue($preferences, 'version');

        if (!$version) {
            $version = $this->resolveBrowserDriverVersion(
                $this->resolveBrowserVersion()
            );
        }

        if (!$version) {
            $versionCheckUrls = $this->dataUtils->assureArrayValue(
                $this->dataUtils->extractValue($requestConfig, ConfigInterface::REQUEST_VERSION, array())
            );

            $version = $this->versionResolver->pollForRemoteVersion(
                $versionCheckUrls,
                $this->resolveBrowserVersion()
            );
        }

        if (!$version) {
            $version = $this->getHighestDriverVersion();
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

            return is_array($driverVersion)
                ? reset($driverVersion)
                : $driverVersion;
        }

        return '';
    }

    private function getHighestDriverVersion()
    {
        $versionMap = array_filter($this->pluginConfig->getBrowserDriverVersionMap());

        $version = reset($versionMap);

        if (is_array($version)) {
            $version = reset($version);
        }

        return $version;
    }
    
    public function resolveBrowserVersion()
    {
        if ($this->browserVersion === null) {
            $this->browserVersion = $this->environmentAnalyser->resolveBrowserVersion();
        }

        return $this->browserVersion;
    }
}
