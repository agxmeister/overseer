<?php
namespace Tests\Api;

use Tests\Support\ApiTester;

class TasksCest
{    
    public function getTasks(ApiTester $I): void
    {
        $I->sendGet('/tasks');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }
}
