<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\WebDriverBinaryDownloader\Strategies;

class DownloadStrategy
{
    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Composer\Context 
     */
    private $composerContext;

    /**
     * @param \Vaimo\WebDriverBinaryDownloader\Composer\Context $composerContext
     */
    public function __construct(
        \Vaimo\WebDriverBinaryDownloader\Composer\Context $composerContext
    ) {
        $this->composerContext = $composerContext;
    }
    
    public function shouldAllow()
    {
        $composer = $this->composerContext->getLocalComposer();

        $packageResolver = new \Vaimo\WebDriverBinaryDownloader\Resolvers\PackageResolver(
            array($composer->getPackage())
        );

        $repository = $composer->getRepositoryManager()->getLocalRepository();
        $packages = $repository->getCanonicalPackages();

        try {
            $packageResolver->resolveForNamespace($packages, __NAMESPACE__);
        } catch (\Vaimo\WebDriverBinaryDownloader\Exceptions\RuntimeException $exception) {
            return false;
        }

        return true;
    }
}
