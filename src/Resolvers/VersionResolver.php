<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\WebDriverBinaryDownloader\Resolvers;

class VersionResolver
{
    /**
     * @var \Composer\Package\Version\VersionParser
     */
    private $versionParser;

    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Utils\StringUtils
     */
    private $stringUtils;
    
    /**
     * @var \Vaimo\WebDriverBinaryDownloader\Utils\DataUtils
     */
    private $dataUtils;
    
    public function __construct()
    {
        $this->versionParser = new \Composer\Package\Version\VersionParser();

        $this->stringUtils = new \Vaimo\WebDriverBinaryDownloader\Utils\StringUtils();
        $this->dataUtils = new \Vaimo\WebDriverBinaryDownloader\Utils\DataUtils();
    }
    
    public function pollForExecutableVersion(array $binaryPaths, array $versionPollingConfig)
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
                    preg_match(
                        sprintf('/%s/i', $pattern),
                        $output,
                        $matches
                    );

                    $result = $this->dataUtils->extractValue($matches, 1, false);

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
    
    public function pollForDriverVersion(array $versionCheckUrls, $browserVersion)
    {
        $versionCheckUrls = $this->dataUtils->assureArrayValue($versionCheckUrls);
        
        $variables = array(
            'major' => $this->stringUtils->strTokOffset($browserVersion, 1),
            'major-minor' => $this->stringUtils->strTokOffset($browserVersion, 2)
        );

        $version = null;
        
        foreach ($versionCheckUrls as $versionCheckUrl) {
            if ($version) {
                break;
            }

            $version = trim(
                @file_get_contents(
                    $this->stringUtils->stringFromTemplate($versionCheckUrl, $variables)
                )
            );
        }

        return $version;
    }
}
