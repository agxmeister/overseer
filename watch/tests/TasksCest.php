<?php
namespace Tests;

use Tests\Support\ApiTester;

class TasksCest
{    
    public function tryApi(ApiTester $I)
    {
        $I->sendGet('/tasks');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }
}
