<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Tests\Schema;

use Doctrine\DBAL\Schema\Exception\InvalidObjectName;
use Doctrine\DBAL\Schema\Identifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AbstractAssetTest extends TestCase
{
    #[DataProvider('invalidNameProvider')]
    public function testInvalidName(string $name): void
    {
        $this->expectException(InvalidObjectName::class);
        new Identifier($name);
    }

    /** @return iterable<array{string}> */
    public static function invalidNameProvider(): iterable
    {
        return [
            // parse error
            [' '],

            // too many qualifiers
            ['i.am.overqualified'],
        ];
    }
}
