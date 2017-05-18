<?php

namespace Albegali\DoctrineFormSerializer\Providers\Output;

use Albegali\DoctrineFormSerializer\Guesser\SerializedTypeGuesser;

abstract class AbstractOutputProvider
{
    /** @var SerializedTypeGuesser */
    protected $typeGuesser;

    /** @var array */
    protected $form;

    public function __construct(SerializedTypeGuesser $typeGuesser)
    {
        $this->typeGuesser = $typeGuesser;
    }

    public function parse($form, $defaultFormData = null)
    {
        $this->form = $this->mergeDefaultData($form, $defaultFormData);
    }

    public function getForm()
    {
        return $this->form;
    }

    protected function mergeDefaultData($form, $defaultFormData)
    {
        if (null === $defaultFormData) {
            $defaultFormData = [];
        }

        return $this->recursiveMerge($form, $defaultFormData);
    }

    private function recursiveMerge($form, $defaultFormData)
    {
//        foreach ($form['blocks'] as &$block) {
//            foreach ($block['fields'] as &$field) {
//                if (!empty($field['blocks'])) {
//                    $field = $this->recursiveMerge($field, $defaultFormData[$block['prefix']]);
//                } elseif (!empty($defaultFormData[$block['prefix']][$field['name']])) {
//                    $field['default'] = $defaultFormData[$block['prefix']][$field['name']];
//                }
//
//                if (!empty($field['choices'])) {
//                    $field['choices'] = $this->configureChoices($field['choices']);
//                }
//            }
//        }

        return $form;
    }
}