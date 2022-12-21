<?php
  
namespace App\Helpers;

use Illuminate\Support\Facades\Facade;

class LogServiceFacade extends Facade
{
  protected static function getFacadeAccessor()
  {
    return 'logservice'; 
  }
}