<?php

namespace Albegali\DoctrineFormSerializer\Guesser;

use Albegali\DoctrineFormSerializer\Configuration\FormConfiguration;

class FieldGuess
{
    /** @var string */
    private $id;
    /** @var string */
    private $name;
    /** @var array */
    private $options = [];
    /** @var string */
    private $type;
    /** @var string */
    private $field;
    /** @var array */
    private $attributes;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName($serializeStrategy): string
    {
        if ($serializeStrategy === FormConfiguration::$serializeStrategyCamelCase) {
            return lcfirst(implode('', array_map('ucfirst', explode('_', $this->name))));
        }

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->name));

    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param array $options
     */
    public function addOption(array $option)
    {
        $this->options = array_merge($this->options, $option);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @param string $field
     */
    public function setField(string $field)
    {
        $this->field = $field;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @param array $attribute
     */
    public function addAttribute(array $attribute)
    {
        $this->attributes = array_merge($this->attributes, $attribute);
    }
}
