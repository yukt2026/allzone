<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Seller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    /**
     * Display a paginated listing of announcements.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $category = $request->query('category', 'all');
        $priority = $request->query('priority', 'all');
        $search = $request->query('search');

        $query = Announcement::query()->with(['author', 'reads']);

        if ($status === 'published') {
            $query->published();
        } elseif ($status === 'draft') {
            $query->where('is_published', false);
        } elseif ($status === 'scheduled') {
            $query->where('is_published', true)->where('published_at', '>', now());
        } elseif ($status === 'expired') {
            $query->whereNotNull('expires_at')->where('expires_at', '<', now());
        }

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        if ($priority && $priority !== 'all') {
            $query->where('priority', $priority);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $announcements = $query->latest('created_at')->paginate(15)->withQueryString();

        $metrics = [
            'total' => Announcement::count(),
            'published' => Announcement::published()->count(),
            'drafts' => Announcement::where('is_published', false)->count(),
            'scheduled' => Announcement::where('is_published', true)->where('published_at', '>', now())->count(),
            'expired' => Announcement::whereNotNull('expires_at')->where('expires_at', '<', now())->count(),
        ];

        $totalApprovedSellers = Seller::where('status', SellerStatus::APPROVED)->count();

        return view('admin.announcements.index', compact(
            'announcements',
            'metrics',
            'status',
            'category',
            'priority',
            'search',
            'totalApprovedSellers'
        ));
    }

    /**
     * Show the form for creating a new announcement.
     */
    public function create(): View
    {
        $sellerTypes = SellerType::cases();
        $sellers = Seller::query()
            ->select(['id', 'business_name', 'seller_type'])
            ->where('status', SellerStatus::APPROVED)
            ->orderBy('business_name')
            ->get();

        return view('admin.announcements.create', compact('sellerTypes', 'sellers'));
    }

    /**
     * Store a newly created announcement in database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:important,general,policy,logistics,feature'],
            'priority' => ['required', 'string', 'in:low,normal,high,urgent'],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'target_type' => ['required', 'string', 'in:all,seller_group,specific_sellers,customers'],
            'target_seller_type' => ['nullable', 'required_if:target_type,seller_group', 'string'],
            'target_seller_ids' => ['nullable', 'array'],
            'target_seller_ids.*' => ['integer', 'exists:sellers,id'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:published_at'],
            'is_published' => ['nullable', 'boolean'],
            'banner_image' => ['nullable', 'image', 'max:4096'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,zip,png,jpg', 'max:10240'],
        ]);

        $slugBase = Str::slug($validated['title']);
        $slug = $slugBase;
        $counter = 1;
        while (Announcement::where('slug', $slug)->exists()) {
            $slug = "{$slugBase}-{$counter}";
            $counter++;
        }

        $isPublished = $request->boolean('is_published', true);
        $publishedAt = !empty($validated['published_at'])
            ? $validated['published_at']
            : ($isPublished ? now() : null);

        $bannerPath = null;
        if ($request->hasFile('banner_image')) {
            $bannerPath = $request->file('banner_image')->store('announcements/banners', 'public');
        }

        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store('announcements/attachments', 'public');
        }

        Announcement::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'summary' => $validated['summary'] ?? Str::limit(strip_tags($validated['content']), 160),
            'content' => $validated['content'],
            'banner_image' => $bannerPath,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'target_type' => $validated['target_type'],
            'target_seller_type' => $validated['target_type'] === 'seller_group' ? ($validated['target_seller_type'] ?? null) : null,
            'target_seller_ids' => $validated['target_type'] === 'specific_sellers' ? ($validated['target_seller_ids'] ?? []) : null,
            'published_at' => $publishedAt,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_published' => $isPublished,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement published and distributed successfully.');
    }

    /**
     * Show the form for editing an announcement.
     */
    public function edit(Announcement $announcement): View
    {
        $sellerTypes = SellerType::cases();
        $sellers = Seller::query()
            ->select(['id', 'business_name', 'seller_type'])
            ->where('status', SellerStatus::APPROVED)
            ->orderBy('business_name')
            ->get();

        return view('admin.announcements.edit', compact('announcement', 'sellerTypes', 'sellers'));
    }

    /**
     * Update the specified announcement.
     */
    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:important,general,policy,logistics,feature'],
            'priority' => ['required', 'string', 'in:low,normal,high,urgent'],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'target_type' => ['required', 'string', 'in:all,seller_group,specific_sellers,customers'],
            'target_seller_type' => ['nullable', 'required_if:target_type,seller_group', 'string'],
            'target_seller_ids' => ['nullable', 'array'],
            'target_seller_ids.*' => ['integer', 'exists:sellers,id'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
            'banner_image' => ['nullable', 'image', 'max:4096'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,zip,png,jpg', 'max:10240'],
            'remove_banner' => ['nullable', 'boolean'],
            'remove_attachment' => ['nullable', 'boolean'],
        ]);

        // Banner handling
        $bannerPath = $announcement->banner_image;
        if ($request->boolean('remove_banner') && $bannerPath) {
            Storage::disk('public')->delete($bannerPath);
            $bannerPath = null;
        }
        if ($request->hasFile('banner_image')) {
            if ($bannerPath) {
                Storage::disk('public')->delete($bannerPath);
            }
            $bannerPath = $request->file('banner_image')->store('announcements/banners', 'public');
        }

        // Attachment handling
        $attachmentPath = $announcement->attachment_path;
        $attachmentName = $announcement->attachment_name;
        if ($request->boolean('remove_attachment') && $attachmentPath) {
            Storage::disk('public')->delete($attachmentPath);
            $attachmentPath = null;
            $attachmentName = null;
        }
        if ($request->hasFile('attachment')) {
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store('announcements/attachments', 'public');
        }

        $isPublished = $request->boolean('is_published', false);
        $publishedAt = !empty($validated['published_at'])
            ? $validated['published_at']
            : ($isPublished && !$announcement->published_at ? now() : $announcement->published_at);

        $announcement->update([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'summary' => $validated['summary'] ?? Str::limit(strip_tags($validated['content']), 160),
            'content' => $validated['content'],
            'banner_image' => $bannerPath,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'target_type' => $validated['target_type'],
            'target_seller_type' => $validated['target_type'] === 'seller_group' ? ($validated['target_seller_type'] ?? null) : null,
            'target_seller_ids' => $validated['target_type'] === 'specific_sellers' ? ($validated['target_seller_ids'] ?? []) : null,
            'published_at' => $publishedAt,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_published' => $isPublished,
        ]);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    /**
     * Delete an announcement.
     */
    public function destroy(Announcement $announcement): RedirectResponse
    {
        if ($announcement->banner_image) {
            Storage::disk('public')->delete($announcement->banner_image);
        }
        if ($announcement->attachment_path) {
            Storage::disk('public')->delete($announcement->attachment_path);
        }

        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement removed permanently.');
    }

    /**
     * Fast toggle publication status.
     */
    public function togglePublish(Announcement $announcement): RedirectResponse|JsonResponse
    {
        $newStatus = !$announcement->is_published;
        $announcement->is_published = $newStatus;
        if ($newStatus && !$announcement->published_at) {
            $announcement->published_at = now();
        }
        $announcement->save();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_published' => $newStatus,
                'message' => $newStatus ? 'Announcement published.' : 'Announcement unpublished (moved to draft).',
            ]);
        }

        return back()->with('success', $newStatus ? 'Announcement published.' : 'Announcement moved to drafts.');
    }

    /**
     * Expire / archive an announcement immediately.
     */
    public function archive(Announcement $announcement): RedirectResponse
    {
        $announcement->update([
            'expires_at' => now(),
            'is_published' => false,
        ]);

        if (function_exists('activity')) {
            activity('announcement')
                ->performedOn($announcement)
                ->causedBy(Auth::user())
                ->log("Announcement '{$announcement->title}' was expired/archived");
        }

        return back()->with('success', "Notice '{$announcement->title}' archived.");
    }

    /**
     * View detailed read breakdown for an announcement.
     */
    public function reads(Announcement $announcement): JsonResponse|View
    {
        $announcement->load(['reads.seller.user']);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'reads_count' => $announcement->reads->count(),
                'reads' => $announcement->reads->map(fn($r) => [
                    'seller_id' => $r->seller_id,
                    'seller_name' => $r->seller?->business_name ?? 'N/A',
                    'read_at' => $r->read_at?->format('d M Y, h:i A') ?? $r->created_at?->format('d M Y, h:i A'),
                ]),
            ]);
        }

        return view('admin.announcements.reads', compact('announcement'));
    }
}
