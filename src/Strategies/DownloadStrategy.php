<?php

namespace LANFest\WebDriverBinaryDownloader\Strategies;

class DownloadStrategy
{
    /**
     * @var \LANFest\WebDriverBinaryDownloader\Composer\Context
     */
    private $composerContext;

    /**
     * @param \LANFest\WebDriverBinaryDownloader\Composer\Context $composerContext
     */
    public function __construct(
        \LANFest\WebDriverBinaryDownloader\Composer\Context $composerContext
    ) {
        $this->composerContext = $composerContext;
    }
    
    public function shouldAllow()
    {
        $composer = $this->composerContext->getLocalComposer();

        $packageResolver = new \LANFest\WebDriverBinaryDownloader\Resolvers\PackageResolver(
            array($composer->getPackage())
        );

        $repository = $composer->getRepositoryManager()->getLocalRepository();
        $packages = $repository->getCanonicalPackages();

        try {
            $packageResolver->resolveForNamespace($packages, __NAMESPACE__);
        } catch (\LANFest\WebDriverBinaryDownloader\Exceptions\RuntimeException $exception) {
            return false;
        }

        return true;
    }
}
