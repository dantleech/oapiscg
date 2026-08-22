<?php

declare(strict_types=1);


namespace DTL\OapiScg\Model;

final class ClassModel
{
    /**
     * @var array<string,PropertyModel>
     */
    public array $properties;

    /**
     * @param array<array-key,PropertyModel> $properties
     */
    public function __construct(public FullyQualifiedName $name, array $properties, public ?string $description = null)
    {
        $this->properties = array_combine(array_map(static fn (PropertyModel $p) => $p->name, $properties), array_values($properties));
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
