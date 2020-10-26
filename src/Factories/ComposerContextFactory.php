<?php

namespace LANFest\WebDriverBinaryDownloader\Factories;

class ComposerContextFactory
{
    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \Composer\Composer
     */
    private static $globalComposer;

    /**
     * @param \Composer\Composer $composer
     */
    public function __construct(
        \Composer\Composer $composer
    ) {
        $this->composer = $composer;
    }

    public function create()
    {
        $instances = array(
            $this->composer
        );

        if (self::$globalComposer === null) {
            self::$globalComposer = \Composer\Factory::createGlobal(
                new \Composer\IO\NullIO(),
                true
            );
        }

        array_unshift($instances, self::$globalComposer);

        return new \LANFest\WebDriverBinaryDownloader\Composer\Context(
            array_filter($instances)
        );
    }
}
