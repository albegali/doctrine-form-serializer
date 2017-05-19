<?php

namespace Albegali\DoctrineFormSerializer\Providers\Output;

class ArrayOutputProvider extends AbstractOutputProvider
{
    public function parse($form, $defaultFormData = null)
    {
        $this->form = $this->mergeDefaultData($form, $defaultFormData);

        foreach ($this->form['fields'] as &$field) {
            $field = $this->populateFieldAttributes($field);
        }
    }

    protected function populateFieldAttributes($field)
    {
        // path of the property
        $propertyPath = $field['property'];
        $targetEntity = $this->form['entity'];

        if ($field['mapped'] == false) {
            $field['attributes']['name'] = $this->form['name'] . '[' . $propertyPath . ']';
            $field['attributes']['id'] = $this->form['name'] . '_' . str_replace('.', '_', $propertyPath);
            return $field;
        }

        $fieldGuess = $this->typeGuesser->guess($targetEntity, $propertyPath, $this->form['name']);

        $field['attributes']['name'] = $fieldGuess->getName();
        $field['attributes']['id'] = $fieldGuess->getId();
        $field['type'] = $field['type'] ?? $fieldGuess->getType();
        $field['field'] = $field['field'] ?? $fieldGuess->getField();

        $field['options'] = array_merge_recursive($fieldGuess->getOptions(), $field['options']);

        return $field;
    }

    public function getForm()
    {
        return $this->form;
    }
}
