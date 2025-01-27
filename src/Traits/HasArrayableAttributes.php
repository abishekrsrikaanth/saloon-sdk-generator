<?php

declare(strict_types=1);

namespace Crescat\SaloonSdkGenerator\Traits;

use Crescat\SaloonSdkGenerator\Enums\SimpleType;
use Crescat\SaloonSdkGenerator\Exceptions\InvalidAttributeTypeException;
use ReflectionClass;

trait HasArrayableAttributes
{
    use HasComplexArrayTypes;

    public function toArray(): array
    {
        $constructor = (new ReflectionClass(static::class))->getConstructor();
        if (! $constructor) {
            throw new InvalidAttributeTypeException('Class to be deserialized must have a constructor');
        }
        $reflectionParams = $constructor->getParameters() ?? [];

        $attributeTypes = [];
        foreach ($reflectionParams as $param) {
            $name = $param->getName();
            $type = $param->getType()->getName();

            // `array` could either be read as a simple PHP array, or a typed array that
            // we want to deserialize into an array of objects
            if ($type === 'array') {
                $type = static::getArrayType($name);
            }

            $attributeTypes[$name] = $type;
        }

        $asArray = [];
        foreach ($attributeTypes as $name => $type) {
            $attributeAsArray = $this->valueToArray($this->{$name}, $type);
            if ($name === 'additionalProperties') {
                $asArray = array_merge($asArray, $attributeAsArray);
            } else {
                $asArray[$name] = $attributeAsArray;
            }
        }

        return $asArray;
    }

    public function valueToArray(mixed $value, SimpleType|array|string $type): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (is_string($type) && SimpleType::tryFrom($type)) {
            return $value;
        } elseif (is_string($type)) {
            if (! class_exists($type)) {
                throw new InvalidAttributeTypeException("Class `$type` does not exist");
            }

            return $value->toArray();
        } elseif (is_array($type)) {
            $typeLen = count($type);

            if ($typeLen !== 1) {
                throw new InvalidAttributeTypeException(
                    "Complex array type must have a single value (the type of the array items), $typeLen given"
                );
            }

            $arrayified = [];
            foreach ($value as $item) {
                $arrayified[] = $this->valueToArray($item, $type[0]);
            }

            return $arrayified;
        }
    }
}
