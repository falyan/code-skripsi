<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\AuthHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->profilCommands = new ProfilCommands();
        // $this->profilQueries = new ProfilQueries();
    }

    public function index()
    {
        try {
            $user = Auth::user();
            $data = Customer::with(['merchant'])->find($user->id);

            return $this->respondWithData($data, 'Success get user info', 200);
        } catch (Exception $ex) {
            return $this->respondWithResult(false, $ex->getMessage(), 500);
        }
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $authReq = new AuthHelper();
        $body = $authReq->privateService('logout', [], $request->header());

        return $this->respondWithResult(true, 'Berhasil Logout');
    }
}
