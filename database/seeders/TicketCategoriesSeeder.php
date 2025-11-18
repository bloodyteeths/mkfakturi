<?php

namespace Database\Seeders;

use Coderflex\LaravelTicket\Models\Category;
use Illuminate\Database\Seeder;

class TicketCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            [
                'name' => 'Technical Support',
                'slug' => 'technical-support',
                'is_visible' => true,
            ],
            [
                'name' => 'Billing Question',
                'slug' => 'billing-question',
                'is_visible' => true,
            ],
            [
                'name' => 'Feature Request',
                'slug' => 'feature-request',
                'is_visible' => true,
            ],
            [
                'name' => 'Bug Report',
                'slug' => 'bug-report',
                'is_visible' => true,
            ],
            [
                'name' => 'Account Issue',
                'slug' => 'account-issue',
                'is_visible' => true,
            ],
            [
                'name' => 'E-Invoice Support',
                'slug' => 'e-invoice-support',
                'is_visible' => true,
            ],
            [
                'name' => 'Bank Integration',
                'slug' => 'bank-integration',
                'is_visible' => true,
            ],
            [
                'name' => 'Tax Compliance',
                'slug' => 'tax-compliance',
                'is_visible' => true,
            ],
            [
                'name' => 'Data Migration',
                'slug' => 'data-migration',
                'is_visible' => true,
            ],
            [
                'name' => 'Other',
                'slug' => 'other',
                'is_visible' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command->info('Ticket categories created successfully!');
    }
}
// CLAUDE-CHECKPOINT: Created ticket categories seeder with Facturino-specific categories
