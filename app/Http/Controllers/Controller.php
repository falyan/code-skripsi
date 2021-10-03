<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected function userInfo()
    {
        $user = Auth::user();

        return $user;
    }

    public function getDistance(array $coordinates, $query) {
        $sqlDistance = DB::raw('( 6371  * acos( cos( radians(' . $coordinates[0] . ') ) 
                            * cos( radians( latitude ) ) 
                            * cos( radians( longitude ) 
                            - radians(' . $coordinates[1]  . ') ) 
                            + sin( radians(' . $coordinates[0]  . ') ) 
                            * sin( radians( latitude ) ) ) )');

        return $query->selectRaw("{$sqlDistance} AS distance");
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'success' => true,
            'message' => 'Login Successfull',
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => time() * 60
        ], 200);
    }

    protected function unAuthenticated()
    {
        return response()->json([
            'success' => false, 
            'message' => 'Unauthorized'
        ], 401);
    }

    protected function logoutResponse()
    {
        return $this->respondWithResult(true, 'Logout Successfull', 200);
    }

    public function respondWithData($data, $message, int $statusCode = 200)
    {
        return response()->json([
            'status' => $statusCode,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    public function respondWithResult($type = true, $message = 'success', int $statusCode = 200)
    {
        return response()->json([
            'success' => $type,
            'message' => $message,
            'status_code' => $statusCode
        ], $statusCode);
    }

    protected function respondValidationError($data = [], $message = 'Validation Error!', $headers = [])
    {
        $result = [
            'status' => 'error',
            'status_code' => 400,
            'message' => $message,
            'data' => [$data]
        ];

        return response()->json($result, 400, $headers);
    }
}
