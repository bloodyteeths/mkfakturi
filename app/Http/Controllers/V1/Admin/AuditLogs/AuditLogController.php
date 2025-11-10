<?php

namespace App\Http\Controllers\V1\Admin\AuditLogs;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', AuditLog::class);

        $limit = $request->has('per_page') ? $request->per_page : 20;

        $query = AuditLog::with(['user', 'auditable'])
            ->whereCompany();

        // Apply filters
        if ($request->has('auditable_type') && $request->auditable_type) {
            $query->forModel($request->auditable_type);
        }

        if ($request->has('auditable_id') && $request->auditable_id) {
            $query->where('auditable_id', $request->auditable_id);
        }

        if ($request->has('user_id') && $request->user_id) {
            $query->byUser($request->user_id);
        }

        if ($request->has('event') && $request->event) {
            $query->event($request->event);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('created_at', '<=', $request->date_to);
        }

        // Order by newest first
        $query->orderBy('created_at', 'desc');

        $auditLogs = $query->paginate($limit);

        return AuditLogResource::collection($auditLogs);
    }

    /**
     * Display the specified audit log.
     *
     * @param  \App\Models\AuditLog  $auditLog
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(AuditLog $auditLog)
    {
        $this->authorize('view', $auditLog);

        // Load relationships
        $auditLog->load(['user', 'auditable']);

        return new AuditLogResource($auditLog);
    }

    /**
     * Get audit logs for a specific document.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function forDocument(Request $request)
    {
        $this->authorize('viewAny', AuditLog::class);

        $request->validate([
            'auditable_type' => 'required|string',
            'auditable_id' => 'required|integer',
        ]);

        $auditLogs = AuditLog::with(['user', 'auditable'])
            ->whereCompany()
            ->where('auditable_type', $request->auditable_type)
            ->where('auditable_id', $request->auditable_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return AuditLogResource::collection($auditLogs);
    }

    /**
     * Get audit logs for a specific user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function forUser(Request $request)
    {
        $this->authorize('viewAny', AuditLog::class);

        $request->validate([
            'user_id' => 'required|integer',
        ]);

        $auditLogs = AuditLog::with(['user', 'auditable'])
            ->whereCompany()
            ->byUser($request->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return AuditLogResource::collection($auditLogs);
    }
}

// CLAUDE-CHECKPOINT
