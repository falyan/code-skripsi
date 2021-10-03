<?php
namespace App\Helpers;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class AuthHelper extends Controller{

    private $url;

    private $client;

    public function __construct()
    {
        $this->url = env('AUTH_URL', 'http://gandalf.test');

        $this->client = new Client();
    }

    public function publicService($uri_path, $data = [], $headers = [])
    {

        try{

            $res = $this->client->request('POST' ,$this->url . $uri_path, [
                'headers' => [
                ],
                'form_params' => $data
            ]);

            return $res->getBody();
        }catch (ClientException $e){
            return response()->json(json_decode($e->getResponse()->getBody(), true), $e->getResponse()->getStatusCode());
        }
        
    }

    public function privateService($uri_path, $data = [], $headers = [])
    {
        try{

            $res = $this->client->request('POST' ,$this->url . $uri_path, [
                'headers' => $headers,
                'form_params' => $data
            ]);

            return $res->getBody();
        }catch (ClientException $e){
            return response()->json(json_decode($e->getResponse()->getBody(), true), $e->getResponse()->getStatusCode());
        }
    }

}
