<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Exception\InvalidObjectName;
use Doctrine\DBAL\Schema\Name\Parser;
use Doctrine\DBAL\Schema\Name\Parser\Identifier;

use function array_map;
use function count;
use function crc32;
use function dechex;
use function implode;
use function str_replace;
use function strtolower;
use function strtoupper;
use function substr;

/**
 * The abstract asset allows to reset the name of all assets without publishing this to the public userland.
 *
 * This encapsulation hack is necessary to keep a consistent state of the database schema. Say we have a list of tables
 * array($tableName => Table($tableName)); if you want to rename the table, you have to make sure this does not get
 * recreated during schema migration.
 */
abstract class AbstractAsset
{
    protected string $_name = '';

    /**
     * Namespace of the asset. If none isset the default namespace is assumed.
     */
    protected ?string $_namespace = null;

    protected bool $_quoted = false;

    /** @var list<Identifier> */
    private array $identifiers = [];

    /**
     * Sets the name of this asset.
     */
    protected function _setName(string $name): void
    {
        if ($name !== '') {
            $parser = new Parser();

            try {
                $identifiers = $parser->parse($name);
            } catch (Parser\Exception $e) {
                throw InvalidObjectName::fromParserException($name, $e);
            }
        } else {
            $identifiers = [];
        }

        switch (count($identifiers)) {
            case 0:
                $this->_name       = '';
                $this->_quoted     = false;
                $this->_namespace  = null;
                $this->identifiers = [];

                return;
            case 1:
                $namespace = null;
                $name      = $identifiers[0];
                break;

            case 2:
                /** @psalm-suppress PossiblyUndefinedArrayOffset */
                [$namespace, $name] = $identifiers;
                break;

            default:
                throw InvalidObjectName::tooManyQualifiers($name, count($identifiers) - 1);
        }

        $this->_name       = $name->getValue();
        $this->_quoted     = $name->isQuoted();
        $this->_namespace  = $namespace?->getValue();
        $this->identifiers = $identifiers;
    }

    /**
     * Is this asset in the default namespace?
     */
    public function isInDefaultNamespace(string $defaultNamespaceName): bool
    {
        return $this->_namespace === $defaultNamespaceName || $this->_namespace === null;
    }

    /**
     * Gets the namespace name of this asset.
     *
     * If NULL is returned this means the default namespace is used.
     */
    public function getNamespaceName(): ?string
    {
        return $this->_namespace;
    }

    /**
     * The shortest name is stripped of the default namespace. All other
     * namespaced elements are returned as full-qualified names.
     */
    public function getShortestName(?string $defaultNamespaceName): string
    {
        $shortestName = $this->getName();
        if ($this->_namespace === $defaultNamespaceName) {
            $shortestName = $this->_name;
        }

        return strtolower($shortestName);
    }

    /**
     * Checks if this asset's name is quoted.
     */
    public function isQuoted(): bool
    {
        return $this->_quoted;
    }

    /**
     * Checks if this identifier is quoted.
     */
    protected function isIdentifierQuoted(string $identifier): bool
    {
        return isset($identifier[0]) && ($identifier[0] === '`' || $identifier[0] === '"' || $identifier[0] === '[');
    }

    /**
     * Trim quotes from the identifier.
     */
    protected function trimQuotes(string $identifier): string
    {
        return str_replace(['`', '"', '[', ']'], '', $identifier);
    }

    /**
     * Returns the name of this schema asset.
     */
    public function getName(): string
    {
        if ($this->_namespace !== null) {
            return $this->_namespace . '.' . $this->_name;
        }

        return $this->_name;
    }

    /**
     * Returns the quoted representation of this asset's name. If the name is unquoted, it is normalized according to
     * the platform's unquoted name normalization rules.
     */
    public function getQuotedName(AbstractPlatform $platform): string
    {
        $parts = array_map(static function (Identifier $identifier) use ($platform): string {
            $value = $identifier->getValue();

            if (! $identifier->isQuoted()) {
                $value = $platform->normalizeUnquotedIdentifier($value);
            }

            return $platform->quoteSingleIdentifier($value);
        }, $this->identifiers);

        return implode('.', $parts);
    }

    /**
     * Generates an identifier from a list of column names obeying a certain string length.
     *
     * This is especially important for Oracle, since it does not allow identifiers larger than 30 chars,
     * however building idents automatically for foreign keys, composite keys or such can easily create
     * very long names.
     *
     * @param array<int, string> $columnNames
     */
    protected function _generateIdentifierName(array $columnNames, string $prefix = '', int $maxSize = 30): string
    {
        $hash = implode('', array_map(static function ($column): string {
            return dechex(crc32($column));
        }, $columnNames));

        return strtoupper(substr($prefix . '_' . $hash, 0, $maxSize));
    }
}
