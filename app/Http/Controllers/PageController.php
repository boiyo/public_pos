<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        $userid = session('userid');
        return view('index', ['userid' => $userid,
							  'modlenames' => ['POS前台','門市總管','門市後台','商品模組','發票模組','採購模組','促銷模組'] ]);
    }
}
