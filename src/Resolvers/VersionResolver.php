<?php

namespace Lanfest\WebDriverBinaryDownloader\Resolvers;

class VersionResolver
{
    /**
     * @var \Composer\IO\IOInterface
     */
    private $cliIO;
    
    /**
     * @var \Composer\Package\Version\VersionParser
     */
    private $versionParser;

    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Utils\StringUtils
     */
    private $stringUtils;
    
    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Utils\DataUtils
     */
    private $dataUtils;
    
    public function __construct(
        \Composer\IO\IOInterface $cliIO = null
    ) {
        $this->cliIO = $cliIO;
        
        $this->versionParser = new \Composer\Package\Version\VersionParser();

        $this->stringUtils = new \Lanfest\WebDriverBinaryDownloader\Utils\StringUtils();
        $this->dataUtils = new \Lanfest\WebDriverBinaryDownloader\Utils\DataUtils();
    }
    
    public function pollForExecutableVersion(array $binaryPaths, array $versionPollingConfig)
    {
        $processExecutor = new \Composer\Util\ProcessExecutor();
        
        $processExecutor::setTimeout(10);

        foreach ($binaryPaths as $path) {
            if ($path !== null && !is_executable($path)) {
                $this->writeTitle('Polling skipped', $path);

                continue;
            }

            foreach ($versionPollingConfig as $callTemplate => $resultPatterns) {
                $output = '';

                $pollCommand = sprintf($callTemplate, $path);

                $this->writeTitle('Polling local version with', $pollCommand);
                
                $processExecutor->execute($pollCommand, $output);

                $output = str_replace(chr(0), '', trim($output));

                $this->writeLn(sprintf('> %s', $output));
                
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

                    $this->writeLn(sprintf('>> %s', $result));

                    if (!$result) {
                        continue;
                    }
                    
                    try {
                        $this->versionParser->parseConstraints($result);
                    } catch (\UnexpectedValueException $exception) {
                        $this->writeLn('>>> INVALID');
                        
                        continue;
                    }

                    $this->writeLn('>>> VALID');

                    return $result;
                }
            }
        }

        return '';
    }
    
    public function pollForRemoteVersion(array $versionCheckUrls, $browserVersion)
    {
        $variables = array(
            'major' => $this->stringUtils->strTokOffset($browserVersion, 1),
            'major-minor' => $this->stringUtils->strTokOffset($browserVersion, 2)
        );

        $version = null;
        
        foreach ($versionCheckUrls as $versionCheckUrl) {
            if ($version) {
                break;
            }

            $queryUrl = $this->stringUtils->stringFromTemplate($versionCheckUrl, $variables);

            $this->writeTitle('Polling remote version with', $queryUrl);

            $result = @file_get_contents($queryUrl);

            $this->writeLn(sprintf('>>> %s', $result));

            try {
                $this->versionParser->parseConstraints(trim($result));
            } catch (\UnexpectedValueException $exception) {
                $this->writeLn('>>> INVALID');
                
                continue;
            }

            $this->writeLn('>>> VALID');
            
            $version = trim($result);
        }

        return $version;
    }
    
    private function writeTitle($title, $subTitle)
    {
        $this->writeLn(
            sprintf('### <comment>%s:</comment> <info>%s</info>', $title, $subTitle)
        );
    }
    
    private function writeLn($message)
    {
        if (!$this->cliIO) {
            return;
        }
        
        $this->cliIO->write($message);
    }
}
