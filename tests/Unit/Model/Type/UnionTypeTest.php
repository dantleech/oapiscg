<?php

declare(strict_types=1);


namespace DTL\OapiScg\Tests\Unit\Model\Type;

use DTL\OapiScg\Model\Type\UnionType;
use PHPUnit\Framework\TestCase;

final class UnionTypeTest extends TestCase
{
    public function testFromValues(): void
    {
        $type = UnionType::fromValues(['foobar', 'barfoo']);
        static::assertSame('string', $type->nativeTypeString());
        static::assertSame('"foobar"|"barfoo"', $type->phpDocString());
    }
}
