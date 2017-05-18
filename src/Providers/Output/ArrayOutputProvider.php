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
        $field['attributes']['name'] = $this->form['name'] . '[' . $propertyPath . ']';
        $field['attributes']['id'] = $this->form['name'] . '_' . str_replace('.', '_', $propertyPath);

        if ($field['mapped'] == false) {
            return $field;
        }

        if (strpos($propertyPath, '.') !== false) {
            $subfields = explode('.', $propertyPath);

            $field['attributes']['name'] = $this->form['name'] . '[' . implode('][', $subfields) . ']';

            // name of the property in the last associated entity
            $propertyPath = array_pop($subfields);

            foreach ($subfields as $subfield) {
                $targetEntity = $this
                    ->typeGuesser
                    ->getMetadata($targetEntity)
                    ->getAssociationMapping($subfield)['targetEntity'];
            }
        }

        $types = $this->typeGuesser->guessType($targetEntity, $propertyPath);
        $guessOptions = $types[1] ?? [];
        $guessRequired = $this->typeGuesser->guessRequired($targetEntity, $propertyPath);

        $field['options']['required'] = $field['options']['required'] ?? $guessRequired;
        $field['type'] = $field['type'] ?? $types[0];
        $field['options'] = array_merge_recursive($guessOptions, $field['options']);

        return $field;
    }

    public function getForm()
    {
        return $this->form;
    }
}
