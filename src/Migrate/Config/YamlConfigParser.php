<?php

namespace Migrate\Config;

use Symfony\Component\Yaml\Yaml;

class YamlConfigParser extends BaseConfigParser
{
    public function parse()
    {
        return Yaml::parse(file_get_contents($this->configFile));
    }
}
