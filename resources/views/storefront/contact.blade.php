@extends('layouts.storefront')

@section('title', 'Contact Us - ' . setting('name', 'ALLZONE'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 space-y-8">

    <!-- Breadcrumb Navigation -->
    <nav class="flex items-center space-x-2 text-xs text-slate-500 overflow-x-auto no-scrollbar">
        <a href="{{ route('home') }}" class="hover:text-indigo-600 font-medium">Home</a>
        <span class="text-slate-300">/</span>
        <span class="font-bold text-slate-800">Contact Us</span>
    </nav>

    <!-- Success Feedback Alert -->
    @if(session('success'))
        <div class="p-4 sm:p-5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2.5">
                <span class="text-emerald-600 text-xl font-bold">✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-lg">&times;</button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm space-y-1 shadow-xs">
            <div class="font-bold flex items-center space-x-1.5">
                <span>⚠️</span>
                <span>Please correct the errors below:</span>
            </div>
            <ul class="list-disc list-inside text-xs text-rose-700 pl-2">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Hero Header Banner -->
    <div class="p-6 sm:p-10 rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white shadow-xl space-y-3">
        <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-white/20 text-white backdrop-blur-md">
            <span>📞 24/7 CUSTOMER SUPPORT</span>
        </span>
        <h1 class="text-2xl sm:text-4xl font-black text-white tracking-tight">We're Here to Help You</h1>
        <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
            Have questions about an active order, seller onboarding, product queries, or delivery logistics? Reach out to the {{ $appName }} support team operated by {{ $companyName }}.
        </p>
    </div>

    <!-- Contact Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Phone Support -->
        <div class="p-5 rounded-2xl sm:rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-2 hover:border-indigo-300 transition">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-lg">
                📞
            </div>
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Toll-Free Helpline</h3>
            <p class="text-sm font-black text-slate-900">{{ $supportPhone }}</p>
            <p class="text-[11px] text-slate-500">Available 7 days a week</p>
        </div>

        <!-- Email Support -->
        <div class="p-5 rounded-2xl sm:rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-2 hover:border-indigo-300 transition">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg">
                ✉️
            </div>
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Direct Email</h3>
            <a href="mailto:{{ $supportEmail }}" class="text-sm font-black text-slate-900 hover:text-indigo-600 transition block truncate">
                {{ $supportEmail }}
            </a>
            <p class="text-[11px] text-slate-500">Responses within 2 hours</p>
        </div>

        <!-- Corporate Entity -->
        <div class="p-5 rounded-2xl sm:rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-2 hover:border-indigo-300 transition">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-lg">
                🏢
            </div>
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Corporate Operating Entity</h3>
            <p class="text-sm font-black text-slate-900">{{ $companyName }}</p>
            <p class="text-[11px] text-slate-500">{{ $officeAddress }}</p>
        </div>

        <!-- Operating Hours -->
        <div class="p-5 rounded-2xl sm:rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-2 hover:border-indigo-300 transition">
            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center font-bold text-lg">
                ⏰
            </div>
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Business Support Hours</h3>
            <p class="text-xs font-bold text-slate-900 leading-snug">{{ $businessHours }}</p>
            <p class="text-[11px] text-slate-500">Fast courier dispatch</p>
        </div>
    </div>

    <!-- Contact Form & FAQ Split -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 items-start">

        <!-- Form: 3 Cols -->
        <div class="lg:col-span-3 bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-5">
            <div>
                <h2 class="text-lg font-black text-slate-900 tracking-tight">Send Us a Direct Message</h2>
                <p class="text-xs text-slate-500">Fill in the form below and an agent will follow up via email or phone.</p>
            </div>

            <form method="POST" action="{{ route('contact.submit') }}" class="space-y-4 text-xs sm:text-sm">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Your Name *</label>
                        <input type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" required placeholder="e.g. Rahul Sharma"
                               class="w-full text-xs sm:text-sm py-2.5 px-3.5 rounded-xl border border-slate-200 focus:ring-indigo-500 font-medium">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Email Address *</label>
                        <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required placeholder="e.g. rahul@example.com"
                               class="w-full text-xs sm:text-sm py-2.5 px-3.5 rounded-xl border border-slate-200 focus:ring-indigo-500 font-medium">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Phone Number (10 digits)</label>
                        <div class="flex items-center">
                            <span class="px-3 py-2.5 rounded-l-xl bg-slate-100 border border-r-0 border-slate-200 text-slate-500 font-bold text-xs">+91</span>
                            <input type="text" name="phone" value="{{ old('phone', auth()->user()?->phone) }}" maxlength="10" placeholder="9876543210"
                                   class="w-full text-xs sm:text-sm py-2.5 px-3 rounded-r-xl border border-slate-200 focus:ring-indigo-500 font-medium">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Inquiry Category *</label>
                        <select name="category" required class="w-full text-xs sm:text-sm py-2.5 px-3 rounded-xl border border-slate-200 focus:ring-indigo-500 font-medium bg-white">
                            <option value="order" {{ old('category') === 'order' ? 'selected' : '' }}>Order Status & Delivery</option>
                            <option value="refund" {{ old('category') === 'refund' ? 'selected' : '' }}>Returns & Refund Tracking</option>
                            <option value="product" {{ old('category') === 'product' ? 'selected' : '' }}>Product Quality & Warranty</option>
                            <option value="account" {{ old('category') === 'account' ? 'selected' : '' }}>Customer Account & Addresses</option>
                            <option value="logistics" {{ old('category') === 'logistics' ? 'selected' : '' }}>Seller & Logistics Partnership</option>
                            <option value="general" {{ old('category', 'general') === 'general' ? 'selected' : '' }}>General Feedback & Inquiry</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Subject / Order ID *</label>
                    <input type="text" name="subject" value="{{ old('subject') }}" required placeholder="e.g. Need assistance with Order #AZ-..."
                           class="w-full text-xs sm:text-sm py-2.5 px-3.5 rounded-xl border border-slate-200 focus:ring-indigo-500 font-medium">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Detailed Message *</label>
                    <textarea name="message" rows="4" required placeholder="Describe your question or issue in detail..."
                              class="w-full text-xs sm:text-sm p-3.5 rounded-xl border border-slate-200 focus:ring-indigo-500 font-medium leading-relaxed">{{ old('message') }}</textarea>
                </div>

                <div class="pt-2 flex items-center justify-end">
                    <button type="submit" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs sm:text-sm shadow-md transition active:scale-95 cursor-pointer flex items-center space-x-2">
                        <span>Send Message</span>
                        <span>&rarr;</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- FAQs: 2 Cols -->
        <div class="lg:col-span-2 space-y-4">
            <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">
                Frequently Asked Questions
            </h3>

            <div class="space-y-3 text-xs">
                <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-1.5">
                    <h4 class="font-bold text-slate-900">How do I track my active order?</h4>
                    <p class="text-slate-600 leading-relaxed">
                        Go to your <a href="{{ route('orders.index') }}" class="text-indigo-600 font-bold hover:underline">My Orders</a> dashboard or click the live GPS tracking link sent via SMS and WhatsApp after dispatch.
                    </p>
                </div>

                <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-1.5">
                    <h4 class="font-bold text-slate-900">What is the return window for products?</h4>
                    <p class="text-slate-600 leading-relaxed">
                        General marketplace merchandise enjoys a <strong>7-day doorstep return</strong> window. Perishables and dairy are verified on the spot or within 24 hours.
                    </p>
                </div>

                <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-1.5">
                    <h4 class="font-bold text-slate-900">How long do refunds take?</h4>
                    <p class="text-slate-600 leading-relaxed">
                        UPI and wallet refunds are processed instantly (2–4 hours). Credit/Debit cards take 3–5 banking business days.
                    </p>
                </div>

                <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-1.5">
                    <h4 class="font-bold text-slate-900">Interested in becoming a merchant?</h4>
                    <p class="text-slate-600 leading-relaxed">
                        Visit our <a href="{{ route('seller.dashboard') }}" class="text-indigo-600 font-bold hover:underline">Seller Hub</a> to onboard your store and products within 24 hours.
                    </p>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
