<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\WebDriverBinaryDownloader\Resolvers;

class PackageResolver
{
    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Analysers\PackageAnalyser
     */
    private $packageAnalyser;
    
    public function __construct()
    {
        $this->packageAnalyser = new \Vaimo\WebDriverBinaryDownloader\Analysers\PackageAnalyser();
    }
    
    public function resolvePackageForNamespace(array $packages, $namespace)
    {
        foreach ($packages as $package) {
            if (!$this->packageAnalyser->isPluginPackage($package)) {
                continue;
            }

            if (!$this->packageAnalyser->ownsNamespace($package, $namespace)) {
                continue;
            }

            return $package;
        }

        throw new \Exception('Failed to detect the plugin package');
    }
}
