<?php

namespace Albegali\DoctrineFormSerializer\Providers\Input;

interface InputProviderInterface
{
    public function parse($resource);

    public function getForm();
}
