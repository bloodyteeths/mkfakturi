<?php

namespace App\Http\Controllers\V1\Admin\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteUserRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
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

        $users = User::applyFilters($request->all())
            ->where('id', '<>', $user->id)
            ->latest()
            ->paginate($limit);

        return UserResource::collection($users)
            ->additional([
                'meta' => [
                    'user_total_count' => User::count(),
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
            \Log::info('Looking for existing user', [
                'email' => $request->email,
                'email_length' => strlen($request->email),
                'email_trimmed' => trim($request->email),
            ]);

            // Try direct DB query first to bypass any model scopes
            $userExists = \DB::table('users')->where('email', $request->email)->first();
            \Log::info('Direct DB query result', [
                'found' => $userExists ? 'yes' : 'no',
                'user_id' => $userExists->id ?? null,
            ]);

            $user = User::where('email', $request->email)->first();

            if (! $user) {
                \Log::warning('User not found', [
                    'searched_email' => $request->email,
                    'all_emails_eloquent' => User::pluck('email')->toArray(),
                    'all_emails_db' => \DB::table('users')->pluck('email')->toArray(),
                ]);

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
