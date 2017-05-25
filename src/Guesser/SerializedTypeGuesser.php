<?php

namespace Albegali\DoctrineFormSerializer\Guesser;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;

class SerializedTypeGuesser
{
    /** @var ObjectManager */
    private $objectManager;

    /** @var MetadataFactoryInterface */
    private $validatorMetadataFactory;

    /** @var string */
    private $property;

    /** @var string */
    private $fieldName;

    /** @var string */
    private $fieldId;

    /** @var ClassMetadata|ClassMetadataInfo */
    private $metadata;

    public static $htmlInputTags = ['input', 'select', 'textarea'];

    /** @var array */
    public static $htmlInputTypes = [
        'button',
        'checkbox',
        'color',
        'date',
        'datetime-local',
        'email',
        'file',
        'hidden',
        'number',
        'password',
        'radio',
        'range',
        'reset',
        'search',
        'submit',
        'tel',
        'text',
        'time',
        'url',
        'not_set'
    ];

    public function __construct(ObjectManager $objectManager, MetadataFactoryInterface $validatorMetadataFactory = null)
    {
        $this->objectManager = $objectManager;
        $this->validatorMetadataFactory = $validatorMetadataFactory;
    }

    public function guess(string $class, string $property, $formName = ''): FieldGuess
    {
        $fieldGuess = new FieldGuess();

        $this->property = $property;
        $class = $this->getPropertyClassName($formName, $class);
        $this->metadata = $this->getMetadata($class);

        $fieldGuess->setName($this->guessName());
        $fieldGuess->setId($this->guessId());
        $fieldGuess->setField($this->guessField());
        $fieldGuess->setType($this->guessType());
        $fieldGuess->setOptions($this->guessOptions());
        $fieldGuess->addOption($this->guessRequired());
        $fieldGuess->addOption($this->guessValidators($class));
        $fieldGuess->addOption($this->guessLabel());

        return $fieldGuess;
    }

    public function getPropertyClassName($formName, $class)
    {
        $this->fieldName = $formName;
        $this->fieldId = $formName;

        if (strpos($this->property, '.') !== false) {
            $subfields = explode('.', $this->property);

            // name of the property in the last associated entity
            $this->property = array_pop($subfields);

            foreach ($subfields as $subfield) {
                $this->fieldName .= '[' . $subfield . ']' . ($this->isMultipleRelation($class, $subfield) ? '[0]' : '');
                $this->fieldId .= '_' . $subfield . ($this->isMultipleRelation($class, $subfield) ? '_0' : '');

                $class = $this
                    ->getMetadata($class)
                    ->getAssociationMapping($subfield)['targetEntity'];
            }
        }

        $this->fieldName .= '[' . $this->property . ']';
        $this->fieldId .= '_' . $this->property;

        return $class;
    }

    protected function isMultipleRelation($class, $property)
    {
        $reader = new AnnotationReader();
        $reflClass = new \ReflectionClass($class);
        $refProp = $reflClass->getProperty($property);
        $annotations = $reader->getPropertyAnnotations($refProp);
        foreach ($annotations as $annotation) {
            if ($annotation instanceof OneToMany || $annotation instanceof ManyToMany) {
                return true;
            }
        }

        return false;
    }

    public function guessName()
    {
        return $this->fieldName;
    }

    public function guessId()
    {
        return $this->fieldId;
    }

    public function guessField()
    {
        if ($this->metadata->hasAssociation($this->property)) {
            $multiple = $this->metadata->isCollectionValuedAssociation($this->property);

            return $multiple ? 'input' : 'select';
        }

        switch ($this->metadata->getTypeOfField($this->property)) {
            case Type::TEXT:
                return 'textarea';
            default:
                return 'input';
        }
    }

    public function guessType()
    {
        if ($this->metadata->hasAssociation($this->property)) {
            $multiple = $this->metadata->isCollectionValuedAssociation($this->property);

            return $multiple ? 'checkbox' : '';
        }

        switch ($this->metadata->getTypeOfField($this->property)) {
            case Type::TARRAY:
            case Type::SIMPLE_ARRAY:
            case Type::JSON_ARRAY:
                return 'checkbox';
            case Type::BOOLEAN:
                return 'radio';
            case Type::DATETIME:
            case Type::DATETIMETZ:
            case 'vardatetime':
                return 'datetime-local';
            case Type::DATE:
                return 'date';
            case Type::TIME:
                return 'time';
            default:
                return 'text';
        }
    }

    public function guessOptions()
    {
        if ($this->metadata->hasAssociation($this->property)) {
            $multiple = $this->metadata->isCollectionValuedAssociation($this->property);
            $mapping = $this->metadata->getAssociationMapping($this->property);
            /** @var EntityRepository $entityRepository */
            $entityRepository = $this->objectManager->getRepository($mapping['targetEntity']);

            return [
                'multiple' => $multiple,
                'choices' => method_exists($entityRepository, 'getAllActiveKeyValue') ?
                    $entityRepository->getAllActiveKeyValue() : []
            ];
        }

        switch ($this->metadata->getTypeOfField($this->property)) {
            case Type::BOOLEAN:
                return ['choices' => [0 => 'No', 1 => 'Si'], 'multiple' => false, 'expanded' => true];
                break;
            default:
                return [];
        }
    }

    public function guessValidators($class)
    {
        $validators = ['constraints' => []];
        $validators['constraints'][Length::class] = new Length(['min' => 0, 'max' => $this->guessMaxLength()]);
        if ($this->validatorMetadataFactory) {
            $classMetadata = $this->validatorMetadataFactory->getMetadataFor($class);

            if ($classMetadata instanceof ClassMetadataInterface && $classMetadata->hasPropertyMetadata($this->property)) {
                $memberMetadatas = $classMetadata->getPropertyMetadata($this->property);

                foreach ($memberMetadatas as $memberMetadata) {
                    $constraints = $memberMetadata->getConstraints();

                    foreach ($constraints as $constraint) {
                        $validators['constraints'][get_class($constraint)] = $constraint;
                    }
                }
            }
        }

        return $validators;
    }

    /**
     * {@inheritdoc}
     */
    public function guessRequired()
    {
        $required = false;

        // Check whether the field exists and is nullable or not
        if ($this->metadata->hasField($this->property)) {
            $required =  !$this->metadata->isNullable($this->property) && Type::BOOLEAN !== $this->metadata->getTypeOfField($this->property);
        } else if ($this->metadata->isAssociationWithSingleJoinColumn($this->property)) {
            $mapping = $this->metadata->getAssociationMapping($this->property);

            if (!isset($mapping['joinColumns'][0]['nullable'])) {
                // The "nullable" option defaults to true, in that case the
                // field should not be required.
                $required = false;
            } else {
                $required = !$mapping['joinColumns'][0]['nullable'];
            }

        }

        return ['required' => $required];
    }

    /**
     * {@inheritdoc}
     */
    public function guessLabel()
    {
        return ['label' => ucwords(preg_replace('/(?<!^)[A-Z]/', ' $0', $this->property))];
    }

    /**
     * {@inheritdoc}
     */
    public function guessMaxLength()
    {
        if ($this->metadata && $this->metadata->hasField($this->property) && !$this->metadata->hasAssociation($this->property)) {
            $mapping = $this->metadata->getFieldMapping($this->property);

            if (isset($mapping['length'])) {
                return $mapping['length'];
            }

            if (in_array($this->metadata->getTypeOfField($this->property), array(Type::DECIMAL, Type::FLOAT))) {
                return null;
            }
        }

        return null;
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
