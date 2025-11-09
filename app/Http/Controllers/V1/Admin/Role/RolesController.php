<?php

namespace App\Http\Controllers\V1\Admin\Role;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\User;
use Illuminate\Http\Request;
use Silber\Bouncer\BouncerFacade;
use Silber\Bouncer\Database\Role;

class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Role::class);

        // Set Bouncer scope to current company
        $companyId = $request->header('company');
        if ($companyId) {
            BouncerFacade::scope()->to($companyId);
        }

        $roles = Role::when($request->has('orderByField'), function ($query) use ($request) {
            return $query->orderBy($request['orderByField'], $request['orderBy']);
        })
            ->when($companyId, function ($query) use ($companyId) {
                return $query->where('scope', $companyId);
            })
            ->get();

        return RoleResource::collection($roles);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RoleRequest $request)
    {
        $this->authorize('create', Role::class);

        // Set Bouncer scope to current company before creating role
        $companyId = $request->header('company');
        if ($companyId) {
            BouncerFacade::scope()->to($companyId);
        }

        $role = Role::create($request->getRolePayload());

        $this->syncAbilities($request, $role);

        return new RoleResource($role);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        $this->authorize('view', $role);

        // Set Bouncer scope to current company
        $companyId = request()->header('company');
        if ($companyId) {
            BouncerFacade::scope()->to($companyId);
        }

        return new RoleResource($role);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(RoleRequest $request, Role $role)
    {
        $this->authorize('update', $role);

        // Set Bouncer scope to current company
        $companyId = $request->header('company');
        if ($companyId) {
            BouncerFacade::scope()->to($companyId);
        }

        $role->update($request->getRolePayload());

        $this->syncAbilities($request, $role);

        return new RoleResource($role);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Spatie\Permission\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);

        // Set Bouncer scope to current company
        $companyId = request()->header('company');
        if ($companyId) {
            BouncerFacade::scope()->to($companyId);
        }

        $users = User::whereIs($role->name)->get()->toArray();

        if (! empty($users)) {
            return respondJson('role_attached_to_users', 'Roles Attached to user');
        }

        $role->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    private function syncAbilities(RoleRequest $request, $role)
    {
        foreach (config('abilities.abilities') as $ability) {
            $check = array_search($ability['ability'], array_column($request->abilities, 'ability'));
            if ($check !== false) {
                BouncerFacade::allow($role)->to($ability['ability'], $ability['model']);
            } else {
                BouncerFacade::disallow($role)->to($ability['ability'], $ability['model']);
            }
        }

        return true;
    }
}
