<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    /**
     * Display Meesho Warehouse management, rates, and enrollment (Screenshot 8).
     */
    public function index(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        return view('seller.warehouse.index', [
            'seller' => $seller,
        ]);
    }
}
