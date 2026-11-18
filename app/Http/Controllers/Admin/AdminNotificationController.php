<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    /**
     * Categories managed for Admin notifications.
     */
    protected array $categories = [
        'orders' => 'Orders & Fulfilment',
        'sellers' => 'Merchants & Onboarding',
        'catalogue' => 'Catalog & Moderation',
        'finance' => 'Payments, Refunds & Payouts',
        'logistics' => 'Logistics & Cargo',
        'support' => 'Support Desk & Escalations',
        'security' => 'Security, Audits & Incidents',
    ];

    /**
     * Display centralized Admin notifications and alert preferences.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        abort_if(!$user, 401);

        $filter = $request->input('filter', 'all');
        $category = $request->input('category');
        $search = $request->input('search');

        $query = $user->notifications();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        if (!empty($category)) {
            $query->where(function ($q) use ($category) {
                $q->where('data->category', $category)
                  ->orWhere('data->event_key', 'like', "%{$category}%");
            });
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('data->title', 'like', "%{$search}%")
                  ->orWhere('data->message', 'like', "%{$search}%");
            });
        }

        $notifications = $query->latest()->paginate(15)->withQueryString();

        // Metrics strip
        $totalCount = $user->notifications()->count();
        $unreadCount = $user->unreadNotifications()->count();
        $readCount = max(0, $totalCount - $unreadCount);
        $todayCount = $user->notifications()->whereDate('created_at', today())->count();

        // Admin notification channel preferences
        $preferences = [];
        foreach (array_keys($this->categories) as $cat) {
            $preferences[$cat] = UserNotificationPreference::firstOrCreate(
                ['user_id' => $user->id, 'category' => $cat],
                [
                    'channel_database' => true,
                    'channel_mail' => true,
                    'channel_sms' => false,
                    'channel_whatsapp' => false,
                    'channel_push' => true,
                    'promotional_consent' => false,
                ]
            );
        }

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'totalCount' => $totalCount,
            'unreadCount' => $unreadCount,
            'readCount' => $readCount,
            'todayCount' => $todayCount,
            'currentFilter' => $filter,
            'currentCategory' => $category,
            'search' => $search,
            'categories' => $this->categories,
            'preferences' => $preferences,
        ]);
    }

    /**
     * Mark notification as read and redirect directly to its target action URL.
     */
    public function goToUrl(Request $request, string $id): RedirectResponse
    {
        $user = Auth::user();
        abort_if(!$user, 401);

        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        $actionUrl = $notification->data['action_url'] ?? route('admin.notifications.index');
        return redirect()->to($actionUrl);
    }

    /**
     * Mark an individual notification as read.
     */
    public function markRead(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        abort_if(!$user, 401);

        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        abort_if(!$user, 401);

        $user->unreadNotifications()->update(['read_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Clear all notifications for the current user.
     */
    public function clearAll(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        abort_if(!$user, 401);

        $user->notifications()->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications cleared.');
    }

    /**
     * Update Admin notification preferences across categories and delivery channels.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_if(!$user, 401);

        $data = $request->input('preferences', []);

        foreach (array_keys($this->categories) as $cat) {
            $catData = $data[$cat] ?? [];

            UserNotificationPreference::updateOrCreate(
                ['user_id' => $user->id, 'category' => $cat],
                [
                    'channel_database' => !empty($catData['database']),
                    'channel_mail' => !empty($catData['mail']),
                    'channel_sms' => !empty($catData['sms']),
                    'channel_whatsapp' => !empty($catData['whatsapp']),
                    'channel_push' => !empty($catData['push']),
                ]
            );
        }

        return back()->with('success', 'Notification preferences updated successfully.');
    }
}
