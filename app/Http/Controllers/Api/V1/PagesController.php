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
        } catch (Exception $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
        }
    }

    public function contactUs()
    {
        try {
            return $this->pagesQueries->contactUsPage();
        } catch (Exception $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
        }
    }

    public function aboutUs()
    {
        try {
            return $this->pagesQueries->aboutUsPage();
        } catch (Exception $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
        }
    }

    public function privacyPolicy()
    {
        try {
            return $this->pagesQueries->privacyPolicyPage();
        } catch (Exception $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
        }
    }
}
