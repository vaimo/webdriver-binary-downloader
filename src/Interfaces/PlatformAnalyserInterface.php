<?php

namespace LANFest\WebDriverBinaryDownloader\Interfaces;

interface PlatformAnalyserInterface
{
    const TYPE_LINUX32 = 'linux32';
    const TYPE_LINUX64 = 'linux64';
    const TYPE_WIN32 = 'win32';
    const TYPE_WIN64 = 'win64';
    const TYPE_MAC64 = 'mac64';

    /**
     * @return string
     */
    public function getPlatformCode();

    /**
     * @return string
     */
    public function getPlatformName();
}
