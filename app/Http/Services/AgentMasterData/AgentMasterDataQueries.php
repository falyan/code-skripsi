<?php

namespace App\Http\Services\AgentMasterData;

use App\Http\Services\Service;
use App\Models\AgentMasterData;

class AgentMasterDataQueries extends Service
{
    public function getListTokenListrik($limit = 15, $page = 1)
    {
        $model = new AgentMasterData();
        $data = $model->status(1)->type('token_listrik')->orderBy('value', 'ASC')->paginate($limit);

        return $data;
    }
}
