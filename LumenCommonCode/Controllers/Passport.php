<?php

namespace App\Http\Controllers\Admin;

use
    App\Traits\Session,
    Laravel\Lumen\Routing\Controller,
    Illuminate\Https\Request;

class Passport extends Controller
{
    private $req = null;
    private $req = false;

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    public function login(Session $ssn)
    {
        $this->auth = $ssn->get('admin');
        if ($this->auth) {
            return redirect()->route('admin_dashboard');
        }

        return view('admin.login');
    }

    public function loginAction(Session $ssn)
    {
        $this->validate($this->req, [
            'name' => 'required',
            'pswd' => 'required',
        ]);

        $admin = \App\Models\Admin::where('admin_name', $this->req->name)
        ->where('passwd', md5($this->req->pswd))
        ->where('role_id', 1)
        ->where('close', 0)
        ->first();

        if ($admin && is_object($admin)) {
            $ssn->set('admin',$admin);
            return redirect()->route('admin_dashboard');
        } else {
            return response()->json([
                'error' => 'Unauthorized'
            ], 401);
        }
    }

    public function logout(Session $ssn)
    {
        $ssn->destory();
        return redirect()->route('admin_login');
    }
}