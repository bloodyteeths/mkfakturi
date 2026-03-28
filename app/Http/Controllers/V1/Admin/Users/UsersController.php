<?php

namespace App\Http\Controllers\V1\Admin\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteUserRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Company;
use App\Models\User;
use App\Services\UserCountService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $limit = $request->has('limit') ? $request->limit : 10;

        $user = $request->user();

        $query = User::with('companies')
            ->applyFilters($request->all())
            ->where('id', '<>', $user->id);

        $totalCount = (clone $query)->count();

        $users = $query->latest()->paginate($limit);

        return UserResource::collection($users)
            ->additional([
                'meta' => [
                    'user_total_count' => $totalCount,
                ],
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse|\App\Http\Resources\UserResource
     */
    public function store(UserRequest $request)
    {
        $this->authorize('create', User::class);

        if ($request->is_existing_user) {
            $user = User::where('email', $request->email)->first();

            if (! $user) {
                return response()->json([
                    'error' => 'user_not_found',
                    'message' => 'No user found with the provided email address.',
                ], 422);
            }

            $user->attachCompanies($request->companies);
        } else {
            $user = User::createFromRequest($request);
        }

        return new UserResource($user);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\JsonResponse|\App\Http\Resources\UserResource
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse|\App\Http\Resources\UserResource
     */
    public function update(UserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $user->updateFromRequest($request);

        return new UserResource($user);
    }

    /**
     * Get user usage stats for the current company's subscription tier.
     */
    public function usage(Request $request, UserCountService $userCountService)
    {
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'company_header_missing'], 400);
        }

        $company = Company::with('subscription')->find($companyId);

        if (! $company) {
            return response()->json(['error' => 'company_not_found'], 404);
        }

        return response()->json([
            'usage' => $userCountService->getUsageStats($company),
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DeleteUserRequest $request)
    {
        $this->authorize('delete multiple users', User::class);

        if ($request->users) {
            User::deleteUsers($request->users);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Resend invitation email to a user.
     */
    public function resendInvitation(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $companyId = $request->header('company');
        $user->sendInvitationEmail($companyId);

        return response()->json([
            'success' => true,
            'message' => 'Invitation resent successfully.',
        ]);
    }

    /**
     * Toggle user active/inactive status.
     */
    public function toggleActive(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $user->update(['is_active' => ! $user->is_active]);

        return new UserResource($user->fresh());
    }

    /**
     * Export users list as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('companies')
            ->applyFilters($request->all())
            ->where('id', '<>', $request->user()->id)
            ->latest()
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="users-export-'.now()->format('Y-m-d').'.csv"',
        ];

        return response()->stream(function () use ($users) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Name', 'Email', 'Phone', 'Role', 'Companies', 'Status', 'Last Login', 'Added On']);

            foreach ($users as $user) {
                $companyNames = $user->relationLoaded('companies')
                    ? $user->companies->pluck('name')->implode(', ')
                    : '';

                fputcsv($handle, [
                    $user->name,
                    $user->email,
                    $user->phone ?? '',
                    $user->role ?? 'user',
                    $companyNames,
                    $user->is_active ? 'Active' : 'Inactive',
                    $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i') : 'Never',
                    $user->created_at->format('Y-m-d'),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Generate Овластување (authorization letter) PDF for a user.
     */
    public function ovlastuvanje(Request $request, User $user)
    {
        $this->authorize('view', $user);

        $companyId = $request->header('company');
        $company = Company::find($companyId);

        if (! $company) {
            return response()->json(['error' => 'company_not_found'], 404);
        }

        $owner = User::find($company->owner_id);

        $data = [
            'company' => $company,
            'user' => $user,
            'owner' => $owner,
            'date' => now()->format('d.m.Y'),
            'city' => $company->city ?? 'Скопје',
        ];

        $pdf = \PDF::loadView('app.pdf.reports.ovlastuvanje', $data);
        $pdf->setPaper('a4');

        return $pdf->stream('ovlastuvanje-'.$user->name.'.pdf');
    }
}
