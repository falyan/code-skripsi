<?php

namespace App\Providers;

use App\Http\Resources\Etalase\EtalaseCollection;
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
    }
}
