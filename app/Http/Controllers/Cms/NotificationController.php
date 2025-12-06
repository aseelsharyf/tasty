<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\CmsNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index(Request $request): Response
    {
        $query = CmsNotification::forUser(auth()->id())
            ->with('triggeredByUser:id,name,avatar_url')
            ->latest();

        // Filter by type
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Filter by read status
        if ($request->filter === 'unread') {
            $query->unread();
        } elseif ($request->filter === 'read') {
            $query->read();
        }

        $notifications = $query->paginate(20);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'filters' => [
                'type' => $request->type,
                'filter' => $request->filter,
            ],
            'unreadCount' => CmsNotification::forUser(auth()->id())->unread()->count(),
        ]);
    }

    /**
     * Get recent notifications for dropdown.
     */
    public function recent(): JsonResponse
    {
        $notifications = CmsNotification::forUser(auth()->id())
            ->with('triggeredByUser:id,name,avatar_url')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($n) => $this->formatNotification($n));

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => CmsNotification::forUser(auth()->id())->unread()->count(),
        ]);
    }

    /**
     * Get unread count for badge.
     */
    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'count' => CmsNotification::forUser(auth()->id())->unread()->count(),
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(CmsNotification $notification): JsonResponse
    {
        // Ensure user owns this notification
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => CmsNotification::forUser(auth()->id())->unread()->count(),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        CmsNotification::forUser(auth()->id())
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(CmsNotification $notification): JsonResponse
    {
        // Ensure user owns this notification
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'unread_count' => CmsNotification::forUser(auth()->id())->unread()->count(),
        ]);
    }

    /**
     * Delete all read notifications.
     */
    public function destroyRead(): JsonResponse
    {
        CmsNotification::forUser(auth()->id())
            ->read()
            ->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Format a notification for JSON response.
     */
    protected function formatNotification(CmsNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'uuid' => $notification->uuid,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'icon' => $notification->icon,
            'color' => $notification->color,
            'action_url' => $notification->action_url,
            'action_label' => $notification->action_label,
            'is_read' => $notification->isRead(),
            'triggered_by' => $notification->triggeredByUser ? [
                'id' => $notification->triggeredByUser->id,
                'name' => $notification->triggeredByUser->name,
                'avatar_url' => $notification->triggeredByUser->avatar_url,
            ] : null,
            'created_at' => $notification->created_at->toISOString(),
            'time_ago' => $notification->created_at->diffForHumans(),
        ];
    }
}
