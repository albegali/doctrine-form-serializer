<?php

namespace Albegali\DoctrineFormSerializer;

use Albegali\DoctrineFormSerializer\Providers\Input\AbstractInputProvider;
use Albegali\DoctrineFormSerializer\Providers\Output\AbstractOutputProvider;

class FormBuilder
{
    /** @var AbstractInputProvider */
    private $inputProvider;

    /** @var AbstractOutputProvider */
    private $outputProvider;

    public function __construct(AbstractInputProvider $inputProvider, AbstractOutputProvider $outputProvider)
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
