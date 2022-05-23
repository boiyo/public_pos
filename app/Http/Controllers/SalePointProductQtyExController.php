<?php

namespace App\Http\Controllers;

use Storage;
use App\Models\Sales;
use App\Models\SalePoint;
use App\Models\Brand;
use App\Models\Product;
use App\Models\IndexItem;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Excel\export;
use Rap2hpoutre\FastExcel\FastExcel;

use DB;

class SalePointProductQtyExController extends Controller
{
	function get_title(){
		$title = IndexItem::select('A.item_name AS main_name','index_item.item_name')
                          ->where('index_item.tree_level','=','1')
                          ->where('index_item.is_show','=','1')
						  ->where('index_item.file_name','=',explode('/',Route::getCurrentRoute()->uri())[0])
                          ->leftJoin('index_item AS A' ,'A.id', '=' ,'index_item.index_item_id')
						  ->get();
		return $title[0]->main_name.' - '.$title[0]->item_name;
    }
    public function index(Request $data){
		set_time_limit(0);
		//商品資訊
		$product_data = array();
		//門市資訊
		$row_data = array();
		//門市全數量
		$all_data = array();
		//存倉別
		$save_loccode = '';
		$where = [];
		$where[] = ['sale_point.is_noshow', '=', '0'];
		$where[] = ['stockmaster.is_invalid', '=', '0'];
		$wherein = '1';
		if($data->s_product_code != null) $where[] = ['stockmaster.stockid', 'like', "%".$data->s_product_code."%"];
		if($data->s_product_name != null) $where[] = ['stockmaster.description', 'like', "%".$data->s_product_name."%"];
		if($data->s_brand_id != null) $where[] = ['stockmaster.stock_brand_id', '=', $data->s_brand_id];
		if($data->s_original_code != null) $where[] = ['stockmaster.stock_original_code', 'like', "%".$data->s_original_code."%"];
		if($data->s_is_close != null) $where[] = ['stockmaster.is_invalid', '=', $data->s_is_close];
		if($data->ss_loc != null) $save_loccode .= "'".str_replace(",", "','",implode(',',$data->ss_loc))."',";
		if($data->p_loc != null) $save_loccode .= "'".str_replace(",", "','",implode(',',$data->p_loc))."',";
		if($data->r_loc != null) $save_loccode .= "'".str_replace(",", "','",implode(',',$data->r_loc))."',";
		if($save_loccode != ''){
			$wherein .= " AND locstock.loccode IN (".substr($save_loccode,0,-1).")";
		}else if($data->s_sale_point_id != null){
			$where[] = ['sale_point.id', '=', $data->s_sale_point_id];	
		}
		if(count(request()->all())>0){
			$temp_data = Product::where($where)
								->whereRaw($wherein)
								->leftJoin('prices',function($join){
										$join->on([ ['prices.stockid','=','stockmaster.stockid'],
													['prices.currabrev','=','stockmaster.currabrev'],
													['prices.typeabbrev','=',DB::raw("'01'")] ]);
								})
								->leftJoin('brand','brand.id','=','stockmaster.stock_brand_id')
								->leftJoin('locstock','locstock.stockid','=','stockmaster.stockid')
								->leftJoin('sale_point',function($join){
										$join->on('sale_point.locations_loccode','=','locstock.loccode')
											->oron('sale_point.return_loccode','=','locstock.loccode')
											->oron('sale_point.sample_loccode','=','locstock.loccode');
								})
								->leftJoinSub(Sales::leftJoin('salesdetails','salesdetails.sales_id','=','sales.id')
												->select('salesdetails.stock_id',DB::raw("SUM(salesdetails.quantity) AS pre_qty"))
												->whereRaw("sales.is_return = '0' AND sales.sale_type = '2'")
												->groupBy('salesdetails.stock_id')
												->toSql(),'pre',function($join){
													$join->on('pre.stock_id','=','stockmaster.stockid');
												}
								)
								->select('stockmaster.stockid','stockmaster.description','brand.name','brand.code','prices.price',
										'stockmaster.stock_original_name','stockmaster.stock_original_code','stockmaster.is_inventory',
										DB::raw("(	SELECT original_sales_price
													FROM stock_prices_import_details
													WHERE effective_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_sales_price IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_sales_price,
												(	SELECT original_purch_price
													FROM stock_prices_import_details
													WHERE effective_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_purch_price IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_purch_price,
												(	SELECT price
													FROM stock_purch_price
													WHERE stop_date >= '".date('Y-m-d')."' AND start_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND currabrev = 'TWD' AND price IS NOT NULL LIMIT 1) AS purch_price"),
										'pre.pre_qty','stockmaster.basic_order_qty','stockmaster.safe_loc_qty','sale_point.sale_point_name','locstock.quantity')
								->orderBy('stockmaster.stockid')
								->orderBy('sale_point.sale_point_code')
								->cursor();
			$product_str = '';
			$sp_str = '';
			$qty = 0;
			if(count($temp_data) > 0){
				foreach($temp_data as $k => $v){
					if($v->is_inventory == 0) $v->quantity = 0;
					if($product_str != $v->stockid && $k != 0){
						//庫存條件時判斷
						if($data->s_loc == 1 && $qty == 0){
							$all_data[$sp_str] -= $qty;
							$all_data['小計'] -= $qty;
							unset($product_data[$product_str]);
						}else if($data->s_loc == 2 && ($qty < $data->from_qty || $qty > $data->to_qty)){
							foreach($product_data[$product_str] as $k3 => $v3){
								if($k3 == 'description' || $k3 == 'brand_name' || $k3 == 'stock_original_code' || $k3 == 'stock_original_name'
								|| $k3 == 'basic_order_qty' || $k3 == 'safe_loc_qty' || $k3 == 'original_sales_price' || $k3 == 'original_purch_price'
								|| $k3 == 'purch_price' || $k3 == 'price' || $k3 == 'pre_qty') continue;
								$all_data[$k3] -= $v3;
							}
							unset($product_data[$product_str]);
							unset($all_data[$sp_str]);
						}
						$qty = 0;
					}
					//商品資訊資料
					$product_data[$v->stockid]['description'] = $v->description;
					$product_data[$v->stockid]['brand_name'] = $v->code.':'.$v->name;
					$product_data[$v->stockid]['stock_original_code'] = $v->stock_original_code;
					$product_data[$v->stockid]['stock_original_name'] = $v->stock_original_name;
					$product_data[$v->stockid]['basic_order_qty'] = $v->basic_order_qty;
					$product_data[$v->stockid]['safe_loc_qty'] = $v->safe_loc_qty;
					$product_data[$v->stockid]['original_sales_price'] = $v->original_sales_price;
					$product_data[$v->stockid]['original_purch_price'] = $v->original_purch_price;
					$product_data[$v->stockid]['purch_price'] = $v->purch_price;
					$product_data[$v->stockid]['price'] = $v->price;
					$product_data[$v->stockid]['pre_qty'] = $v->pre_qty;
					if(!isset($product_data[$v->stockid][$v->sale_point_name])) $product_data[$v->stockid][$v->sale_point_name] = 0;
					$product_data[$v->stockid][$v->sale_point_name] += $v->quantity;
					if(!isset($product_data[$v->stockid]['小計'])) $product_data[$v->stockid]['小計'] = 0;
					$product_data[$v->stockid]['小計'] += $v->quantity;

					if(!isset($all_data[$v->sale_point_name])) $all_data[$v->sale_point_name] = 0;
					$all_data[$v->sale_point_name] += $v->quantity;
					if(!isset($all_data['小計'])) $all_data['小計'] = 0;
					$all_data['小計'] += $v->quantity;
					//門市資訊資料
					$row_data[$v->sale_point_name] = '';
					$product_str = $v->stockid;
					$sp_str = $v->sale_point_name;
					$qty += $v->quantity;
				}
				unset($temp_data);
				if(count($product_data) > 0){
					//庫存條件時判斷
					if($data->s_loc == 1 && $qty == 0){
						if(isset($product_data[$product_str])){
							$all_data[$sp_str] -= $qty;
							$all_data['小計'] -= $qty;
						}
						unset($product_data[$product_str]);
					}else if($data->s_loc == 2 && ($qty < $data->from_qty || $qty > $data->to_qty)){
						if(isset($product_data[$product_str])){
							foreach($product_data[$product_str] as $k3 => $v3){
								if($k3 == 'description' || $k3 == 'brand_name' || $k3 == 'stock_original_code' || $k3 == 'stock_original_name'
								|| $k3 == 'basic_order_qty' || $k3 == 'safe_loc_qty' || $k3 == 'original_sales_price' || $k3 == 'original_purch_price'
								|| $k3 == 'purch_price' || $k3 == 'price' || $k3 == 'pre_qty') continue;
								$all_data[$k3] -= $v3;
							}
						}
						unset($product_data[$product_str]);
						unset($all_data[$sp_str]);
					}
					$row_data['小計'] = '';
				}
			}
		}
		// echo count($product_data).'===<br>';
		// var_dump($all_data);
		// dd($row_data);
		return view('manage.SalePointProductQtyEx', ['title' => SalePointProductQtyExController::get_title(),
					'item_id' => 'SalePointProductQtyEx',
					'userid' => session('userid'),
					'username' => session('username'),
					'modeldata' => session('modeldata'),
					'workdata' => session('workdata'),
					'sale_point_data' => SalePoint::where('is_noshow','=','0')->select('id','sale_point_code','sale_point_name')->orderBy('sale_point_code')->get(),
					'brand_data' => Brand::orderBy('code')->get(),
					'user_loc_data' => SalePoint::where('is_noshow','=','0')
												->leftJoin('locations AS S','S.loccode','=','sale_point.locations_loccode')
												->leftJoin('locations AS P','P.loccode','=','sale_point.sample_loccode')
												->leftJoin('locations AS R','R.loccode','=','sale_point.return_loccode')
												->select('sale_point.id','sale_point.sale_point_name','S.loccode AS S_loc','S.locationname AS S_locname',
														'P.loccode AS P_loc','P.locationname AS P_locname','R.loccode AS R_loc','R.locationname AS R_locname')
												->orderBy('sale_point.sale_point_code')->get(),
					'product_data' => $product_data,
					'all_data' => $all_data,
					'row_data' => $row_data ]);
    }
    public function export(Request $data){
		ini_set("memory_limit","1500M");
		set_time_limit(0);
		//商品資訊
		$product_data = array();
		//門市資訊
		$row_data = array();
		//門市全數量
		$all_data = array();
		//存倉別
		$save_loccode = '';
		$where = [];
		$where[] = ['sale_point.is_noshow', '=', '0'];
		$where[] = ['stockmaster.is_invalid', '=', '0'];
		$wherein = '1';
		if($data->s_product_code != null) $where[] = ['stockmaster.stockid', 'like', "%".$data->s_product_code."%"];
		if($data->s_product_name != null) $where[] = ['stockmaster.description', 'like', "%".$data->s_product_name."%"];
		if($data->s_brand_id != null) $where[] = ['stockmaster.stock_brand_id', '=', $data->s_brand_id];
		if($data->s_original_code != null) $where[] = ['stockmaster.stock_original_code', 'like', "%".$data->s_original_code."%"];
		if($data->s_is_close != null) $where[] = ['stockmaster.is_invalid', '=', $data->s_is_close];
		if($data->ss_loc != null) $save_loccode .= "'".str_replace(",", "','",implode(',',$data->ss_loc))."',";
		if($data->p_loc != null) $save_loccode .= "'".str_replace(",", "','",implode(',',$data->p_loc))."',";
		if($data->r_loc != null) $save_loccode .= "'".str_replace(",", "','",implode(',',$data->r_loc))."',";
		if($save_loccode != ''){
			$wherein .= " AND locstock.loccode IN (".substr($save_loccode,0,-1).")";
		}else if($data->s_sale_point_id != null){
			$where[] = ['sale_point.id', '=', $data->s_sale_point_id];	
		}
		$now_date = date('YmdHis');
		$temp = [];
		$excel_name = 'SalePointProductQtyEx_'.$now_date;
		$startTime = microtime(TRUE);
		if(count(request()->all())>0){
			$product_str = '';
			$sp_str = '';
			$qty = 0;
			$temp_data = Product::where($where)
								->whereRaw($wherein)
								->leftJoin('prices',function($join){
										$join->on([ ['prices.stockid','=','stockmaster.stockid'],
													['prices.currabrev','=','stockmaster.currabrev'],
													['prices.typeabbrev','=',DB::raw("'01'")] ]);
								})
								->leftJoin('brand','brand.id','=','stockmaster.stock_brand_id')
								->leftJoin('locstock','locstock.stockid','=','stockmaster.stockid')
								->leftJoin('sale_point',function($join){
										$join->on('sale_point.locations_loccode','=','locstock.loccode')
											->oron('sale_point.return_loccode','=','locstock.loccode')
											->oron('sale_point.sample_loccode','=','locstock.loccode');
								})
								->leftJoinSub(Sales::leftJoin('salesdetails','salesdetails.sales_id','=','sales.id')
												->select('salesdetails.stock_id',DB::raw("SUM(salesdetails.quantity) AS pre_qty"))
												->whereRaw("sales.is_return = '0' AND sales.sale_type = '2'")
												->groupBy('salesdetails.stock_id')
												->toSql(),'pre',function($join){
													$join->on('pre.stock_id','=','stockmaster.stockid');
												}
								)
								->select('stockmaster.stockid','stockmaster.description','brand.name','brand.code','prices.price',
										'stockmaster.stock_original_name','stockmaster.stock_original_code','stockmaster.is_inventory',
										DB::raw("(	SELECT original_sales_price
													FROM stock_prices_import_details
													WHERE effective_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_sales_price IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_sales_price,
												(	SELECT original_purch_price
													FROM stock_prices_import_details
													WHERE effective_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_purch_price IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_purch_price,
												(	SELECT price
													FROM stock_purch_price
													WHERE stop_date >= '".date('Y-m-d')."' AND start_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND currabrev = 'TWD' AND price IS NOT NULL LIMIT 1) AS purch_price"),
										'pre.pre_qty','stockmaster.basic_order_qty','stockmaster.safe_loc_qty','sale_point.sale_point_name','locstock.quantity')
								->orderBy('stockmaster.stockid')
								->orderBy('sale_point.sale_point_code')
								->cursor();
$endTime = microtime(TRUE);
		echo "相差時間為：".($endTime - $startTime)."<br>";
			if(count($temp_data) > 0){
				// while(list($key,$value) = each($temp_data)){
					// dd($key);
				foreach($temp_data as $k => $v){
					if($v->is_inventory == 0) $v->quantity = 0;
					if($product_str != $v->stockid && $k != 0){
						// 庫存條件時判斷
						if($data->s_loc == 1 && $qty == 0){
							$all_data[$sp_str] -= $qty;
							$all_data['小計'] -= $qty;
							unset($product_data[$product_str]);
						}else if($data->s_loc == 2 && ($qty < $data->from_qty || $qty > $data->to_qty)){
							foreach($product_data[$product_str] as $k3 => $v3){
								if($k3 == 'description' || $k3 == 'brand_name' || $k3 == 'stock_original_code' || $k3 == 'stock_original_name'
								|| $k3 == 'basic_order_qty' || $k3 == 'safe_loc_qty' || $k3 == 'original_sales_price' || $k3 == 'original_purch_price'
								|| $k3 == 'purch_price' || $k3 == 'price' || $k3 == 'pre_qty') continue;
								$all_data[$k3] -= $v3;
							}
							unset($product_data[$product_str]);
						}
						$qty = 0;
					}
					//商品資訊資料
					$product_data[$v->stockid]['description'] = $v->description;
					$product_data[$v->stockid]['brand_name'] = $v->code.':'.$v->name;
					$product_data[$v->stockid]['stock_original_code'] = $v->stock_original_code;
					$product_data[$v->stockid]['stock_original_name'] = $v->stock_original_name;
					$product_data[$v->stockid]['basic_order_qty'] = $v->basic_order_qty;
					$product_data[$v->stockid]['safe_loc_qty'] = $v->safe_loc_qty;
					$product_data[$v->stockid]['original_sales_price'] = $v->original_sales_price;
					$product_data[$v->stockid]['original_purch_price'] = $v->original_purch_price;
					$product_data[$v->stockid]['purch_price'] = $v->purch_price;
					$product_data[$v->stockid]['price'] = $v->price;
					$product_data[$v->stockid]['pre_qty'] = $v->pre_qty;
					if(!isset($product_data[$v->stockid][$v->sale_point_name])) $product_data[$v->stockid][$v->sale_point_name] = 0;
					$product_data[$v->stockid][$v->sale_point_name] += $v->quantity;
					if(!isset($product_data[$v->stockid]['小計'])) $product_data[$v->stockid]['小計'] = 0;
					$product_data[$v->stockid]['小計'] += $v->quantity;
	
					if(!isset($all_data[$v->sale_point_name])) $all_data[$v->sale_point_name] = 0;
					$all_data[$v->sale_point_name] += $v->quantity;
					if(!isset($all_data['小計'])) $all_data['小計'] = 0;
					$all_data['小計'] += $v->quantity;
					//門市資訊資料
					$row_data[$v->sale_point_name] = '';
					$product_str = $v->stockid;
					$sp_str = $v->sale_point_name;
					$qty += $v->quantity;
				}
				unset($temp_data);
				//庫存條件時判斷,有存在變數在減去,避免重複減
				if($data->s_loc == 1 && $qty == 0){
					if(isset($product_data[$product_str])){
						$all_data[$sp_str] -= $qty;
						$all_data['小計'] -= $qty;
					}
					unset($product_data[$product_str]);
				}else if($data->s_loc == 2 && ($qty < $data->from_qty || $qty > $data->to_qty)){
					if(isset($product_data[$product_str])){
						foreach($product_data[$product_str] as $k3 => $v3){
							if($k3 == 'description' || $k3 == 'brand_name' || $k3 == 'stock_original_code' || $k3 == 'stock_original_name'
							|| $k3 == 'basic_order_qty' || $k3 == 'safe_loc_qty' || $k3 == 'original_sales_price' || $k3 == 'original_purch_price'
							|| $k3 == 'purch_price' || $k3 == 'price' || $k3 == 'pre_qty') continue;
							$all_data[$k3] -= $v3;
						}
					}
					unset($product_data[$product_str]);
				}
				$row_data['小計'] = '';
				foreach($product_data as $k => $v){
					$merge_key = array('商品代碼','商品名稱','商品原文代碼','商品原文名稱','品牌','日幣銷售價(含稅)','日幣採購價(含稅)','台幣採購價','售價(台幣)','基本訂購量','安全庫存量','客訂數量');
					$merge_value = array($k,$v['description'],$v['stock_original_code'],$v['stock_original_name'],$v['brand_name'],$v['original_sales_price'],$v['original_purch_price'],(int)$v['purch_price'],(int)$v['price'],$v['basic_order_qty'],$v['safe_loc_qty'],(int)$v['pre_qty']);
					foreach($row_data as $k2 => $v2){
						$merge_key[] = $k2;
						$merge_value[] = $v[$k2];
					}
					$temp[] = (object)array_combine($merge_key, $merge_value);
					unset($merge_key);
					unset($merge_value);
				}
				if(count($product_data) > 0){
					$merge_key = array('商品代碼','商品名稱','商品原文代碼','商品原文名稱','品牌','日幣銷售價(含稅)','日幣採購價(含稅)','台幣採購價','售價(台幣)','基本訂購量','安全庫存量','客訂數量');
					$merge_value = array('','','','','','','','','','','','小計');
					foreach($row_data as $k2 => $v2){
						array_push($merge_key,$k2);
						array_push($merge_value,$all_data[$k2]);
					}
					$temp[] = (object)array_combine($merge_key, $merge_value);
					unset($merge_key);
					unset($merge_value);
				}
			}
		}
		unset($product_data);
		unset($row_data);
		unset($all_data);
		$temp = collect($temp);
		ob_clean();
// $endTime = microtime(TRUE);
		// echo memory_get_usage().'=A=<br>';
		// echo "相差時間為：".($endTime - $startTime)."<br>";
// return;
		return (new FastExcel($temp))->download($excel_name.'.xlsx');
    }

}