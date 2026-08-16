<?php

namespace DTL\OapiScg\Tests\Unit\Model\Type;

use DTL\OapiScg\Model\Type\UnionType;
use PHPUnit\Framework\TestCase;

final class UnionTypeTest extends TestCase
{
    public function testFromValues(): void
    {
        $type = UnionType::fromValues(['foobar', 'barfoo']);
        self::assertEquals('string', $type->nativeTypeString());
        self::assertEquals('"foobar"|"barfoo"', $type->phpDocString());
    }
}
