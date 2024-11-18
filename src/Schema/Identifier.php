<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Schema;

/**
 * An abstraction class for an asset identifier.
 *
 * Wraps identifier names like column names in indexes / foreign keys
 * in an abstract class for proper quotation capabilities.
 *
 * @internal
 */
class Identifier extends AbstractAsset
{
}
