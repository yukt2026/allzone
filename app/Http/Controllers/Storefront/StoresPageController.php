<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Store;
use App\Models\StoreLocation;
use App\Services\Search\SearchServiceInterface;
use App\Services\Storefront\LocationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoresPageController extends Controller
{
    public function __construct(
        protected SearchServiceInterface $searchService,
        protected LocationService $locationService
    ) {}

    /**
     * Unified Marketplace: Public stores directory redirects to unified catalog search (/search).
     */
    public function index(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('search', $request->query());
    }
}
