@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Support Topics & FAQ Configuration - ALLZONE Admin'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16" x-data="{
    newCategoryModal: false,
    editCategoryModal: false,
    activeEditCategory: null,
    newQuestionModal: false,
    activeCategoryForQuestion: null,
    editQuestionModal: false,
    activeEditQuestion: null,
}">
    <!-- Page Header -->
    <x-page-header 
        title="Support Categories & FAQ Management" 
        subtitle="Configure seller issue topics, common questions, and dynamic order/product requirements."
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'Support Desk' => route('admin.support.index'), 'Categories' => '#']"
    >
        <x-slot name="badge">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                {{ $categories->count() }} Categories Configured
            </span>
        </x-slot>

        <x-slot name="actions">
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.support.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition">
                    &larr; Back to Tickets
                </a>
                <button type="button" @click="newCategoryModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Add New Category</span>
                </button>
            </div>
        </x-slot>
    </x-page-header>

    <!-- Categories Accordion / Cards List -->
    <div class="space-y-4">
        @forelse($categories as $category)
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4" x-data="{ open: true }">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            #{{ $category->sort_order }}
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ $category->name }}
                                </h3>
                                @if($category->is_active)
                                    <span class="px-2 py-0.2 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Active</span>
                                @else
                                    <span class="px-2 py-0.2 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">Disabled</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $category->description }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="activeCategoryForQuestion = {{ $category->id }}; newQuestionModal = true" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-lg transition flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Add Question</span>
                        </button>

                        <button type="button" @click="activeEditCategory = {{ Js::from($category) }}; editCategoryModal = true" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition">
                            Edit
                        </button>

                        <form action="{{ route('admin.support.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Delete category and all its questions?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-2.5 py-1.5 text-xs font-semibold rounded-lg text-rose-600 hover:bg-rose-50 transition">
                                Delete
                            </button>
                        </form>

                        <button type="button" @click="open = !open" class="text-slate-400 hover:text-slate-700 p-1">
                            <svg class="w-5 h-5 transition transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Questions List -->
                <div x-show="open" class="space-y-2 pt-1">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        Subcategory Questions ({{ $category->questions->count() }})
                    </div>

                    @forelse($category->questions as $question)
                        <div class="p-3 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                            <div class="space-y-1">
                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>{{ $question->question }}</span>
                                    @if($question->requires_order)
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">Requires Order</span>
                                    @endif
                                    @if($question->requires_product)
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Requires Product</span>
                                    @endif
                                    @if($question->requires_shipment)
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200">Requires AWB</span>
                                    @endif
                                </div>
                                @if($question->short_answer)
                                    <p class="text-[11px] text-slate-500 italic line-clamp-1">{{ $question->short_answer }}</p>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" @click="activeEditQuestion = {{ Js::from($question) }}; editQuestionModal = true" class="px-2 py-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded text-[11px] font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 transition">
                                    Edit
                                </button>
                                <form action="{{ route('admin.support.categories.questions.destroy', $question) }}" method="POST" onsubmit="return confirm('Delete this question?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-[11px] font-semibold text-rose-600 hover:bg-rose-50 rounded transition">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-xs text-slate-400">
                            No questions configured for this category yet. Click "Add Question" above.
                        </div>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
                No support categories found.
            </div>
        @endforelse
    </div>

    <!-- Modal: Add Category -->
    <div x-show="newCategoryModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4" @click.outside="newCategoryModal = false">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b pb-2">
                Create Support Category
            </h3>
            <form action="{{ route('admin.support.categories.store') }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Category Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Account & Banking" class="w-full rounded-lg border-slate-200 p-2 text-xs">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="2" placeholder="Brief summary of topics covered..." class="w-full rounded-lg border-slate-200 p-2 text-xs"></textarea>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="1" min="0" class="w-full rounded-lg border-slate-200 p-2 text-xs">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t">
                    <button type="button" @click="newCategoryModal = false" class="px-3 py-1.5 border rounded-lg text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-indigo-600 text-white font-bold rounded-lg">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Add Question -->
    <div x-show="newQuestionModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4" @click.outside="newQuestionModal = false">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b pb-2">
                Add Subcategory Question
            </h3>
            <form :action="'/admin/support/categories/' + activeCategoryForQuestion + '/questions'" method="POST" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Question / Topic *</label>
                    <input type="text" name="question" required placeholder="e.g. Courier missed pickup scheduled for today" class="w-full rounded-lg border-slate-200 p-2 text-xs">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Instant Guidance / Short Answer (Optional)</label>
                    <textarea name="short_answer" rows="3" placeholder="Helpful guidance shown to the seller before ticket submission..." class="w-full rounded-lg border-slate-200 p-2 text-xs"></textarea>
                </div>
                <div class="space-y-1.5 pt-1">
                    <label class="block font-bold text-slate-700 mb-1">Dynamic Field Requirements</label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="requires_order" value="1" class="rounded text-indigo-600">
                        <span>Requires associated Order selection</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="requires_product" value="1" class="rounded text-indigo-600">
                        <span>Requires associated Product selection</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="requires_shipment" value="1" class="rounded text-indigo-600">
                        <span>Requires AWB / Courier tracking number</span>
                    </label>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t">
                    <button type="button" @click="newQuestionModal = false" class="px-3 py-1.5 border rounded-lg text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-indigo-600 text-white font-bold rounded-lg">Save Question</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
