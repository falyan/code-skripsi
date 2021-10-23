<?php

namespace App\Http\Services\Faq;

use App\Http\Services\Service;
use App\Models\Faq;

class FaqQueries extends Service
{
    public function getData()
    {
        return Faq::get();
    }
}
