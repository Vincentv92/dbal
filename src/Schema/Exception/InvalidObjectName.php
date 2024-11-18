<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Schema\Exception;

use Doctrine\DBAL\Schema\Name\Parser;
use Doctrine\DBAL\Schema\SchemaException;
use InvalidArgumentException;

use function sprintf;

/** @psalm-immutable */
class InvalidObjectName extends InvalidArgumentException implements SchemaException
{
    public static function fromParserException(string $name, Parser\Exception $parserException): self
    {
        return new self(sprintf('Unable to parse object name "%s".', $name), 0, $parserException);
    }

    public static function tooManyQualifiers(string $tableName, int $number): self
    {
        return new self(sprintf('Object name %s contains %d qualifiers. At most one is allowed.', $tableName, $number));
    }
}
