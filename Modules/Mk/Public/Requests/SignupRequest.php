<?php

namespace Modules\Mk\Public\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SignupRequest
 *
 * Validation rules for public company registration.
 * Handles cleanup of abandoned signups (never logged in, created >1h ago).
 */
class SignupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Public endpoint - no authorization required
        return true;
    }

    /**
     * Prepare the data for validation.
     *
     * Clean up abandoned signups: if email belongs to a user who
     * never logged in and was created more than 1 hour ago, delete
     * the orphaned user and their company so the email can be reused.
     */
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (! $email) {
            return;
        }

        $existingUser = User::where('email', $email)->first();

        if (! $existingUser) {
            return;
        }

        // Only clean up genuinely abandoned signups: the account must have NEVER
        // been logged into. Facturino authenticates via Sanctum SPA cookies, which
        // do NOT create personal_access_tokens rows — so tokens()->exists() is
        // always false even for active daily users. Relying on it flagged live
        // customers as "abandoned" and destructively detached/deleted their
        // company (data-loss bug). last_login_at is set on every login and is the
        // correct "account was used" signal.
        if ($existingUser->last_login_at !== null) {
            return;
        }

        // Belt-and-suspenders: personal access tokens still count as usage.
        if ($existingUser->tokens()->exists()) {
            return;
        }

        if ($existingUser->created_at->gt(now()->subHour())) {
            return;
        }

        Log::info('Cleaning up abandoned signup', [
            'email' => $email,
            'user_id' => $existingUser->id,
            'created_at' => $existingUser->created_at,
        ]);

        // Perform the destructive cleanup atomically so a mid-way failure (e.g. an
        // FK RESTRICT on user/company delete) can't leave the account half-deleted
        // with its user_company pivot detached but the user/company still present.
        try {
            DB::transaction(function () use ($existingUser) {
                // Delete orphaned company (if user owns one with no invoices)
                foreach ($existingUser->companies as $company) {
                    if ((int) $company->owner_id === (int) $existingUser->id) {
                        // Only delete if company has no real data (no invoices)
                        if (! $company->invoices()->exists()) {
                            $company->delete();
                        }
                    }
                }

                // Detach from companies and delete user
                $existingUser->companies()->detach();
                $existingUser->tokens()->delete();
                $existingUser->delete();
            });
        } catch (\Throwable $e) {
            // If cleanup can't complete cleanly, leave the account untouched rather
            // than orphaning it. The unique:email rule will surface a clear error.
            Log::warning('Abandoned signup cleanup aborted (left intact)', [
                'email' => $email,
                'user_id' => $existingUser->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Company information
            'company_name' => [
                'required',
                'string',
                'max:255',
                'min:2',
            ],
            'vat_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Z]{2}[0-9]{8,12}$/', // EU VAT format
            ],
            'tax_id' => [
                'nullable',
                'string',
                'max:20',
            ],

            // User information
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'min:8',
            ],
            'password_confirmation' => [
                'nullable',
            ],

            // Subscription plan
            'plan' => [
                'required',
                'string',
                'in:free,starter,standard,business,max',
            ],
            'billing_period' => [
                'required',
                'string',
                'in:monthly,yearly',
            ],
            'payment_currency' => [
                'nullable',
                'string',
                'in:mkd,eur',
            ],

            // Company preferences (optional - defaults to MKD/mk)
            'currency' => [
                'nullable',
                'integer',
                'exists:currencies,id',
            ],
            'language' => [
                'nullable',
                'string',
                'in:ar,nl,en,fr,de,ja,it,lv,pl,pt_BR,sr,ko,es,sv,sk,vi,cs,el,hr,mk,sq,tr,th',
            ],

            // Company address (optional)
            'address' => [
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'nullable',
                'string',
                'max:255',
            ],
            'zip' => [
                'nullable',
                'string',
                'max:20',
            ],

            // Referral tracking (optional)
            'referral_code' => [
                'nullable',
                'string',
                'max:50',
            ],
            'partner_id' => [
                'nullable',
                'integer',
                'exists:partners,id',
            ],
            'affiliate_link_id' => [
                'nullable',
                'integer',
                'exists:affiliate_links,id',
            ],

            // UTM tracking (optional)
            'utm_source' => ['nullable', 'string', 'max:100'],
            'utm_medium' => ['nullable', 'string', 'max:100'],
            'utm_campaign' => ['nullable', 'string', 'max:100'],

            // Terms acceptance (optional - implicit acceptance by registering)
            'accept_terms' => [
                'nullable',
            ],
            'accept_privacy' => [
                'nullable',
            ],
        ];
    }

    /**
     * Get custom validation messages (Macedonian)
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'Внесете име на компанија.',
            'company_name.min' => 'Името мора да содржи најмалку 2 знаци.',
            'vat_number.regex' => 'ДДВ бројот мора да биде во EU формат (пр. MK12345678).',
            'name.required' => 'Внесете го вашето име.',
            'email.required' => 'Внесете email адреса.',
            'email.unique' => 'Оваа email адреса е веќе регистрирана. Обидете се со најава.',
            'password.required' => 'Внесете лозинка.',
            'password.min' => 'Лозинката мора да содржи најмалку 8 знаци.',
            'password.confirmed' => 'Лозинките не се совпаѓаат.',
            'plan.required' => 'Изберете план за претплата.',
            'plan.in' => 'Невалиден план за претплата.',
            'billing_period.required' => 'Изберете период на наплата.',
            'billing_period.in' => 'Невалиден период на наплата.',
        ];
    }

    /**
     * Get custom attribute names for validation errors
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'company_name' => 'име на компанија',
            'vat_number' => 'ДДВ број',
            'tax_id' => 'ЕДБ',
            'name' => 'име и презиме',
            'email' => 'email адреса',
            'password' => 'лозинка',
            'password_confirmation' => 'потврда на лозинка',
            'plan' => 'план за претплата',
            'billing_period' => 'период на наплата',
            'referral_code' => 'код за препорака',
        ];
    }
}
// CLAUDE-CHECKPOINT
