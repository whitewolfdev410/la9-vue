<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class AozoraController extends Controller
{
    public function test()
    {
        $response = Http::get('https://api.gmo-aozora.com/ganb/api/simulator/corporation/v1/accounts/balances');
        echo $response->body();
    }
}