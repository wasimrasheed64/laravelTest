<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\Order;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MerchantController extends Controller
{
    public function __construct(
        MerchantService $merchantService
    ) {}

    /**
     * Useful order statistics for the merchant API.
     *
     * @param Request $request Will include a from and to date
     * @return JsonResponse
     * Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        $from = Carbon::parse($request->input('from'));
        $to = Carbon::parse($request->input('to'));

        $ordersCount        = Order::where('merchant_id', $request->user()->id)->whereBetween('created_at', [$from, $to])->count();
        $ordersTotal        = Order::where('merchant_id', $request->user()->id)->whereBetween('created_at', [$from, $to])->sum('subtotal');
        $commissionOwned    = Order::where('merchant_id', $request->user()->id)->whereNotNull('affiliate_id')
                                ->whereBetween('created_at', [$from, $to])->sum('commission_owed');
        return response()->json([
            'count' => $ordersCount,
            'revenue' => $ordersTotal,
            'commissions_owed' => $commissionOwned
        ]);


    }
}
