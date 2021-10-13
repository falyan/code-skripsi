<?php
namespace App\Helpers;

use App\Http\Controllers\Controller;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class AuthHelper extends Controller{

    private $url;

    private $client;

    public function __construct()
    {
        $this->url = env('AUTH_URL', 'http://pln-marketplace-gandalf-development:8080');

        $this->client = new Client();
    }

    public function publicService($uri_path, $data = [], $headers = [])
    {

        try{
            $res = $this->client->request('POST' ,$this->url . '/auth/' .$uri_path, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            
            return $res->getBody();
        }catch(RequestException $e) {
            Log::warning('Request to Auth server failed.', json_decode($e->getResponse()->getBody(), TRUE) ?? array('Error From Request') );
            return false;
        } catch(ClientException $e) {
            Log::warning('Error From Client.', json_decode($e->getResponse()->getBody(), TRUE) ?? array('Error From Client') );
            return false;
        } catch(ServerException $e) {
            Log::warning('Error From Auth Server.', json_decode($e->getResponse()->getBody(), TRUE) ?? array('Error From Auth Server') );
            return false;
        } catch (Exception $e) {
            return false;
        }
        
    }
    
    public function privateService($uri_path, $data = [], $headers = [])
    {
        try{
            $res = $this->client->request('POST' ,$this->url . '/' . $uri_path, [
                'headers' => $headers,
                'form_params' => $data
            ]);

            return $res->getBody();
        }catch (ClientException $e){
            return response()->json(json_decode($e->getResponse()->getBody(), true), $e->getResponse()->getStatusCode());
        }
    }

}
