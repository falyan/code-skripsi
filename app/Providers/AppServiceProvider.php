<?php

namespace App\Providers;

use App\Http\Resources\Etalase\EtalaseCollection;
use App\Http\Services\Manager\AgentManager;
use App\Http\Services\Manager\GamificationManager;
use App\Http\Services\Manager\IconcashManager;
use App\Http\Services\Manager\IconpayManager;
use App\Http\Services\Manager\KudoManager;
use App\Http\Services\Manager\LogisticManager;
use App\Http\Services\Manager\RajaOngkirManager;
use App\Http\Services\Notification\NotificationCommands;
use App\Http\Services\Service;
use App\Http\Services\Transaction\TransactionCommands;
use App\Http\Services\Voucher\VoucherCommands;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    public function boot()
    {
        EtalaseCollection::withoutWrapping();
        RajaOngkirManager::init();
        TransactionCommands::init();
        Service::init();
        IconcashManager::init();
        IconpayManager::init();
        NotificationCommands::init();
        VoucherCommands::init();
        NotificationCommands::init();
        KudoManager::init();
        AgentManager::init();
        GamificationManager::init();
        LogisticManager::init();
    }
}
