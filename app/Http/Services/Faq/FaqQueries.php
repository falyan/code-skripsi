<?php

namespace App\Http\Services\Faq;

use App\Models\Faq;

class FaqQueries
{
    public function getData()
    {
        return Faq::get();
    }
}
