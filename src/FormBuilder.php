<?php

namespace Albegali\DoctrineFormSerializer;

use Albegali\DoctrineFormSerializer\Providers\Input\AbstractInputProvider;
use Albegali\DoctrineFormSerializer\Providers\Input\InputProviderInterface;
use Albegali\DoctrineFormSerializer\Providers\Output\AbstractOutputProvider;

class FormBuilder
{
    /** @var InputProviderInterface */
    private $inputProvider;

    /** @var AbstractOutputProvider */
    private $outputProvider;

    public function __construct(InputProviderInterface $inputProvider, AbstractOutputProvider $outputProvider)
    {
        $this->inputProvider = $inputProvider;
        $this->outputProvider = $outputProvider;
    }

    public function build($resource, $defaultFormData = null)
    {
        $this->inputProvider->parse($resource);
        $form = $this->inputProvider->getForm();

        $this->outputProvider->parse($form, $defaultFormData);
        return $this->outputProvider->getForm();
    }
}
