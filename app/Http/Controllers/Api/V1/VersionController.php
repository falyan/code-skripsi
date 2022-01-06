<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Version\VersionQueries;
use Exception;

class VersionController extends Controller
{
    public function __construct()
    {
        $this->versionQuery = new VersionQueries();
    }

    public function getVersionStatus()
    {
        if (!$version = request()->get('version')) {
            return $this->respondWithResult(false, 'field version kosong', 400);
        }
        try {
            return $this->versionQuery->getVersionStatus($version);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}
