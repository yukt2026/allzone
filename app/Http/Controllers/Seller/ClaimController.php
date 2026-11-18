<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClaimController extends Controller
{
    /**
     * Display Claims Management Dashboard (matching Meesho screenshot 6).
     */
    public function index(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        $tab = $request->query('tab', 'all');

        $claims = [
            'total' => 22,
            'open' => 1,
            'approved' => 11,
            'rejected' => 10,
        ];

        return view('seller.claims.index', [
            'tab' => $tab,
            'claims' => $claims,
        ]);
    }
}
