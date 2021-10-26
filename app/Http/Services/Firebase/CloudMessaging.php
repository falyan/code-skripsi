<?php

use GuzzleHttp\Client;
use Kreait\Firebase\Messaging\CloudMessage;

class Notification
{
  public static function fireNotification()
  {
    $messaging = app('firebase.messaging');

    $message = CloudMessage::withTarget();
  }
}