<?php

namespace App\Exceptions;

use RuntimeException;

class StockTransferException extends RuntimeException
{
    public const SAME_WAREHOUSE = 'same_warehouse';

    public const ITEMS_REQUIRED = 'items_required';

    public const INVALID_QUANTITY = 'invalid_quantity';

    public const ITEM_NOT_FOUND = 'item_not_found';

    public const INSUFFICIENT_STOCK = 'insufficient_stock';

    public function __construct(
        public readonly string $reason,
        public readonly array $context = []
    ) {
        parent::__construct($reason);
    }
}
