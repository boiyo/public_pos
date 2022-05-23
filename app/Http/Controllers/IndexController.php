<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;


class IndexController extends Controller
{
    public function index(Request $request,$id=null)
    {
		$userid = session('userid');
		$username = session('username');
		$modeldata = session('modeldata');
		$workdata = session('workdata');
		$security_data = session('security_data');
		return view('index', ['id' => substr($_SERVER['REQUEST_URI'],1),
			'title' => '',
			'userid' => $userid,
			'username' => $username,
			'modeldata' => $modeldata,
			'workdata' => $workdata,
			'security_data' => $security_data ]);
    }
}
