<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\AgentMasterData\AgentMasterDataQueries;
use Exception;
use Illuminate\Http\Request;

class AgentMasterDataController extends Controller
{
    protected $queries, $agentQueries;

    public function __construct()
    {
        $this->queries = new AgentMasterDataQueries();
    }

    public function getListTokenListrik(Request $request)
    {
        try {
            $limit = $request->limit ?? 15;
            $page = $request->page ?? 1;

            $data = $this->queries->getListTokenListrik($limit, $page);

            return $this->respondCustom([
                'message' => $data->count() > 0 ? 'success' : 'empty data',
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getAgentSbu()
    {
        try {
            $agent_sbu = $this->queries->getAgentSbu();

            return $this->respondWithData($agent_sbu, 'success');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }

    public function getAgentMitra()
    {
        try {
            $agent_mitra = $this->queries->getAgentMitra();

            return $this->respondWithData($agent_mitra, 'success');
        } catch (Exception $e) {
            return $this->respondErrorException($e, request());
        }
    }
}
