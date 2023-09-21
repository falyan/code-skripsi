<?php

namespace App\Http\Services\Installment;

use App\Http\Services\Service;
use App\Models\InstallmentProvider;

class InstallmentQueries extends Service
{
    public function getListInstallmentProvider($price, $provider_id)
    {
        $providers = InstallmentProvider::with(['details' => function ($query) {
            $query->orderBy('tenor', 'asc');
        }])->where('status', 1);

        if (!empty($provider_id)) {
            $providers = $providers->where('id', $provider_id);
        }

        if (!empty($price)) {
            $providers = $providers->where(function ($query) use ($price) {
                $query->where(function ($query) use ($price) {
                    $query->where('provider_code', 'bri-ceria')
                        ->where('min_price', '<=', $price)
                        ->where('max_price', '>=', $price);
                })->orWhere(function ($query) use ($price) {
                    $query->where('provider_code', 'bni-installment')
                        ->where('min_price', '<=', $price);
                });
            });
        }

        $providers = $providers->get();

        $providers = $providers->transform(function ($provider) use ($price) {
            if ($provider->provider_code == 'bri-ceria') {
                foreach ($provider->details as $detail) {
                    if (!empty($price)) {
                        $markup_price = $price / (1 - ($detail->mdr_percentage / 100));
                        $installment_price = ($markup_price / $detail->tenor) + ($markup_price * $detail->interest_percentage / 100);
                        $detail->simulation_price_installment = round($installment_price);
                        $detail->simulation_fee_installment = round($markup_price * $detail->mdr_percentage / 100);
                    } else {
                        $detail->simulation_price_installment = 0;
                        $detail->simulation_fee_installment = 0;
                    }
                }
            } else if ($provider->provider_code == 'bni-installment') {
                foreach ($provider->details as $detail) {
                    if (!empty($price)) {
                        $markup_price = ($price + $detail->fee_provider) / (1 - ($detail->mdr_percentage / 100));
                        $detail->simulation_price_installment = round($markup_price / $detail->tenor);
                        $detail->simulation_fee_installment = round($markup_price * $detail->mdr_percentage / 100);
                    } else {
                        $detail->simulation_price_installment = 0;
                        $detail->simulation_fee_installment = 0;
                    }
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

    public function getTenorInstallmentByProvider($price, $provider_id)
    {
        $providers = InstallmentProvider::with(['details' => function ($query) {
            $query->orderBy('tenor', 'asc');
        }])->where('status', 1)->where('id', $provider_id)->get();

        $providers = $providers->transform(function ($provider) use ($price) {
            if ($provider->provider_code == 'bri-ceria') {
                unset($provider->details);
                $provider->details = [];
            } else if ($provider->provider_code == 'bni-installment') {
                foreach ($provider->details as $detail) {
                    if (!empty($price)) {
                        $markup_price = ($price + $detail->fee_provider) / (1 - ($detail->mdr_percentage / 100));
                        $detail->simulation_price_installment = round($markup_price / $detail->tenor);
                        $detail->simulation_fee_installment = round($markup_price * $detail->mdr_percentage / 100);
                    } else {
                        $detail->simulation_price_installment = 0;
                        $detail->simulation_fee_installment = 0;
                    }
                }
            }

            return $provider;
        });

        return [
            'providers' => $providers,
        ];
    }

    public static function calculateInstallment($payload, $price)
    {
        $providerId = $payload['provider_id'] ?? null;
        $tenor = $payload['tenor'] ?? null;

        $providers = InstallmentProvider::with(['details' => function ($query) use ($tenor) {
            if ($tenor !== null) {
                $query->where('tenor', $tenor);
            }
        }])->where('id', $providerId)->where('status', 1)->first();

        if ($providers->provider_code === 'bri-ceria') {
            $markup_price = $price / (1 - ($providers->details[0]->mdr_percentage / 100));
            $installment_fee = round($markup_price * $providers->details[0]->mdr_percentage / 100);
        } else if ($providers->provider_code === 'bni-installment') {
            $markup_price = ($price + $providers->details[0]->fee_provider) / (1 - ($providers->details[0]->mdr_percentage / 100));
            $installment_price = round($markup_price / $tenor);
            $installment_fee = round($markup_price * $providers->details[0]->mdr_percentage / 100);
        }

        return [
            'price' => $price,
            'tenor' => $tenor,
            'markup_price' => round($markup_price),
            'installment_price' => $installment_price ?? 0,
            'installment_fee' => $installment_fee ?? 0,
            'provider_fee' => $providers->details[0]->fee_provider ?? 0,
            'interest_percentage' => $providers->details[0]->interest_percentage ?? 0,
        ];
    }
}
