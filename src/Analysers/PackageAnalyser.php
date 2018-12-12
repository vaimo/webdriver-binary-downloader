<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\WebDriverBinaryDownloader\Analysers;

use Vaimo\WebDriverBinaryDownloader\Composer\Config as ComposerConfig;

class PackageAnalyser
{
    public function isPluginPackage(\Composer\Package\PackageInterface $package)
    {
        return $package->getType() === ComposerConfig::COMPOSER_PLUGIN_TYPE;
    }
    
    public function ownsNamespace(\Composer\Package\PackageInterface $package, $namespace)
    {
        $autoloadConfig = $package->getAutoload();

        return (bool)array_filter(
            array_keys($autoloadConfig[ComposerConfig::PSR4_CONFIG] ?? []),
            function ($item) use ($namespace) {
                return strpos($namespace, rtrim($item, '\\')) === 0;
            }
        );
    }
}
