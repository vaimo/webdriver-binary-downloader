<?php

namespace Lanfest\WebDriverBinaryDownloader\Strategies;

class DownloadStrategy
{
    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Composer\Context
     */
    private $composerContext;

    /**
     * @param \Lanfest\WebDriverBinaryDownloader\Composer\Context $composerContext
     */
    public function __construct(
        \Lanfest\WebDriverBinaryDownloader\Composer\Context $composerContext
    ) {
        $this->composerContext = $composerContext;
    }
    
    public function shouldAllow()
    {
        $composer = $this->composerContext->getLocalComposer();

        $packageResolver = new \Lanfest\WebDriverBinaryDownloader\Resolvers\PackageResolver(
            array($composer->getPackage())
        );

        $repository = $composer->getRepositoryManager()->getLocalRepository();
        $packages = $repository->getCanonicalPackages();

        try {
            $packageResolver->resolveForNamespace($packages, __NAMESPACE__);
        } catch (\Lanfest\WebDriverBinaryDownloader\Exceptions\RuntimeException $exception) {
            return false;
        }

        return true;
    }
}
