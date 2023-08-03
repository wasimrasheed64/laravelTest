<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService,
        protected  Affiliate $affiliate
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {

        if ($this->getAffiliate($email)) {
            throw new AffiliateCreateException('User already exists');
        }

        $affiliateUser  =  User::create([
            'name' => $name,
            'email' => $email,
            'type' => User::TYPE_AFFILIATE,
        ]);

        $discountCode = $this->apiService->createDiscountCode($merchant);

        $affiliate = $this->affiliate->create([
            'user_id' => $affiliateUser->id,
            'merchant_id' => $merchant->id,
            'commission_rate' => $commissionRate,
            'discount_code' => $discountCode['code'],
        ]);
        Mail::to($email)->send(new AffiliateCreated($affiliate));
        return $affiliate;
    }

    public function getAffiliate($email): ?User
    {
         return  User::where('email',$email)->first();
    }
}
