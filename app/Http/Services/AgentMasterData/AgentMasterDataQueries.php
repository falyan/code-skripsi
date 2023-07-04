<?php

namespace App\Http\Services\AgentMasterData;

use App\Http\Services\Service;
use App\Models\AgentMasterData;
use App\Models\AgentMasterMitra;
use App\Models\AgentMasterSbu;

class AgentMasterDataQueries extends Service
{
    public function getListTokenListrik($limit = 15, $page = 1)
    {
        $model = new AgentMasterData();
        $data = $model->status(1)->type('token_listrik')->orderBy('value', 'ASC')->paginate($limit);

        return $data;
    }

    public function getAgentSbu()
    {
        $agent_sbu = AgentMasterSbu::orderBy('id', 'asc')->get();

        return $agent_sbu;
    }

    public function getAgentMitra()
    {
        $agent_mitra = AgentMasterMitra::orderBy('id', 'asc')->get();

        return $agent_mitra;
    }
}
