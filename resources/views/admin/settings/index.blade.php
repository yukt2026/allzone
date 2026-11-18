@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'System Platform Settings'])

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">

    <!-- Page Header -->
    <x-page-header 
        title="Global Platform Settings" 
        subtitle="Manage marketplace branding, tax GST rules, catalog moderation standards, return policies, and secure API integrations."
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'System Settings' => '#']"
    >
        <x-slot name="badge">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                Core Engine v2.0
            </span>
        </x-slot>
    </x-page-header>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Tabbed Settings Layout -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden" x-data="{ testResult: null, testing: false }">
        
        <!-- Tab Bar -->
        <div class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 p-2 overflow-x-auto flex items-center space-x-1 no-scrollbar">
            @foreach($groups as $grpKey => $grpTitle)
                <a 
                    href="{{ route('admin.settings.index', ['tab' => $grpKey]) }}" 
                    class="px-3.5 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition {{ $currentTab === $grpKey ? 'bg-white dark:bg-slate-800 text-emerald-700 dark:text-emerald-400 shadow-sm border border-slate-200/60 dark:border-slate-700' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/60 dark:hover:bg-slate-800/60' }}"
                >
                    {{ $grpTitle }}
                </a>
            @endforeach
        </div>

        @if($currentTab === 'integrations' || $currentTab === 'payment')
            <!-- Live API Diagnostics Bar -->
            <div class="p-4 bg-slate-50/50 dark:bg-slate-800/20 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Integration Diagnostics</span>
                    <span class="text-[11px] text-slate-500">(Secrets are encrypted and never shown in plain text)</span>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        @click="testing = true; testResult = null; fetch('{{ route('admin.settings.test_integration') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ service: 'razorpay' }) }).then(r => r.json()).then(d => { testResult = d; testing = false; })"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 shadow-sm"
                        :disabled="testing"
                    >
                        Test Razorpay
                    </button>
                    <button 
                        type="button" 
                        @click="testing = true; testResult = null; fetch('{{ route('admin.settings.test_integration') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ service: 'shiprocket' }) }).then(r => r.json()).then(d => { testResult = d; testing = false; })"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 shadow-sm"
                        :disabled="testing"
                    >
                        Test Shiprocket
                    </button>
                </div>
            </div>

            <!-- Diagnostics Response Box -->
            <div x-show="testing || testResult" x-cloak class="p-4 bg-emerald-50/60 dark:bg-emerald-950/30 border-b border-emerald-100 dark:border-emerald-900 text-xs">
                <div x-show="testing" class="text-slate-600 dark:text-slate-400 font-medium flex items-center gap-2">
                    <svg class="animate-spin h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    Pinging service endpoints...
                </div>
                <div x-show="testResult" class="font-medium">
                    <span :class="testResult && testResult.status === 'success' ? 'text-emerald-700 dark:text-emerald-300 font-bold' : 'text-rose-700 dark:text-rose-300 font-bold'" x-text="testResult ? testResult.service + ': ' + testResult.message : ''"></span>
                </div>
            </div>
        @endif

        <!-- Tab Body Form -->
        <form method="POST" action="{{ route('admin.settings.update') }}" class="p-6 space-y-6">
            @csrf
            <input type="hidden" name="tab" value="{{ $currentTab }}">

            @php
                $tabSettings = $settings->get($currentTab, collect());
                $secretList = [
                    'razorpay_key_secret',
                    'razorpay_webhook_secret',
                    'shiprocket_password',
                    'shiprocket_api_key',
                    'shiprocket_webhook_token',
                    'smtp_password',
                ];
            @endphp

            @if($tabSettings->isEmpty())
                <div class="py-12 text-center text-slate-400 text-xs">
                    No configurable settings found under {{ $groups[$currentTab] ?? $currentTab }}.
                </div>
            @else
                <div class="space-y-4 divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($tabSettings as $setting)
                        @php
                            $isSecret = in_array($setting->key, $secretList, true) || str_contains($setting->key, 'secret') || str_contains($setting->key, 'password');
                        @endphp
                        <div class="pt-4 first:pt-0 grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                            <div>
                                <label class="text-xs font-bold text-slate-900 dark:text-white block">
                                    {{ $setting->label ?: ucwords(str_replace('_', ' ', $setting->key)) }}
                                    @if($isSecret)
                                        <span class="inline-flex items-center ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400">
                                            Encrypted Secret
                                        </span>
                                    @endif
                                </label>
                                <span class="font-mono text-[10px] text-slate-400 block mt-0.5">
                                    {{ $setting->key }}
                                </span>
                            </div>

                            <div class="md:col-span-2">
                                @if($isSecret)
                                    <div class="space-y-1">
                                        <input 
                                            type="password" 
                                            name="settings[{{ $setting->key }}]" 
                                            value="{{ !empty($setting->value) ? '••••••••••••••••' : '' }}" 
                                            autocomplete="new-password"
                                            class="text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5 w-full focus:ring-1 focus:ring-emerald-500 font-mono"
                                            placeholder="Leave blank or bullet mask to preserve existing secret"
                                        >
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block">
                                            Never displayed in plain text. Leave unchanged to retain existing encrypted credential.
                                        </span>
                                    </div>
                                @elseif($setting->type === 'boolean' || $setting->type === 'bool')
                                    <select 
                                        name="settings[{{ $setting->key }}]" 
                                        class="text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5 max-w-xs w-full font-semibold focus:ring-1 focus:ring-emerald-500"
                                    >
                                        <option value="1" {{ ($setting->value == '1' || $setting->value === 'true' || $setting->value === true) ? 'selected' : '' }}>Enabled / True</option>
                                        <option value="0" {{ ($setting->value == '0' || $setting->value === 'false' || $setting->value === false || empty($setting->value)) ? 'selected' : '' }}>Disabled / False</option>
                                    </select>
                                @elseif($setting->type === 'textarea' || $setting->type === 'text')
                                    <textarea 
                                        name="settings[{{ $setting->key }}]" 
                                        rows="3" 
                                        class="text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5 w-full focus:ring-1 focus:ring-emerald-500 font-mono leading-relaxed"
                                    >{{ is_array($setting->value) ? json_encode($setting->value, JSON_PRETTY_PRINT) : $setting->value }}</textarea>
                                @elseif(in_array($setting->type, ['integer', 'numeric', 'float', 'number'], true))
                                    <input 
                                        type="number" 
                                        step="any" 
                                        name="settings[{{ $setting->key }}]" 
                                        value="{{ $setting->value }}" 
                                        class="text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5 max-w-xs w-full focus:ring-1 focus:ring-emerald-500 font-semibold"
                                    >
                                @else
                                    <input 
                                        type="text" 
                                        name="settings[{{ $setting->key }}]" 
                                        value="{{ is_array($setting->value) ? json_encode($setting->value) : $setting->value }}" 
                                        class="text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5 w-full focus:ring-1 focus:ring-emerald-500"
                                    >
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end pt-6 border-t border-slate-100 dark:border-slate-800">
                    <button 
                        type="submit" 
                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-sm transition"
                    >
                        Save {{ $groups[$currentTab] ?? 'Settings' }}
                    </button>
                </div>
            @endif
        </form>

    </div>

</div>
@endsection
