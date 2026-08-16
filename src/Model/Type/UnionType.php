<?php

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
        $types = array_combine(
            array_map(
                fn (PhpType $type) => $type->nativeTypeString(),
                $this->types
            ),
            array_values($this->types),
        );
        return implode('|', array_map(
            fn (PhpType $type) => $type->nativeTypeString(),
            $types
        ));
    }

    #[\Override]
    public function phpDocString(): string
    {
        return implode('|', array_map(
            fn (PhpType $type) => $type->phpDocString(),
            $this->types
        ));
    }
    /**
     * @param list<mixed> $values
     */
    public static function fromValues(array $values): self
    {
        $types = array_map(function (mixed $value) {
            return match (get_debug_type($value)) {
                'string' => new StringLiteralType($value),
                default => new MixedType(),
            };
        }, $values);

        return new self($types);
    }
}
