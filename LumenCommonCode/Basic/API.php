<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller,
    Illuminate\Http\Request;

class API extends Controller
{
    protected $req      = null;
    protected $jwt_auth = null;

    public function __construct(Request $req)
    {
        $this->req = $req;
        $this->jwt_auth = $req->get('jwt_auth');
    }
}