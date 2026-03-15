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

        $query = User::applyFilters($request->all())
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
}
