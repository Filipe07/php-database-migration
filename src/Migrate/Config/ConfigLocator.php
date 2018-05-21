<?php

namespace Migrate\Config;

class ConfigLocator
{
    protected $configPath;

    public function __construct($configPath)
    {
        $this->configPath = $configPath;
    }

    public function locate($filename)
    {
        $path = $this->configPath . '/' . $filename;
        if (file_exists($path)) {
            return new YamlConfigParser($path);
        }

        $path = $this->configPath . '/' . $filename . '.yml';
        if (file_exists($path)) {
            return new YamlConfigParser($path);
        }

        throw new \RuntimeException(
            sprintf('File %s does not exist or incorrect', $filename)
        );
    }
}
