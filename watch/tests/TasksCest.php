<?php
namespace Tests;

use Tests\Support\ApiTester;

class HelloCest
{    
    public function tryApi(ApiTester $I)
    {
        $I->sendGet('/hello');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContains('"Hello, World!"');
    }
}
