<?php

namespace Lanfest\WebDriverBinaryDownloader\Managers;

use Composer\Package\PackageInterface;
use Lanfest\WebDriverBinaryDownloader\Analysers\PlatformAnalyser as OsDetector;

class PackageManager
{
    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Interfaces\ConfigInterface
     */
    private $pluginConfig;

    /**
     * @var \Composer\Util\Filesystem
     */
    private $fileSystem;

    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Analysers\PlatformAnalyser
     */
    private $platformAnalyser;

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
    private $vendorDir;

    /**
     * @param \Lanfest\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig
     * @param string $vendorDir
     */
    public function __construct(
        \Lanfest\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig,
        $vendorDir
    ) {
        $this->pluginConfig = $pluginConfig;
        $this->vendorDir = $vendorDir;

        $this->fileSystem = new \Composer\Util\Filesystem();

        $this->platformAnalyser = new \Lanfest\WebDriverBinaryDownloader\Analysers\PlatformAnalyser();

        $this->systemUtils = new \Lanfest\WebDriverBinaryDownloader\Utils\SystemUtils();
        $this->dataUtils = new \Lanfest\WebDriverBinaryDownloader\Utils\DataUtils();
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess) | Using standard Composer utility class, no alternatives
     *
     * @param PackageInterface $package
     * @param string $binDir
     * @return string[]
     * @throws \Exception
     */
    public function installBinaries(PackageInterface $package, $binDir)
    {
        $sourceDir = $this->systemUtils->composePath($this->vendorDir, $package->getTargetDir());

        $matches = [];

        $binaries = $package->getBinaries();

        foreach ($binaries as $binary) {
            if (file_exists($this->systemUtils->composePath($sourceDir, $binary))) {
                $matches[] = $this->systemUtils->composePath($sourceDir, $binary);
            }

            $globPattern = $this->systemUtils->composePath($sourceDir, '**', $binary);

            $matches = array_merge(
                $matches,
                $this->systemUtils->recursiveGlob($globPattern)
            );
        }

        if (!$matches) {
            $errorMessage = sprintf(
                'Could not locate binaries (%s) from downloaded source',
                implode(
                    ', ',
                    array_unique(
                        array_map(function ($item) {
                            return basename($item);
                        }, $binaries)
                    )
                )
            );

            throw new \Exception($errorMessage);
        }

        $fileRenames = $this->pluginConfig->getExecutableFileRenames();

        $this->fileSystem->ensureDirectoryExists($binDir);

        foreach (array_filter($matches, 'is_executable') as $fromPath) {
            $fileName = basename($fromPath);

            $toPath = $this->systemUtils->composePath(
                $binDir,
                $this->dataUtils->extractValue($fileRenames, $fileName, $fileName)
            );

            $this->fileSystem->copyThenRemove($fromPath, $toPath);

            $platformCode = $this->platformAnalyser->getPlatformCode();

            if ($platformCode !== OsDetector::TYPE_WIN32 && $platformCode !== OsDetector::TYPE_WIN64) {
                \Composer\Util\Silencer::call('chmod', $toPath, 0777 & ~umask());
            }
        }

        return $matches;
    }
}
