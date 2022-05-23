<?php

namespace App\Http\Controllers\api;

use App\Models\Address;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function show($county=null,$city=null,Request $request)
    {
		if($county==null){
			return Address::select('county')->groupBy('county')->orderBy('county')->get();
		}else if($county != null && $city == null){
			return Address::select('county','city')->where('county','=',$county)
				->groupBy('county','city')->orderBy('county')->orderBy('city')->get();
		}else if($county != null && $city != null){
			return Address::select('zip')->where('county','=',$county)->where('city','=',$city)->get();
		}
    }
}
