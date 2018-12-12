<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\WebDriverBinaryDownloader\Installer;

class VersionResolver
{
    /**
     * @var \Composer\Package\Version\VersionParser 
     */
    private $versionParser;
    
    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Installer\Utils
     */
    private $installerUtils;

    public function __construct() 
    {
        $this->versionParser = new \Composer\Package\Version\VersionParser();
        $this->installerUtils = new \Vaimo\WebDriverBinaryDownloader\Installer\Utils();
    }
    
    public function pollForVersion(array $binaryPaths, array $versionPollingConfig)
    {
        $processExecutor = new \Composer\Util\ProcessExecutor();
        
        $processExecutor::setTimeout(10);

        foreach ($binaryPaths as $path) {
            if ($path !== null && !is_executable($path)) {
                continue;
            }

            foreach ($versionPollingConfig as $callTemplate => $resultPatterns) {
                $output = '';

                $pollCommand = sprintf($callTemplate, $path);
                $processExecutor->execute($pollCommand, $output);

                $output = str_replace(chr(0), '', trim($output));
                
                if (!$output) {
                    continue;
                }

                foreach ($resultPatterns as $pattern) {
                    preg_match(sprintf('/%s/i', $pattern), $output, $matches);

                    $result = $matches[1] ?? false;

                    if (!$result) {
                        continue;
                    }
                    
                    try {
                        $this->versionParser->parseConstraints($result);
                    } catch (\UnexpectedValueException $exception) {
                        continue;
                    }

                    return $result;
                }
            }
        }

        return '';
    }
}
