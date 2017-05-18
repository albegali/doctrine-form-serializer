<?php

namespace Albegali\DoctrineFormSerializer\Providers\Input;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

class YamlInputProvider extends AbstractInputProvider
{
    public function parse($fileName)
    {
        if (!file_exists($fileName)) {
            throw new FileNotFoundException('File ' . $fileName . ' not found');
        }
        $content = Yaml::parse(file_get_contents($fileName));

        $this->processConfiguration($content);
    }
}
