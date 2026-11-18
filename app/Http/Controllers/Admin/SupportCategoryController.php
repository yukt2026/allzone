<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportCategory;
use App\Models\SupportQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SupportCategoryController extends Controller
{
    /**
     * Display categories and FAQ questions management view.
     */
    public function index(): View
    {
        $categories = SupportCategory::query()
            ->with(['questions'])
            ->orderBy('sort_order')
            ->get();

        return view('admin.support.categories.index', compact('categories'));
    }

    /**
     * Store new support category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $slug = Str::slug($validated['name']);
        $counter = 1;
        while (SupportCategory::where('slug', $slug)->exists()) {
            $slug = Str::slug($validated['name']) . '-' . $counter;
            $counter++;
        }

        SupportCategory::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'icon' => ($validated['icon'] ?? null) ?: 'help-circle',
            'description' => $validated['description'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => true,
        ]);

        return back()->with('success', 'Support category created successfully.');
    }

    /**
     * Update existing support category.
     */
    public function update(Request $request, SupportCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'icon' => ($validated['icon'] ?? null) ?: $category->icon,
            'description' => $validated['description'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Support category updated.');
    }

    /**
     * Delete support category.
     */
    public function destroy(SupportCategory $category): RedirectResponse
    {
        $category->delete();

        return back()->with('success', 'Support category deleted.');
    }

    /**
     * Add question to category.
     */
    public function storeQuestion(Request $request, SupportCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'short_answer' => ['nullable', 'string', 'max:1000'],
            'requires_order' => ['nullable', 'boolean'],
            'requires_product' => ['nullable', 'boolean'],
            'requires_shipment' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $slug = Str::slug($validated['question']);

        SupportQuestion::create([
            'support_category_id' => $category->id,
            'question' => $validated['question'],
            'slug' => $slug,
            'short_answer' => $validated['short_answer'] ?? null,
            'requires_order' => $request->boolean('requires_order'),
            'requires_product' => $request->boolean('requires_product'),
            'requires_shipment' => $request->boolean('requires_shipment'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => true,
        ]);

        return back()->with('success', 'Subcategory question added successfully.');
    }

    /**
     * Update question.
     */
    public function updateQuestion(Request $request, SupportQuestion $question): RedirectResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'short_answer' => ['nullable', 'string', 'max:1000'],
            'requires_order' => ['nullable', 'boolean'],
            'requires_product' => ['nullable', 'boolean'],
            'requires_shipment' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $question->update([
            'question' => $validated['question'],
            'short_answer' => $validated['short_answer'] ?? null,
            'requires_order' => $request->boolean('requires_order'),
            'requires_product' => $request->boolean('requires_product'),
            'requires_shipment' => $request->boolean('requires_shipment'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Question updated.');
    }

    /**
     * Delete question.
     */
    public function destroyQuestion(SupportQuestion $question): RedirectResponse
    {
        $question->delete();

        return back()->with('success', 'Question deleted.');
    }
}
