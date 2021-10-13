<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Faq\FaqCommands;
use App\Http\Services\Faq\FaqQueries;
use Exception;

class FaqController extends Controller
{
    public function __construct()
    {
        $this->faqQueries = new FaqQueries();
        $this->faqCommands = new FaqCommands();
    }

    public function index()
    {
        try {
            return $this->respondWithData($this->faqQueries->getData(), 'Sukses ambil data faq');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}
