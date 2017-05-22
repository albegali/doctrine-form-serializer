<?php

namespace Albegali\DoctrineFormSerializer\Providers\Input;

use Symfony\Component\Yaml\Yaml;

class YamlInputProvider extends AbstractFileInputProvider
{
    protected function importFile($file)
    {
        return Yaml::parse(file_get_contents($file), Yaml::PARSE_CONSTANT);
    }
}
