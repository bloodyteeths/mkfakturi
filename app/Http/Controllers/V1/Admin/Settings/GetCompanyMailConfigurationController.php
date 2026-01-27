<?php

namespace App\Http\Controllers\V1\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GetCompanyMailConfigurationController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // Facturino mail configuration - use config values which default to fakturi@facturino.mk
        // All emails are sent from the system address for deliverability
        $mailConfig = [
            'from_name' => config('mail.from.name', 'Facturino'),
            'from_mail' => config('mail.from.address', 'fakturi@facturino.mk'),
        ];

        return response()->json($mailConfig);
    }
}
// CLAUDE-CHECKPOINT
