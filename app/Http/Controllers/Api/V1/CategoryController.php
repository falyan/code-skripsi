<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Category\CategoryQueries;
use Exception;

class CategoryController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->categoryQueries = new CategoryQueries();
    }

    public function getAllCategory()
    {
        try {
            return $this->categoryQueries->getAllCategory();
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getThreeRandomCategory()
    {
        try {
            return $this->categoryQueries->getThreeRandomCategory();
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getBasicCategory()
    {
        try {
            return $this->categoryQueries->getBasicCategory();
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}
