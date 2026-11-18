<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NoticeController extends Controller
{
    /**
     * Display seller notices and platform announcements.
     */
    public function index(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        $category = $request->query('category', 'all');
        $status = $request->query('status', 'all');
        $search = $request->query('search');

        $baseQuery = Announcement::query()
            ->published()
            ->forSeller($seller)
            ->with(['reads' => fn($q) => $q->where('seller_id', $seller->id)]);

        // Calculate counts
        $totalCount = (clone $baseQuery)->count();
        $importantCount = (clone $baseQuery)->where('category', 'important')->count();
        $generalCount = (clone $baseQuery)->where('category', 'general')->count();
        $unreadCount = (clone $baseQuery)->whereDoesntHave('reads', fn($q) => $q->where('seller_id', $seller->id))->count();

        // Apply filters
        if ($category && $category !== 'all') {
            $baseQuery->where('category', $category);
        }

        if ($status === 'unread') {
            $baseQuery->whereDoesntHave('reads', fn($q) => $q->where('seller_id', $seller->id));
        } elseif ($status === 'read') {
            $baseQuery->whereHas('reads', fn($q) => $q->where('seller_id', $seller->id));
        }

        if ($search) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $notices = $baseQuery->latest('published_at')->paginate(10)->withQueryString();
        $store = Store::where('seller_id', $seller->id)->first();

        return view('seller.notices.index', compact(
            'seller',
            'store',
            'notices',
            'category',
            'status',
            'search',
            'totalCount',
            'importantCount',
            'generalCount',
            'unreadCount'
        ));
    }

    /**
     * Display a specific announcement detail page.
     */
    public function show(Announcement $announcement): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        // Security check: ensure announcement is published and targeted to this seller
        $isEligible = Announcement::query()
            ->published()
            ->forSeller($seller)
            ->where('id', $announcement->id)
            ->exists();

        abort_if(!$isEligible, 404, 'Announcement not found or unavailable.');

        // Automatically mark as read
        $announcement->markAsReadFor($seller);

        return view('seller.notices.show', [
            'seller' => $seller,
            'notice' => $announcement,
        ]);
    }

    /**
     * Mark a single announcement as read.
     */
    public function markAsRead(Announcement $announcement): RedirectResponse|JsonResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        $announcement->markAsReadFor($seller);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Notice marked as read.']);
        }

        return back()->with('success', 'Announcement marked as read.');
    }

    /**
     * Mark all eligible notices as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        $eligibleAnnouncements = Announcement::query()
            ->published()
            ->forSeller($seller)
            ->get();

        foreach ($eligibleAnnouncements as $announcement) {
            $announcement->markAsReadFor($seller);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'All notices marked as read.']);
        }

        return back()->with('success', 'All announcements marked as read.');
    }

    /**
     * Download attached policy document or flyer.
     */
    public function downloadAttachment(Announcement $announcement): BinaryFileResponse|StreamedResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        $isEligible = Announcement::query()
            ->published()
            ->forSeller($seller)
            ->where('id', $announcement->id)
            ->exists();

        abort_if(!$isEligible, 404);
        abort_if(empty($announcement->attachment_path), 404, 'No attachment associated with this announcement.');

        if (!Storage::disk('public')->exists($announcement->attachment_path)) {
            abort(404, 'Attached document file not found on disk.');
        }

        $downloadName = $announcement->attachment_name ?: basename($announcement->attachment_path);

        return Storage::disk('public')->download($announcement->attachment_path, $downloadName);
    }

    /**
     * Submit leave or store holiday request.
     */
    public function submitLeaveRequest(Request $request): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        $validated = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $store = Store::where('seller_id', $seller->id)->first();
        if ($store) {
            $store->status = \App\Enums\StoreStatus::INACTIVE;
            $store->save();
        }

        return back()->with('success', "Leave request from {$validated['start_date']} to {$validated['end_date']} registered. Store status set to offline during this period.");
    }
}
