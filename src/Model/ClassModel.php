<?php

declare(strict_types=1);


namespace DTL\OapiScg\Model;

final class ClassModel
{
    /**
     * @param array<array-key,PropertyModel> $properties
     */
    public function __construct(public FullyQualifiedName $name, public array $properties, public ?string $description = null)
    {
    }

    public function property(string $name): PropertyModel
    {
        if (!array_key_exists($name, $this->properties)) {
            throw new \RuntimeException(sprintf(
                'Property "%s" does not exist, known properties: "%s"',
                $name, 
                implode('", "', array_keys($this->properties))
            ));
        }

        return $this->properties[$name];
    }

}
