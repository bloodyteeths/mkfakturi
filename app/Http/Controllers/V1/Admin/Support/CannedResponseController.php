<?php

namespace App\Http\Controllers\V1\Admin\Support;

use App\Http\Controllers\Controller;
use App\Models\CannedResponse;
use Illuminate\Http\Request;

/**
 * CannedResponseController
 *
 * CRUD operations for canned responses (quick reply templates)
 */
class CannedResponseController extends Controller
{
    /**
     * List all canned responses
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Only admins and support agents can access canned responses
        if (!$user->isOwner() && !$user->hasRole('support')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Only admins and support agents can access canned responses'
            ], 403);
        }

        $responses = CannedResponse::query()
            ->with('creator')
            ->when($request->category, function ($query, $category) {
                $query->where('category', $category);
            })
            ->when($request->active_only, function ($query) {
                $query->active();
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('content', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('usage_count', 'desc') // Most used first
            ->orderBy('title', 'asc')
            ->get();

        return response()->json([
            'data' => $responses,
            'total' => $responses->count(),
        ]);
    }

    /**
     * Create new canned response
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->isOwner() && !$user->hasRole('support')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Only admins and support agents can create canned responses'
            ], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $response = CannedResponse::create([
            'title' => $request->title,
            'content' => $request->content,
            'category' => $request->category,
            'is_active' => $request->is_active ?? true,
            'created_by' => $user->id,
        ]);

        $response->load('creator');

        return response()->json([
            'success' => true,
            'message' => 'Canned response created successfully',
            'data' => $response,
        ], 201);
    }

    /**
     * Show single canned response
     *
     * @param CannedResponse $cannedResponse
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(CannedResponse $cannedResponse)
    {
        $cannedResponse->load('creator');

        return response()->json([
            'data' => $cannedResponse,
        ]);
    }

    /**
     * Update canned response
     *
     * @param Request $request
     * @param CannedResponse $cannedResponse
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, CannedResponse $cannedResponse)
    {
        $user = $request->user();

        if (!$user->isOwner() && !$user->hasRole('support')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Only admins and support agents can update canned responses'
            ], 403);
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'category' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $cannedResponse->update($request->only([
            'title',
            'content',
            'category',
            'is_active',
        ]));

        $cannedResponse->load('creator');

        return response()->json([
            'success' => true,
            'message' => 'Canned response updated successfully',
            'data' => $cannedResponse,
        ]);
    }

    /**
     * Delete canned response
     *
     * @param CannedResponse $cannedResponse
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(CannedResponse $cannedResponse)
    {
        $cannedResponse->delete();

        return response()->json([
            'success' => true,
            'message' => 'Canned response deleted successfully',
        ]);
    }

    /**
     * Use canned response (increment usage count)
     *
     * @param CannedResponse $cannedResponse
     * @return \Illuminate\Http\JsonResponse
     */
    public function use(CannedResponse $cannedResponse)
    {
        $cannedResponse->incrementUsage();

        return response()->json([
            'success' => true,
            'data' => $cannedResponse,
        ]);
    }
}
// CLAUDE-CHECKPOINT
