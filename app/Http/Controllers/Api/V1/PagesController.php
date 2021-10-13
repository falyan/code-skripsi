<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Pages\PagesCommands;
use App\Http\Services\Pages\PagesQueries;
use Exception;
use Illuminate\Support\Facades\Validator;

class PagesController extends Controller
{
    public function __construct()
    {
        $this->pagesQueries = new PagesQueries();
        $this->pagesCommands = new PagesCommands();
    }

    public function termCondition()
    {
        try {
            return $this->pagesQueries->termConditionPage();
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function contactUs()
    {
        try {
            return $this->pagesQueries->contactUsPage();
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function aboutUs()
    {
        try {
            return $this->pagesQueries->aboutUsPage();
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function privacyPolicy()
    {
        try {
            return $this->pagesQueries->privacyPolicyPage();
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}
