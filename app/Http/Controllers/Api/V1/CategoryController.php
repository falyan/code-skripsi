<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Category\CategoryQueries;
use Exception;

class CategoryController extends Controller
{
    protected $categoryQueries;

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

    public function getListCategory()
    {
        try {
            return $this->categoryQueries->getListCategory();
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

    public function getChildCategory()
    {
        try {
            return $this->categoryQueries->getChildCategory();
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getParentCategory()
    {
        try {
            return $this->categoryQueries->getParentCategory();
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}
