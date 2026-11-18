<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Categories supported for Seller and general user preferences.
     */
    protected array $sellerCategories = [
        'orders' => 'Orders & Fulfilment',
        'shipments' => 'Shipments & Tracking',
        'returns_claims' => 'Returns & Disputes',
        'catalogue' => 'Catalog Moderation',
        'inventory' => 'Inventory & Low Stock',
        'settlements' => 'Disbursements & Payouts',
        'support' => 'Support Desk',
        'announcements' => 'Platform Notices',
        'promotions' => 'Campaigns & Deals',
    ];

    /**
     * Display in-app notifications and preferences portal.
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

        $totalCount = $user->notifications()->count();
        $unreadCount = $user->unreadNotifications()->count();
        $readCount = max(0, $totalCount - $unreadCount);
        $todayCount = $user->notifications()->whereDate('created_at', today())->count();

        // Load or initialize user preferences across categories
        $activeCategories = $this->sellerCategories;
        $preferences = [];

        foreach (array_keys($activeCategories) as $cat) {
            $preferences[$cat] = UserNotificationPreference::firstOrCreate(
                ['user_id' => $user->id, 'category' => $cat],
                [
                    'channel_database' => true,
                    'channel_mail' => true,
                    'channel_sms' => ($cat !== 'promotions'),
                    'channel_whatsapp' => ($cat !== 'promotions'),
                    'channel_push' => true,
                    'promotional_consent' => false,
                ]
            );
        }

        return view('notifications.index', [
            'user' => $user,
            'notifications' => $notifications,
            'totalCount' => $totalCount,
            'unreadCount' => $unreadCount,
            'readCount' => $readCount,
            'todayCount' => $todayCount,
            'currentFilter' => $filter,
            'currentCategory' => $category,
            'search' => $search,
            'eventCategories' => $activeCategories,
            'categories' => $activeCategories,
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

        $fallbackRoute = request()->is('seller*') ? route('seller.notifications.index') : route('notifications.index');
        $actionUrl = $notification->data['action_url'] ?? $fallbackRoute;

        return redirect()->to($actionUrl);
    }

    /**
     * Update notification channel preferences and marketing consent.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_if(!$user, 401);

        $data = $request->input('preferences', []);
        $promotionalConsent = $request->boolean('promotional_consent');

        foreach (array_keys($this->sellerCategories) as $cat) {
            $catData = $data[$cat] ?? [];

            $isPromo = ($cat === 'promotions');
            $consent = $isPromo ? $promotionalConsent : false;

            UserNotificationPreference::updateOrCreate(
                ['user_id' => $user->id, 'category' => $cat],
                [
                    'channel_database' => !empty($catData['database']),
                    'channel_mail' => !empty($catData['mail']),
                    'channel_sms' => !empty($catData['sms']),
                    'channel_whatsapp' => !empty($catData['whatsapp']),
                    'channel_push' => !empty($catData['push']),
                    'promotional_consent' => $consent,
                    'promotional_consent_at' => $consent ? now() : null,
                ]
            );
        }

        return back()->with('success', 'Notification preferences updated successfully.');
    }

    /**
     * Mark an individual database notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse|RedirectResponse
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
     * Mark all database notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
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
     * Clear all notifications for user.
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
     * API: Get unread count and latest 5 for live bell badge.
     */
    public function unreadCountApi(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['count' => 0, 'notifications' => []]);
        }

        $unreadCount = $user->unreadNotifications()->count();
        $latest = $user->notifications()->latest()->limit(5)->get()->map(fn ($n) => [
            'id' => $n->id,
            'title' => $n->data['title'] ?? 'Notification',
            'message' => $n->data['message'] ?? '',
            'read' => $n->read_at !== null,
            'time_ago' => $n->created_at->diffForHumans(),
            'action_url' => $n->data['action_url'] ?? null,
        ]);

        return response()->json([
            'count' => $unreadCount,
            'notifications' => $latest,
        ]);
    }
}
