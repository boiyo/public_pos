<?php

use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'IndexController@index')->middleware('login');
//登入及登出
Route::post('login', 'LoginController@login');
Route::get('login', 'LoginController@showLoginPage');
Route::get('logout', 'LoginController@logout');
//api json取資料
Route::get('api/Member/{type}/{id?}', 'api\MemberController@show')->middleware('login');
Route::get('api/ProductHistory/{type}', 'api\ProductHistoryController@show')->middleware('login');
Route::get('api/Product', 'api\ProductController@show')->middleware('login');
Route::get('api/Address/{county?}/{city?}', 'api\AddressController@show')->middleware('login');
Route::get('api/PurchOrder/{type}/{id}', 'api\PurchOrderController@show')->middleware('login');
Route::get('api/SalesDay/{date}/{loccode}/{stockid}/{qty}', 'api\SalesDayController@show')->middleware('login');

Route::get('download/{filename}', function($filename){
    $file_path = storage_path() .'/'. $filename;
    if(file_exists($file_path)){
        return Response::download($file_path, $filename, ['Content-Length: '. filesize($file_path)]);
    }else{
        exit('參考檔案 '.$filename.' 不存在!');
    }
});
Route::group(['middleware' => ['login']], function() {
	if(isset($_SERVER['REQUEST_URI'])){
		$temp = explode('/',$_SERVER['REQUEST_URI']);
		$temp = explode('?',$temp[1]);
		$url = $temp[0];
		//echo $url.'='.$_SERVER['REQUEST_METHOD'].'==';
		//echo "route=>".substr($_SERVER['REQUEST_URI'],1)."===<br>";
		if(preg_match('/^[a-z]+$/', substr($url,0,1)) && $url != 'logout'){
			Route::get('/'.$url, 'IndexController@index');
		}else if(preg_match('/^[A-Z]+$/', substr($url,0,1))){
			Route::get('/'.$url, $url.'Controller@index');

			Route::get('/'.$url.'/json/{id}', $url.'Controller@json_data');

			Route::get('/'.$url.'/create', $url.'Controller@create');
			Route::get('/'.$url.'/export', $url.'Controller@export');
			Route::get('/'.$url.'/pdf', $url.'Controller@pdf');

			Route::get('/'.$url.'/{id}/transfer', $url.'Controller@transfer');
			Route::get('/'.$url.'/{id}/transfer_out', $url.'Controller@transfer_out');
			Route::get('/'.$url.'/{id}/transfer_out_cancel', $url.'Controller@transfer_out_cancel');
			Route::get('/'.$url.'/{id}/transfer_in', $url.'Controller@transfer_in');
			Route::get('/'.$url.'/{id}/stock_adjust', $url.'Controller@stock_adjust');
			Route::get('/'.$url.'/{id}/cancel_stock_adjust', $url.'Controller@cancel_stock_adjust');
			Route::get('/'.$url.'/{id}/stock_check', $url.'Controller@stock_check');
			Route::get('/'.$url.'/{id}/material', $url.'Controller@material');
			Route::get('/'.$url.'/{id}/audit', $url.'Controller@audit');
			Route::get('/'.$url.'/{id}/close', $url.'Controller@close');
			Route::get('/'.$url.'/{id}/checkin', $url.'Controller@checkin');
			Route::get('/'.$url.'/{id}/cancel_checkin', $url.'Controller@cancel_checkin');

			Route::get('/'.$url.'/{id}/edit', $url.'Controller@edit');
			Route::get('/'.$url.'/{id}/export', $url.'Controller@one_export');

			//Route::get('/'.$url.'/{id}', $url.'Controller@destroy');
			Route::get('/'.$url.'/{id}', $url.'Controller@show');
			
			Route::post('/'.$url, $url.'Controller@store');
			Route::post('/'.$url.'/import', $url.'Controller@import');

			Route::put('/'.$url.'/{id?}', $url.'Controller@update');

			Route::delete('/'.$url.'/{id}', $url.'Controller@destroy');

			Route::get('/'.$url.'/pos_search/{items}/{id?}', $url.'Controller@items');
			Route::get('/'.$url.'/events/{events}/{main_id}/{details_id}', $url.'Controller@events');
			Route::get('/'.$url.'/status/{id}/{status}/{msg?}', $url.'Controller@status');
		}
	}
});

