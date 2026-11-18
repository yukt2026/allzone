@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Catalog Standards & Governance'])

@section('content')
<div class="space-y-6" x-data="{ activeTab: '{{ $currentTab }}' }">
    <x-page-header 
        title="Catalog Standards & Platform Specifications" 
        subtitle="Manage measurement units, GST tax slabs, HSN compliance, standard specification templates, and media quality rules for all marketplace products."
        :breadcrumbs="['Command Central' => route('admin.dashboard'), 'Catalogue' => route('admin.catalogue.moderation.index'), 'Catalog Standards' => '#']"
    >
        <x-slot name="actions">
            <a 
                href="{{ route('admin.catalogue.moderation.index') }}" 
                class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 transition flex items-center"
            >
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Moderation Queue
            </a>
        </x-slot>
    </x-page-header>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200 dark:border-slate-800">
        <nav class="flex space-x-2 sm:space-x-4 overflow-x-auto" aria-label="Catalog Standards Tabs">
            <button 
                type="button" 
                @click="activeTab = 'units'" 
                :class="activeTab === 'units' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium'"
                class="py-3 px-3 text-xs border-b-2 transition whitespace-nowrap flex items-center space-x-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 18h12l3-18H3z"/></svg>
                <span>Units of Measure ({{ count($units) }})</span>
            </button>

            <button 
                type="button" 
                @click="activeTab = 'taxes'" 
                :class="activeTab === 'taxes' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium'"
                class="py-3 px-3 text-xs border-b-2 transition whitespace-nowrap flex items-center space-x-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                <span>Tax Configuration & GST Slabs</span>
            </button>

            <button 
                type="button" 
                @click="activeTab = 'specifications'" 
                :class="activeTab === 'specifications' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium'"
                class="py-3 px-3 text-xs border-b-2 transition whitespace-nowrap flex items-center space-x-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Specification Standards</span>
            </button>

            <button 
                type="button" 
                @click="activeTab = 'images'" 
                :class="activeTab === 'images' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium'"
                class="py-3 px-3 text-xs border-b-2 transition whitespace-nowrap flex items-center space-x-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Image Requirements & QC</span>
            </button>
        </nav>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: UNITS OF MEASURE -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'units'" class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Measurement Units Directory</h3>
                <p class="text-xs text-slate-500">Authorized measurement units available for sellers when configuring catalog variants and pack sizes.</p>
            </div>
            <button 
                type="button" 
                @click="$dispatch('open-modal', 'add-unit-modal')"
                class="px-3.5 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition flex items-center space-x-1.5 self-start"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Unit of Measure</span>
            </button>
        </div>

        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/40 text-slate-400 font-semibold uppercase">
                            <th class="py-3 px-4">Code</th>
                            <th class="py-3 px-4">Unit Name</th>
                            <th class="py-3 px-4 text-center">Symbol</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($units as $u)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 dark:text-white">{{ $u['code'] }}</td>
                                <td class="py-3 px-4 text-slate-700 dark:text-slate-300 font-medium">{{ $u['name'] }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ $u['symbol'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-500">{{ $u['category'] }}</td>
                                <td class="py-3 px-4 text-center">
                                    @if(!empty($u['is_active']))
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Active
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                            Disabled
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <form method="POST" action="{{ route('admin.catalogue.standards.units.destroy', $u['code']) }}" class="inline" onsubmit="return confirm('Remove unit {{ $u['name'] }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:text-rose-800 text-xs font-semibold">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: TAX CONFIGURATION & GST SLABS -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'taxes'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: GST Slabs Reference Table -->
            <div class="lg:col-span-2 space-y-4">
                <x-card title="India GST Tax Slabs (Statutory Reference)" subtitle="Standard statutory tax slabs applicable to marketplace categories and HSN codes">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/40 text-slate-400 font-semibold uppercase">
                                    <th class="py-2.5 px-3">Slab Rate</th>
                                    <th class="py-2.5 px-3">Classification</th>
                                    <th class="py-2.5 px-3">Applicable Goods Description</th>
                                    <th class="py-2.5 px-3">Example HSN</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($taxSlabs as $slab)
                                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50">
                                        <td class="py-3 px-3 font-mono font-extrabold text-emerald-600 dark:text-emerald-400 text-sm">
                                            {{ number_format((float) $slab['rate'], 1) }}%
                                        </td>
                                        <td class="py-3 px-3 font-bold text-slate-800 dark:text-slate-200">{{ $slab['name'] }}</td>
                                        <td class="py-3 px-3 text-slate-600 dark:text-slate-300 leading-relaxed">{{ $slab['description'] }}</td>
                                        <td class="py-3 px-3 font-mono text-[11px] text-slate-500">{{ $slab['hsn_example'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <!-- Tax Guidance Box -->
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 space-y-2">
                    <h4 class="font-bold text-slate-900 dark:text-white flex items-center space-x-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Marketplace Tax Settlement Logic</span>
                    </h4>
                    <p class="text-[11px] leading-relaxed text-slate-500">
                        For intra-state transactions (merchant and customer in the same state), GST is split equally into <strong>CGST (50%)</strong> and <strong>SGST (50%)</strong>. For inter-state orders, the full rate is attributed to <strong>IGST (100%)</strong>. All displayed consumer prices on ALLZONE are inclusive of statutory GST.
                    </p>
                </div>
            </div>

            <!-- Right: Tax Compliance Settings Form -->
            <div>
                <x-card title="Platform Tax Rules" subtitle="Catalog-wide tax enforcement defaults">
                    <form method="POST" action="{{ route('admin.catalogue.standards.taxes.update') }}" class="space-y-4 text-xs">
                        @csrf
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Default Fallback Tax Slab</label>
                            <select name="default_tax_slab" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                <option value="0">0.0% (Exempt)</option>
                                <option value="5">5.0% (Essential Commodities)</option>
                                <option value="12">12.0% (Standard Low)</option>
                                <option value="18" selected>18.0% (Standard High / Electronics)</option>
                                <option value="28">28.0% (Luxury Goods)</option>
                            </select>
                            <span class="text-[10px] text-slate-400 mt-1 block">Applied when category does not override tax percentage.</span>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Minimum HSN Code Digits</label>
                            <select name="hsn_min_digits" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                <option value="4" selected>4 Digits (Turnover &lt; ₹5 Crores)</option>
                                <option value="6">6 Digits (Turnover &ge; ₹5 Crores)</option>
                                <option value="8">8 Digits (International / Chemicals)</option>
                            </select>
                        </div>

                        <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="require_hsn_code" value="1" checked class="rounded-sm border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="font-medium text-slate-700 dark:text-slate-300">Mandate HSN Code on all listings</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="tax_inclusive_pricing" value="1" checked class="rounded-sm border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="font-medium text-slate-700 dark:text-slate-300">All seller selling prices are Tax Inclusive</span>
                            </label>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full py-2.5 px-4 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition">
                                Save Tax Rules
                            </button>
                        </div>
                    </form>
                </x-card>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 3: SPECIFICATION STANDARDS -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'specifications'" class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Product Specification Blueprint</h3>
                <p class="text-xs text-slate-500">Recommended technical specification keys organized by commercial group for catalog completeness.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($specGroups as $groupName => $keys)
                <x-card :title="$groupName" :subtitle="count($keys) . ' standard attribute keys'">
                    <ul class="space-y-2 text-xs divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($keys as $k)
                            <li class="pt-1.5 first:pt-0 flex items-center justify-between text-slate-700 dark:text-slate-300">
                                <span class="font-medium">{{ $k }}</span>
                                <span class="text-[10px] font-mono text-slate-400">text</span>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endforeach
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 4: IMAGE REQUIREMENTS & QC -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'images'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Current Image Rules Card -->
            <div class="lg:col-span-2 space-y-4">
                <x-card title="Listing Media Guidelines" subtitle="Quality criteria checked during product QC moderation">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs mb-4">
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block mb-1">Min Resolution</span>
                            <strong class="text-emerald-600 dark:text-emerald-400 font-mono text-sm">
                                {{ $imageRequirements['min_width'] }} &times; {{ $imageRequirements['min_height'] }}
                            </strong>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block mb-1">Aspect Ratio</span>
                            <strong class="text-slate-900 dark:text-white font-mono text-xs">
                                {{ $imageRequirements['recommended_ratio'] }}
                            </strong>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block mb-1">Max File Size</span>
                            <strong class="text-slate-900 dark:text-white font-mono text-xs">
                                {{ $imageRequirements['max_file_size_mb'] }} MB / photo
                            </strong>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block mb-1">Image Count</span>
                            <strong class="text-slate-900 dark:text-white font-mono text-xs">
                                {{ $imageRequirements['min_images'] }} to {{ $imageRequirements['max_images'] }} photos
                            </strong>
                        </div>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200">
                            <strong class="font-bold block mb-0.5">Background Standard:</strong>
                            <p class="text-[11px] leading-relaxed">{{ $imageRequirements['background'] }}</p>
                        </div>

                        <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-900 dark:text-rose-200">
                            <strong class="font-bold block mb-0.5">Strictly Prohibited on Product Photos:</strong>
                            <p class="text-[11px] leading-relaxed">{{ $imageRequirements['prohibited'] }}</p>
                        </div>
                    </div>
                </x-card>
            </div>

            <!-- Right: Edit Image Requirements -->
            <div>
                <x-card title="Configure Image Rules" subtitle="Adjust QC threshold parameters">
                    <form method="POST" action="{{ route('admin.catalogue.standards.images.update') }}" class="space-y-3 text-xs">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Min Width (px)</label>
                                <input type="number" name="min_width" value="{{ $imageRequirements['min_width'] }}" required min="400" max="4000" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Min Height (px)</label>
                                <input type="number" name="min_height" value="{{ $imageRequirements['min_height'] }}" required min="400" max="4000" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Recommended Aspect Ratio</label>
                            <input type="text" name="recommended_ratio" value="{{ $imageRequirements['recommended_ratio'] }}" required class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Min Photos</label>
                                <input type="number" name="min_images" value="{{ $imageRequirements['min_images'] }}" required min="1" max="5" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Max Photos</label>
                                <input type="number" name="max_images" value="{{ $imageRequirements['max_images'] }}" required min="1" max="20" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Max File Size (MB)</label>
                            <input type="number" name="max_file_size_mb" value="{{ $imageRequirements['max_file_size_mb'] }}" required min="1" max="25" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Background Requirements</label>
                            <input type="text" name="background" value="{{ $imageRequirements['background'] }}" required class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Prohibited Content Rules</label>
                            <textarea name="prohibited" rows="2" required class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-xs">{{ $imageRequirements['prohibited'] }}</textarea>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full py-2.5 px-4 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition">
                                Update Image Rules
                            </button>
                        </div>
                    </form>
                </x-card>
            </div>
        </div>
    </div>

    <!-- Modal: Add Unit -->
    <x-modal name="add-unit-modal" title="Add Unit of Measure" size="md">
        <form action="{{ route('admin.catalogue.standards.units.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Unit Code (Lowercase ID) <span class="text-rose-500">*</span></label>
                <input type="text" name="code" required placeholder="e.g. sqft, roll, pair" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Full Unit Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required placeholder="e.g. Square Feet, Roll, Pair" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Short Symbol <span class="text-rose-500">*</span></label>
                    <input type="text" name="symbol" required placeholder="e.g. sq ft, roll" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Category <span class="text-rose-500">*</span></label>
                    <input type="text" name="category" required placeholder="e.g. Area, Length, Volume" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="flex items-center space-x-2 pt-2">
                <input type="checkbox" name="is_active" id="unit_is_active" value="1" checked class="rounded-sm border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <label for="unit_is_active" class="font-medium text-slate-700 dark:text-slate-300">Enable this unit for merchant listings</label>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="$dispatch('close-modal', 'add-unit-modal')" class="px-3 py-1.5 text-xs text-slate-600">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition">Save Unit</button>
            </div>
        </form>
    </x-modal>
</div>
@endsection
