<?php

namespace App\Models;

use Laravel\Paddle\Customer as CashierCustomer;

/**
 * Custom Paddle Customer Model
 *
 * Extends Laravel Cashier Paddle's Customer model to use a custom table name
 * to avoid conflicts with InvoiceShelf's customers table.
 */
class PaddleCustomer extends CashierCustomer
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'paddle_customers';
}
// CLAUDE-CHECKPOINT
