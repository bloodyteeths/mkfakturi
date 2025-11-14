<?php

namespace App\Models;

use Laravel\Paddle\Subscription as CashierSubscription;

/**
 * Custom Paddle Subscription Model
 *
 * Extends Laravel Cashier Paddle's Subscription model to use a custom table name
 * for clarity and namespace separation.
 */
class PaddleSubscription extends CashierSubscription
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'paddle_subscriptions';
}
// CLAUDE-CHECKPOINT
