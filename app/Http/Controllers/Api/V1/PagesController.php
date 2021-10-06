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

    public function index()
    {
        $validator = Validator::make(request()->all(), [
            'page_type' => 'required'
        ]);

        try {
            if ($validator->fails()) {
                throw new Exception($validator->errors(), 400);
            }

            return $this->pagesQueries->getPageType(request('page_type'));
        } catch (Exception $th) {
            if (in_array($th->getCode(), $this->error_codes)) {
                return $this->respondWithResult(false, $th->getMessage(), $th->getCode());
            }
            return $this->respondWithResult(false, $th->getMessage(), 500);
        }
    }
}
