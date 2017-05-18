<?php

namespace Albegali\DoctrineFormSerializer\Providers\Input;

use Albegali\DoctrineFormSerializer\Configuration\FormConfiguration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

abstract class AbstractInputProvider
{
    /** @var array */
    protected $form;

    abstract public function parse($input);

    public function processConfiguration(array $content = [])
    {
        if (empty($content['form'])) {
            throw new InvalidConfigurationException('Missing parameter "form" in yaml file');
        }

        $processor = new Processor();
        $this->form = $processor->processConfiguration(new FormConfiguration(), $content);
    }

    public function getForm()
    {
        return $this->form;
    }
}
