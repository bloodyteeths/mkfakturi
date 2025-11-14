<?php

namespace App\Models;

use Laravel\Paddle\SubscriptionItem as CashierSubscriptionItem;

/**
 * Custom Paddle Subscription Item Model
 *
 * Extends Laravel Cashier Paddle's SubscriptionItem model to use a custom table name
 * for clarity and namespace separation.
 */
class PaddleSubscriptionItem extends CashierSubscriptionItem
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'paddle_subscription_items';
}
// CLAUDE-CHECKPOINT
