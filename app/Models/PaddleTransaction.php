<?php

namespace App\Models;

use Laravel\Paddle\Transaction as CashierTransaction;

/**
 * Custom Paddle Transaction Model
 *
 * Extends Laravel Cashier Paddle's Transaction model to use a custom table name
 * to avoid conflicts with InvoiceShelf's transactions table.
 */
class PaddleTransaction extends CashierTransaction
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'paddle_transactions';
}
// CLAUDE-CHECKPOINT
