<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService,
        protected  ApiService $apiService
    ) {}

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return Order|null
     */
    public function processOrder(array $data): ?Order
    {
        if(!$this->getOrderByExternalOrderId($data['order_id'])){
            $merchant =  Merchant::where('domain', $data['merchant_domain'])->firstOrFail();
            $user =  User::where('email', $data['customer_email'])
                    ->where('type', User::TYPE_AFFILIATE)
                    ->first();
            if($user){
                $affiliateUser = Affiliate::where('user_id', $user->id)->where('merchant_id',$merchant->id)->first();
            }else{
                $affiliateUser = $this->affiliateService->register($merchant, $data['customer_email'], $data['customer_name'], 0.1);
            }

            $order = new Order([
                'external_order_id' => $data['order_id'],
                'subtotal' => $data['subtotal_price'],
                'affiliate_id' => $affiliateUser->id,
                'merchant_id' => $merchant->id,
                'commission_owed' => $data['subtotal_price'] * $affiliateUser->commission_rate,
            ]);
            $order->save();
            return $order;
        }
        return null;
    }


    /**
     * @param $externalOrderId
     * @return ?Order
     */
    public function getOrderByExternalOrderId($externalOrderId): ?Order
    {
        return Order::where('external_order_id', $externalOrderId)->first();
    }
}
