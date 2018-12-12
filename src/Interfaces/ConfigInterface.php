<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\WebDriverBinaryDownloader\Interfaces;

interface ConfigInterface
{
    const REQUEST_VERSION = 'version';
    const REQUEST_DOWNLOAD = 'download';
    
    /**
     * @return string[]
     */
    public function getPreferences();

    /**
     * @return string
     */
    public function getDriverName();

    /**
     * @return string[]
     */
    public function getRequestUrlConfig();

    /**
     * @return string[]
     */
    public function getBrowserBinaryPaths();

    /**
     * @return string[]
     */
    public function getBrowserVersionPollingConfig();

    /**
     * @return string[]
     */
    public function getDriverVersionPollingConfig();

    /**
     * @return string[]
     */
    public function getBrowserDriverVersionMap();

    /**
     * @return string[]
     */
    public function getDriverVersionHashMap();

    /**
     * @return string[]
     */
    public function getRemoteFileNames();

    /**
     * @return string[]
     */
    public function getExecutableFileNames();

    /**
     * @return string[]
     */
    public function getExecutableFileRenames();
}