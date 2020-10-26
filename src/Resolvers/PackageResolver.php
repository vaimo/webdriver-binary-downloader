<?php

namespace LANFest\WebDriverBinaryDownloader\Resolvers;

class PackageResolver
{
    /**
     * @var \Composer\Package\PackageInterface[]
     */
    private $additionalPackages;

    /**
     * @var \LANFest\WebDriverBinaryDownloader\Analysers\PackageAnalyser
     */
    private $packageAnalyser;

    /**
     * @param \Composer\Package\PackageInterface[] $additionalPackages
     */
    public function __construct(
        array $additionalPackages = array()
    ) {
        $this->additionalPackages = $additionalPackages;

        $this->packageAnalyser = new \LANFest\WebDriverBinaryDownloader\Analysers\PackageAnalyser();
    }

    
    public function resolveForNamespace(array $packages, $namespace)
    {
        $packages = array_merge(
            $this->additionalPackages,
            array_values($packages)
        );

        foreach ($packages as $package) {
            if (!$this->packageAnalyser->ownsNamespace($package, $namespace)) {
                continue;
            }

            return $package;
        }

        throw new \LANFest\WebDriverBinaryDownloader\Exceptions\RuntimeException(
            'Failed to detect the plugin package'
        );
    }
}
