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
        // Hardcoded Facturino mail configuration
        // All emails are sent from the system address for deliverability
        $mailConfig = [
            'from_name' => 'Facturino',
            'from_mail' => 'invoices@facturino.mk',
        ];

        return response()->json($mailConfig);
    }
}
// CLAUDE-CHECKPOINT
