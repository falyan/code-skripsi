<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Version\VersionQueries;
use Exception;

class VersionController extends Controller
{
    private $versionQuery;

    public function __construct()
    {
        $this->versionQuery = new VersionQueries();
    }

    public function getVersionStatus()
    {
        try {
            return $this->versionQuery->getVersionStatus();
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}
