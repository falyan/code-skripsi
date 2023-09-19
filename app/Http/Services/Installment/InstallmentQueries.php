<?php

namespace App\Http\Services\Installment;

use App\Http\Services\Service;
use App\Models\InstallmentProvider;

class InstallmentQueries extends Service
{
    public function getListInstallmentProvider($price)
    {
        $providers = InstallmentProvider::with(['details' => function ($query) {
            $query->orderBy('tenor', 'asc');
        }])->where('status', 1)->get();

        $providers = $providers->transform(function ($provider) use ($price) {
            foreach ($provider->details as $detail) {
                if (!empty($price)) {
                    $markup_price = $price / (1 - ($detail->mdr_percentage / 100));
                    $installment_price = ($markup_price / $detail->tenor) + ($markup_price * $detail->fee_percentage / 100);
                    $detail->simulation_price = round($installment_price);
                } else {
                    $detail->installment_price = 0;
                }
            }

            return $provider;
        });

        $tenor_prices = [];
        foreach ($providers as $provider) {
            foreach ($provider->details as $simulation) {
                $tenor_prices[] = $simulation->simulation_price_installment;
            }
        }

        return [
            'providers' => $providers,
            'smallest_installment' => !empty($tenor_prices) ? min($tenor_prices) : null,
        ];
    }

    public function getTenorInstallmentByProvider($providerId, $price)
    {
        $providers = InstallmentProvider::with('details')->where('id', $providerId)->get();

        // $providers = $providers->transform(function ($provider) use ($price) {
        //     foreach ($provider->details as $detail) {
        //         if (!empty($price)) {
        //             $installment_price = $price + ($price * $detail->mdr_percentage / 100) + ($price * $detail->fee_percentage / 100);
        //             $installment_price = round($installment_price / $detail->tenor);
        //             $detail->simulation_price = $installment_price;
        //         } else {
        //             $detail->installment_price = 0;
        //         }
        //     }

        //     return $provider;
        // });

        return $providers;
    }
}
