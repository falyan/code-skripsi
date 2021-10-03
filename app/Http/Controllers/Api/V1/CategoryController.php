<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Category\CategoryQueries;

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

    public function getAllCategory(){
        return $this->categoryQueries->getAllCategory();
    }

    public function getThreeRandomCategory(){
        return $this->categoryQueries->getThreeRandomCategory();
    }
}
