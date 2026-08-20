<?php

declare(strict_types=1);


namespace DTL\OapiScg\Model\Type;

use DTL\OapiScg\Model\PhpType;

final class UnionType extends PhpType
{
    /**
     * @param list<PhpType> $types
     */
    public function __construct(private array $types)
    {
    }

    #[\Override]
    public function nativeTypeString(): string
    {
        return $this->typeString(true);
    }

    private function typeString(bool $native = true): string
    {
        $method = $native ? 'nativeTypeString' : 'phpDocString';

        $types = array_combine(
            array_map(
                static fn (PhpType $type) => $type->$method(),
                $this->types
            ),
            array_values($this->types),
        );

        /** @mago-expect lint:strict-behavior */
        if (count($types) === 2 && in_array(new NullType(), $types, false)) {
            foreach ($types as $type) {
                /** @mago-expect lint:identity-comparison */
                if ($type != new NullType) {
                    return sprintf('?%s', $type->$method());
                }
            }
        }

        return implode('|', array_map(
            static fn (PhpType $type) => $type->$method(),
            $types
        ));
    }

    #[\Override]
    public function phpDocString(): string
    {
        return $this->typeString(false);
    }
    /**
     * @param list<mixed> $values
     */
    public static function fromValues(array $values): self
    {
        $types = array_map(static fn (mixed $value) => match (get_debug_type($value)) {
                'string' => new StringLiteralType((string)$value),
                default => new MixedType(),
            }, $values);

        return new self($types);
    }

    public function withType(PhpType $newType): PhpType
    {
        foreach ($this->types as $type) {
            /** @mago-expect lint:identity-comparison */
            if ($type == $newType) {
                return $this;
            }
        }
        return new self([...$this->types, $newType]);
    }
}
