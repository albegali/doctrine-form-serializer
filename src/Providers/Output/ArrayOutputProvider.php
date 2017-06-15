<?php

namespace Albegali\DoctrineFormSerializer\Providers\Output;

class ArrayOutputProvider extends AbstractOutputProvider
{
    public function parse($form, $defaultFormData = null)
    {
        $this->form = $this->mergeDefaultData($form, $defaultFormData);

        foreach ($this->form['blocks'] as &$block) {
            foreach ($block['fields'] as $propertyPath => $field) {
                $block['fields'][$propertyPath] = $this->populateFieldAttributes($propertyPath, $field);
            }
        }
    }

    protected function populateFieldAttributes($propertyPath, $field)
    {
        $targetEntity = $this->form['entity'];
        $serializeStrategy = $this->form['serialize_strategy'];

        if ($field['mapped'] == false) {
            $propertyPath = explode('.', $propertyPath);
            $field['attributes']['name'] = $this->form['name'] . '[' . implode('][', $propertyPath) . ']';
            $field['attributes']['id'] = $this->form['name'] . '_' . implode('_', $propertyPath);
            unset($field['target_property']);

            return $field;
        }

        $fieldGuess = $this->typeGuesser->guess(
            $targetEntity,
            $propertyPath,
            $field['target_property'],
            $this->form['name']
        );

        $field['attributes']['name'] = $fieldGuess->getName($serializeStrategy);
        $field['attributes']['id'] = $fieldGuess->getId();
        $field['type'] = $field['type'] ?? $fieldGuess->getType();
        $field['field'] = $field['field'] ?? $fieldGuess->getField();
        $field['options'] = array_replace_recursive($fieldGuess->getOptions(), $field['options']);

        unset($field['target_property']);

        return $field;
    }

    public function getForm()
    {
        return $this->form;
    }
}
