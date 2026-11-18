<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QualityController extends Controller
{
    /**
     * Display Catalog Quality & Dispatch Performance (Meesho screenshot 3).
     */
    public function index(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        $metrics = [
            'dispatch_health' => 94,
            'on_time_dispatch' => 30,
            'total_dispatched' => 32,
            'catalogs_at_risk' => 1,
            'catalogs_blocked' => 0,
            'customer_return_rate' => '40.00%',
            'rto_rate' => '37.50%',
        ];

        return view('seller.quality.index', compact('metrics'));
    }
}
