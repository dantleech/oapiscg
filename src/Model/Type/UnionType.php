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
        return $this->typeString(true);
    }

    private function typeString(bool $native = true): string
    {
        $method = $native ? 'nativeTypeString' : 'phpDocString';

        $types = array_combine(
            array_map(
                fn (PhpType $type) => $type->$method(),
                $this->types
            ),
            array_values($this->types),
        );

        if (count($types) === 2 && in_array(new NullType(), $types, false)) {
            foreach ($types as $type) {
                if ($type != new NullType) {
                    return sprintf('?%s', $type->$method());
                }
            }
        }

        return implode('|', array_map(
            fn (PhpType $type) => $type->$method(),
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
        $types = array_map(function (mixed $value) {
            return match (get_debug_type($value)) {
                'string' => new StringLiteralType((string)$value),
                default => new MixedType(),
            };
        }, $values);

        return new self($types);
    }

    public function withType(NullType $nullType): PhpType
    {
        foreach ($this->types as $type) {
            if ($type == $nullType) {
                return $this;
            }
        }
        return new self([...$this->types, $nullType]);
    }
}
