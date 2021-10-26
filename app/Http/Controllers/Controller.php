<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Controller extends BaseController
{
    /**
     * @OA\Info(
     *   title="API Saruman",
     *   version="1.0",
     *   @OA\Contact(
     *     email="support@example.com",
     *     name="Support Team"
     *   )
     * ),
     * 
     * @OA\Server(
     *      url=SWAGGER_LUME_CONST_HOST,
     *      description="Demo API Server"
     * ),
     * 
     * @OA\SecurityScheme(
     *     securityScheme="bearerAuth",
     *     in="header",
     *     name="bearerAuth",
     *     type="http",
     *     scheme="bearer",
     *     bearerFormat="JWT",
     * ),
     * 
     * @OA\SecurityDefinitions(
     *      bearer={
     *          type="apiKey",
     *          name="Authorization",
     *          in="header"
     *      }
     * ),
     */
    protected $error_codes = [400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 421, 422, 423, 424, 425, 426, 428, 429, 431, 451, 500, 501, 502, 503, 504, 505, 506, 507, 508, 510, 511];

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
            'data' => $data
        ];

        return response()->json($result, 400, $headers);
    }

    public function respondErrorException($e, $request)
    {
        $data = [];
        $message = $e->getMessage();
        $error = ("{$message}\r\nFile {$e->getFile()}:{$e->getLine()} with message {$e->getMessage()}");

        $uid = Str::random(12);
        Log::error($uid, [
            'path_url' => $request->path(),
            'query' =>  $request->query(),
            'body' => $request->except(['password', 'c_password', 'bearer', 'bearer_token', 'related_id', 'related_customer_id', 'rlc_id']),
            'error' => $error
        ]);

        $data = [
            'success' => false, 
            'status_code' => in_array($e->getCode(), $this->error_codes) ? $e->getCode() : 404,
            'message' => $message
        ];

        
        if (in_array($e->getCode(), $this->error_codes)) {
            return response($data, $e->getCode(), $request->header??[]);
        }else {
            return response($data, 404);
        }

    }
    
}
