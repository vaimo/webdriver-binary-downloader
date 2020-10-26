<?php

namespace LANFest\WebDriverBinaryDownloader\Factories;

class DriverPackageFactory
{
    /**
     * @var \Composer\Package\Version\VersionParser
     */
    private $versionParser;
    
    public function __construct()
    {
        $this->versionParser = new \Composer\Package\Version\VersionParser();
    }
    
    public function create($name, $remoteFile, $version, $targetDir, $binFiles = array())
    {
        $package = new \Composer\Package\Package(
            $name,
            $this->versionParser->normalize($version),
            $version
        );

        /**
         * This guarantees that parallel runs on same system (that might be sharing the cache folder) do not
         * end up crashing into each-other when the package is being downloaded (where tmp cache file unlinking)
         * imght cause issues when the package uid hash generation ends up creating same file name reference on
         * multiple runs.
         */
        $package->setExtra(array(
            'uid' => md5(uniqid(rand(), true))
        ));
        
        $package->setBinaries($binFiles);
        $package->setInstallationSource('dist');

        $package->setDistType(
            $this->resolveDistType($remoteFile)
        );

        $package->setTargetDir($targetDir);
        $package->setDistUrl($remoteFile);

        return $package;
    }
    
    private function resolveDistType($remoteFile)
    {
        switch (pathinfo($remoteFile, PATHINFO_EXTENSION)) {
            case 'zip':
                return 'zip';
            case 'exe':
                return 'file';
        }

        return 'tar';
    }
}
