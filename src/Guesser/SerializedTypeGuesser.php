<?php

namespace Albegali\DoctrineFormSerializer\Guesser;

use Albegali\DoctrineFormSerializer\Configuration\FormConfiguration;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class SerializedTypeGuesser
{
    /** @var ObjectManager */
    private $objectManager;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($class, $property)
    {
        $metadata = $this->getMetadata($class);

        if ($metadata->hasAssociation($property)) {
            $multiple = $metadata->isCollectionValuedAssociation($property);
            $mapping = $metadata->getAssociationMapping($property);

            return [
                ($multiple ? FormConfiguration::$htmlFields['checkbox'] : FormConfiguration::$htmlFields['select']),
                [
                    'multiple' => $multiple,
                    'entity' => $mapping['targetEntity']
                ]
            ];
        }

        switch ($metadata->getTypeOfField($property)) {
            case Type::TARRAY:
                return [FormConfiguration::$htmlFields['select'], ['multiple' => true, 'expanded' => true]];
            case Type::BOOLEAN:
                return [FormConfiguration::$htmlFields['radio'], ['choices' => [0 => 'No', 1 => 'Si'], 'multiple' => false, 'expanded' => true]];
            case Type::DATETIME:
            case Type::DATETIMETZ:
            case 'vardatetime':
                return [FormConfiguration::$htmlFields['date'], ['format' => 'd/M/y H:i:s', 'widget' => 'text']];
            case Type::DATE:
                return [FormConfiguration::$htmlFields['date'], ['format' => 'd/M/y', 'widget' => 'text']];
            case Type::TIME:
                return [FormConfiguration::$htmlFields['date'], ['format' => 'H:i:s', 'widget' => 'text']];
            case Type::DECIMAL:
            case Type::FLOAT:
            case Type::INTEGER:
            case Type::BIGINT:
            case Type::SMALLINT:
                return [FormConfiguration::$htmlFields['number']];
            case Type::TEXT:
                return [FormConfiguration::$htmlFields['textarea']];
            case Type::STRING:
                return [FormConfiguration::$htmlFields['text']];
            default:
                return [FormConfiguration::$htmlFields['text']];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function guessRequired($class, $property)
    {
        $classMetadata = $this->getMetadata($class);

        // Check whether the field exists and is nullable or not
        if ($classMetadata->hasField($property)) {
            return !$classMetadata->isNullable($property) && Type::BOOLEAN !== $classMetadata->getTypeOfField($property);
        }

        // Check whether the association exists, is a to-one association and its
        // join column is nullable or not
        if ($classMetadata->isAssociationWithSingleJoinColumn($property)) {
            $mapping = $classMetadata->getAssociationMapping($property);

            if (!isset($mapping['joinColumns'][0]['nullable'])) {
                // The "nullable" option defaults to true, in that case the
                // field should not be required.
                return false;
            }

            return !$mapping['joinColumns'][0]['nullable'];
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function guessMaxLength($class, $property)
    {
        $ret = $this->getMetadata($class);
        if ($ret && $ret->hasField($property) && !$ret->hasAssociation($property)) {
            $mapping = $ret->getFieldMapping($property);

            if (isset($mapping['length'])) {
                return $mapping['length'];
            }

            if (in_array($ret->getTypeOfField($property), array(Type::DECIMAL, Type::FLOAT))) {
                return null;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function guessPattern($class, $property)
    {
        $ret = $this->getMetadata($class);
        if ($ret && $ret->hasField($property) && !$ret->hasAssociation($property) && in_array($ret->getTypeOfField($property), array(Type::DECIMAL, Type::FLOAT), true)) {
            return null;
        }
    }

    /**
     * @param $class
     * @return ClassMetadataInfo|ClassMetadata|null
     */
    public function getMetadata($class)
    {
        $class = ClassUtils::getRealClass(ltrim($class, '\\'));

        return $this->objectManager->getClassMetadata($class);
    }
}
