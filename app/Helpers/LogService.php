<?php 

namespace App\Helpers;

use App\Models\ServiceLog;
use Exception;

class LogService
{
  protected $url          = '';
  protected $request      = null;
  protected $response     = null;
  protected $service_code = null;
  protected $category     = null;
  protected $isUpdate     = false;

  public function setUrl($url)
  {
    $this->url = $url;

    return $this;
  }

  public function setRequest($request)
  {
    $this->request = $request;

    return $this;
  }

  public function setResponse($response)
  {
    $this->response = $response;

    return $this;
  }

  public function setServiceCode($service_code)
  {
    $this->service_code = $service_code;

    return $this;
  }

  public function setCategory($category)
  {
    $this->category = $category;

    return $this;
  }

  public function isUpdate()
  {
    $this->isUpdate = true;

    return $this;
  }

  public function log()
  {
    try {
      $data = [
        'url'           => $this->url,
        'service_code'  => $this->service_code,
        'req'           => $this->request,
        'res'           => $this->response,
        'cat'           => $this->category
      ];

      ServiceLog::create($data);

    } catch (Exception $e) {
      // throw $e;
    }
  }
}