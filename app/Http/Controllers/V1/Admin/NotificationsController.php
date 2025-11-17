<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * NotificationsController
 *
 * Handles user notification operations including fetching, marking as read, and clearing notifications.
 *
 * @package App\Http\Controllers\V1\Admin
 */
class NotificationsController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(50) // Limit to last 50 notifications
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $this->extractNotificationType($notification->type),
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];
            });

        return response()->json([
            'data' => $notifications,
        ]);
    }

    /**
     * Mark a specific notification as read
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'error' => 'Notification not found',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Delete a specific notification
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'error' => 'Notification not found',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted',
        ]);
    }

    /**
     * Clear all notifications for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->notifications()->delete();

        return response()->json([
            'success' => true,
            'message' => 'All notifications cleared',
        ]);
    }

    /**
     * Get unread notification count
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = $user->unreadNotifications()->count();

        return response()->json([
            'count' => $count,
        ]);
    }

    /**
     * Extract notification type from full class name
     *
     * @param string $fullType
     * @return string
     */
    private function extractNotificationType(string $fullType): string
    {
        // Extract the notification class name and convert to snake_case
        $className = class_basename($fullType);

        // Remove "Notification" suffix if present
        $className = str_replace('Notification', '', $className);

        // Convert to snake_case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }
}
// CLAUDE-CHECKPOINT
