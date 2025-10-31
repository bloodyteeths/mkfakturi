<?php

namespace App\Console\Commands;

use App\Space\PdfTemplateUtils;
use Illuminate\Console\Command;

class RefreshTemplateCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'templates:cache:refresh {type? : Template type to refresh (e.g. invoice)} {--warm : Warm the cache after clearing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear cached PDF template previews and optionally warm the cache';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        $types = $type ? [$type] : PdfTemplateUtils::availableTemplateTypes();

        if (empty($types)) {
            $this->warn('No template types found.');

            return self::SUCCESS;
        }

        foreach ($types as $templateType) {
            PdfTemplateUtils::clearTemplateCache($templateType);
            $this->info(sprintf('Cleared cache for "%s" templates.', $templateType));

            if ($this->option('warm')) {
                PdfTemplateUtils::getFormattedTemplates($templateType);
                $this->line(sprintf('Warmed cache for "%s" templates.', $templateType));
            }
        }

        return self::SUCCESS;
    }
}
