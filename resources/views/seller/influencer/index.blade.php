@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Influencer Marketing - Meesho Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header (Screenshot 9) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Influencer Marketing
        </h1>
        <div class="flex items-center space-x-3 text-xs">
            <a href="#" class="flex items-center space-x-1.5 text-slate-700 font-semibold hover:text-slate-900">
                <span class="text-rose-600">▶</span>
                <span>How it Works?</span>
            </a>
            <button type="button" class="px-4 py-2 rounded-lg bg-[#3e2e84] hover:bg-[#32236e] text-white font-bold text-xs transition shadow-xs">
                Select Catalogs
            </button>
        </div>
    </div>

    <!-- Promo Banner: 0.5% Commission (Screenshot 9 Top) -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-orange-50 via-pink-50 to-amber-50 border border-orange-200 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="text-lg font-black text-slate-900">Now commission is available at 0.5%</span>
                <span class="px-1.5 py-0.2 rounded bg-rose-500 text-white font-black text-[9px] uppercase">NEW</span>
            </div>
            <p class="text-xs text-slate-600 mt-1">Festive season ahead, boost orders upto 30%* &bull; T&amp;C apply</p>
            <button type="button" class="mt-4 px-4 py-1.5 rounded-lg bg-[#3e2e84] text-white text-xs font-bold hover:bg-[#32236e] transition">
                Opt-in now
            </button>
        </div>
        <div class="w-48 h-24 bg-gradient-to-tr from-amber-400 to-rose-400 rounded-xl flex items-center justify-center text-white font-black text-center p-3 shadow-md shrink-0">
            Just 0.5% Commission!
        </div>
    </div>

    <!-- Value Proposition Banner (Screenshot 9 Middle) -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-6 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="space-y-3">
            <h2 class="text-lg font-bold text-slate-900">Grow Orders with Influencer Promotion! 📈</h2>
            <div class="space-y-2 text-xs text-slate-700">
                <div class="flex items-center space-x-2">
                    <span class="text-emerald-600 font-bold">✔</span>
                    <span><strong>50K+ influencers</strong> can make videos on your products</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-emerald-600 font-bold">✔</span>
                    <span>Videos are posted on <strong>Meesho App + Social Media</strong> = More Visibility, More Sales</span>
                </div>
            </div>
            <button type="button" class="px-5 py-2.5 rounded-lg bg-[#3e2e84] hover:bg-[#32236e] text-white text-xs font-bold transition shadow-xs mt-2">
                Select Catalogs
            </button>
        </div>
        <div class="w-64 h-36 bg-purple-50 rounded-2xl border border-purple-100 flex items-center justify-center text-purple-700 font-bold text-xs p-4 text-center">
            📱 Interactive Shorts &amp; Reels Feed Placement
        </div>
    </div>

    <!-- 2Lakh+ Sellers are Growing Grid (Screenshot 9 Bottom) -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-6 shadow-2xs">
        <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center space-x-2">
            <span>2Lakh+ Sellers are Growing with Influencer Videos!</span>
            <span>🔥</span>
        </h3>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            @php
                $videoItems = [
                    ['title' => 'Women Kurti', 'views' => '6.9M', 'likes' => '219K'],
                    ['title' => 'Mens Jackets', 'views' => '172K', 'likes' => '5.3K'],
                    ['title' => 'Home Decor', 'views' => '4.9M', 'likes' => '138K'],
                    ['title' => 'IG Story', 'views' => '2.1M', 'likes' => '94K'],
                    ['title' => 'Collection Page', 'views' => '850K', 'likes' => '32K'],
                ];
            @endphp
            @foreach($videoItems as $vid)
                <div class="relative rounded-xl overflow-hidden bg-slate-900 text-white h-52 flex flex-col justify-between p-3 group hover:scale-[1.02] transition">
                    <div class="text-[10px] font-bold bg-black/40 backdrop-blur-xs px-2 py-0.5 rounded w-max">
                        {{ $vid['title'] }}
                    </div>
                    <div class="text-center">
                        <span class="w-8 h-8 rounded-full bg-white/20 backdrop-blur-xs inline-flex items-center justify-center text-xs">▶</span>
                    </div>
                    <div class="flex items-center justify-between text-[10px] font-semibold text-slate-200">
                        <span>▶ {{ $vid['views'] }}</span>
                        <span>❤️ {{ $vid['likes'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
