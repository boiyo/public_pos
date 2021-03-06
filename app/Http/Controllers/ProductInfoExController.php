<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Brand;
use App\Models\ProductCategory;
use App\Models\Sales;
use App\Models\Product;
use App\Models\IndexItem;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Excel\export;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\PhpWord;
use PDF;

use DB;

class ProductInfoExController extends Controller
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
		$where = [];
		$having = '';
		$wherein = '1';
		if($data->s_product_code != null) $where[] = ['stockmaster.stockid', 'like', "%".$data->s_product_code."%"];
		if($data->s_product_name != null) $where[] = ['stockmaster.description', 'like', "%".$data->s_product_name."%"];
		if($data->s_original_code != null) $where[] = ['stockmaster.stock_original_code', 'like', "%".$data->s_original_code."%"];
		if($data->s_original_name != null) $where[] = ['stockmaster.stock_original_name', 'like', "%".$data->s_original_name."%"];
		if($data->s_supplier_id != null){
			$supplier_id = str_replace(",", "','",$data->s_supplier_id);
			$wherein .= " AND stockmaster.stockid IN (SELECT stockid FROM stock_purch_suppliers WHERE supplierid IN ('".$supplier_id."'))";
		}
		if($data->s_product_catid != null){
			$product_catid = str_replace(",", "','",$data->s_product_catid);
			$wherein .= " AND stockmaster.categoryid IN ('".$product_catid."')";
		}
		if($data->s_brand_id != null) $where[] = ['stockmaster.stock_brand_id', '=', $data->s_brand_id];
		if($data->s_is_close != null) $where[] = ['stockmaster.is_invalid', '=', $data->s_is_close];
		if($data->s_loc == 1){
			$having = " qty <> 0 ";
		}else if($data->s_loc == 2){
			$having = " qty >= ".$data->from_qty." AND qty <= ".$data->to_qty;
		}
		if($data->s_loc == '') {
			$row_data = Product::where($where)
								->whereRaw($wherein)
								->leftJoin('color_master','color_master.id','=','stockmaster.colorid')
								->leftJoin('production_place','production_place.id','=','stockmaster.production_place_id')
								->leftJoin('stockcategory','stockcategory.categoryid','=','stockmaster.categoryid')
								->leftJoin('taxcategories','taxcategories.taxcatid','=','stockmaster.taxcatid')
								->leftJoin('prices',function($join){
										$join->on([ ['prices.stockid','=','stockmaster.stockid'],
													['prices.currabrev','=','stockmaster.currabrev'],
													['prices.typeabbrev','=',DB::raw("'01'")] ]);
								})
								->leftJoin('unitsofmeasure','unitsofmeasure.id','=','stockmaster.use_units')
								->leftJoin('brand','brand.id','=','stockmaster.stock_brand_id')
								->select('stockmaster.stockid','stockmaster.international_barcode','stockmaster.description',
										'stockcategory.categoryid','stockcategory.categorydescription',
										'unitsofmeasure.unit_name','stockmaster.taxcatid','taxcategories.taxcatname',
										'prices.price AS de_price',
										'stockmaster.safe_loc_qty','stockmaster.basic_order_qty',
										DB::raw('CASE	WHEN (stockmaster.cost_type = "1") THEN "??????"
														WHEN (stockmaster.cost_type = "2") THEN "??????"
														WHEN (stockmaster.cost_type = "3") THEN "??????"
														WHEN (stockmaster.cost_type = "4") THEN "??????"
												ELSE "???"
												END AS cost_type'),
										'stockmaster.stock_original_name','stockmaster.stock_original_code',
										DB::raw('CONCAT(brand.code,":",brand.name) AS brand_name,
												(SELECT SUM(quantity) FROM locstock WHERE locstock.stockid = stockmaster.stockid GROUP BY stockid) qty '),
										'stockmaster.english_name AS english_name',
										'stockmaster.stock_spec','stockmaster.stock_material_id',
										'color_master.name AS color_name','production_place.place_name',
										'stockmaster.dispurchase_date',
										'stockmaster.invoice_description',
										DB::raw("(	SELECT group_concat(suppliers.supplierid,':',suppliers.suppname ORDER BY suppliers.supplierid SEPARATOR ',')
													FROM stock_purch_suppliers 
													LEFT JOIN suppliers ON suppliers.supplierid = stock_purch_suppliers.supplierid
													WHERE stock_purch_suppliers.stockid = stockmaster.stockid
													AND stock_purch_suppliers.deleted = 0 ) AS show_suppname,
												(	SELECT price
													FROM stock_purch_price
													WHERE stop_date >= '".date('Y-m-d')."' AND start_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND currabrev = 'TWD' AND price IS NOT NULL LIMIT 1) AS purch_price,
												(	SELECT original_sales_price
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
												CASE	WHEN (stockmaster.is_inventory = '1') THEN '???'
														ELSE '???'
														END AS is_inventory,
												CASE	WHEN (stockmaster.is_no_accumulated_amount = '1') THEN '???'
														ELSE '???'
														END AS is_no_accumulated_amount,
												CASE	WHEN (stockmaster.is_free_price = '1') THEN '???'
														ELSE '???'
														END AS is_free_price,
												(	SELECT original_sales_price_notax
													FROM stock_prices_import_details
													WHERE effective_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_sales_price_notax IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_sales_price_notax,
												(	SELECT original_purch_price_notax
													FROM stock_prices_import_details
													WHERE effective_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_purch_price_notax IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_purch_price_notax,
												CASE	WHEN (stockmaster.is_no_discount = '1') THEN '???'
														ELSE '???'
														END AS is_no_discount,
												CASE	WHEN (stockmaster.is_invalid = '1') THEN '???'
														ELSE '???'
														END AS is_invalid"))
								->orderBy('stockmaster.stockid')
								->paginate(session('max_page'))->appends($data->input());
		}else{
			$row_data = Product::where($where)
								->whereRaw($wherein)
								->leftJoin('color_master','color_master.id','=','stockmaster.colorid')
								->leftJoin('production_place','production_place.id','=','stockmaster.production_place_id')
								->leftJoin('stockcategory','stockcategory.categoryid','=','stockmaster.categoryid')
								->leftJoin('taxcategories','taxcategories.taxcatid','=','stockmaster.taxcatid')
								->leftJoin('prices',function($join){
										$join->on([ ['prices.stockid','=','stockmaster.stockid'],
													['prices.currabrev','=','stockmaster.currabrev'],
													['prices.typeabbrev','=',DB::raw("'01'")] ]);
								})
								->leftJoin('unitsofmeasure','unitsofmeasure.id','=','stockmaster.use_units')
								->leftJoin('brand','brand.id','=','stockmaster.stock_brand_id')
								->select('stockmaster.stockid','stockmaster.international_barcode','stockmaster.description',
										'stockcategory.categoryid','stockcategory.categorydescription',
										'unitsofmeasure.unit_name','stockmaster.taxcatid','taxcategories.taxcatname',
										'prices.price AS de_price',
										'stockmaster.safe_loc_qty','stockmaster.basic_order_qty',
										DB::raw('CASE	WHEN (stockmaster.cost_type = "1") THEN "??????"
														WHEN (stockmaster.cost_type = "2") THEN "??????"
														WHEN (stockmaster.cost_type = "3") THEN "??????"
														WHEN (stockmaster.cost_type = "4") THEN "??????"
												ELSE "???"
												END AS cost_type'),
										'stockmaster.stock_original_name','stockmaster.stock_original_code',
										DB::raw('CONCAT(brand.code,":",brand.name) AS brand_name,
												(SELECT SUM(quantity) FROM locstock WHERE locstock.stockid = stockmaster.stockid GROUP BY stockid) qty '),
										'stockmaster.english_name AS english_name',
										'stockmaster.stock_spec','stockmaster.stock_material_id',
										'color_master.name AS color_name','production_place.place_name',
										'stockmaster.dispurchase_date',
										'stockmaster.invoice_description',
										DB::raw("(	SELECT group_concat(suppliers.supplierid,':',suppliers.suppname ORDER BY suppliers.supplierid SEPARATOR ',')
													FROM stock_purch_suppliers 
													LEFT JOIN suppliers ON suppliers.supplierid = stock_purch_suppliers.supplierid
													WHERE stock_purch_suppliers.stockid = stockmaster.stockid
													AND stock_purch_suppliers.deleted = 0 ) AS show_suppname,
												(	SELECT price
													FROM stock_purch_price
													WHERE stop_date >= '".date('Y-m-d')."' AND start_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND currabrev = 'TWD' AND price IS NOT NULL LIMIT 1) AS purch_price,
												(	SELECT original_sales_price
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
												CASE	WHEN (stockmaster.is_inventory = '1') THEN '???'
														ELSE '???'
														END AS is_inventory,
												CASE	WHEN (stockmaster.is_no_accumulated_amount = '1') THEN '???'
														ELSE '???'
														END AS is_no_accumulated_amount,
												CASE	WHEN (stockmaster.is_free_price = '1') THEN '???'
														ELSE '???'
														END AS is_free_price,
												(	SELECT original_sales_price_notax
													FROM stock_prices_import_details
													WHERE effective_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_sales_price_notax IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_sales_price_notax,
												(	SELECT original_purch_price_notax
													FROM stock_prices_import_details
													WHERE effective_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_purch_price_notax IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_purch_price_notax,
												CASE	WHEN (stockmaster.is_no_discount = '1') THEN '???'
														ELSE '???'
														END AS is_no_discount,
												CASE	WHEN (stockmaster.is_invalid = '1') THEN '???'
														ELSE '???'
														END AS is_invalid"))
								->havingRaw($having)
								->orderBy('stockmaster.stockid')
								->paginate(session('max_page'))->appends($data->input());
		}
		return view('manage.ProductInfoEx', ['title' => ProductInfoExController::get_title(),
					'item_id' => 'ProductInfoEx',
					'userid' => session('userid'),
					'username' => session('username'),
					'modeldata' => session('modeldata'),
					'workdata' => session('workdata'),
					'supplier_data' => Supplier::get(),
					'brand_data' => Brand::orderBy('code')->get(),
					'product_catid_data' => ProductCategory::selectRaw("categoryid,CONCAT(REPEAT('???',cat_rank-1),categoryid) AS show_categoryid,categorydescription")->get(),
					'row_data' => $row_data ]);
    }
    public function export(Request $data){
		ini_set("memory_limit","1300M");
		set_time_limit(0);
		$where = [];
		$having = '';
		$wherein = '1';
		if($data->s_product_code != null) $where[] = ['stockmaster.stockid', 'like', "%".$data->s_product_code."%"];
		if($data->s_product_name != null) $where[] = ['stockmaster.description', 'like', "%".$data->s_product_name."%"];
		if($data->s_original_code != null) $where[] = ['stockmaster.stock_original_code', 'like', "%".$data->s_original_code."%"];
		if($data->s_original_name != null) $where[] = ['stockmaster.stock_original_name', 'like', "%".$data->s_original_name."%"];
		if($data->s_supplier_id != null){
			$supplier_id = str_replace(",", "','",$data->s_supplier_id);
			$wherein .= " AND stockmaster.stockid IN (SELECT stockid FROM stock_purch_suppliers WHERE supplierid IN ('".$supplier_id."'))";
		}
		if($data->s_product_catid != null){
			$product_catid = str_replace(",", "','",$data->s_product_catid);
			$wherein .= " AND stockmaster.categoryid IN ('".$product_catid."')";
		}
		if($data->s_brand_id != null) $where[] = ['stockmaster.stock_brand_id', '=', $data->s_brand_id];
		if($data->s_is_close != null) $where[] = ['stockmaster.is_invalid', '=', $data->s_is_close];
		if($data->s_loc == 1){
			$having = " qty <> 0 ";
		}else if($data->s_loc == 2){
			$having = " qty >= ".$data->from_qty." AND qty <= ".$data->to_qty;
		}
		if($data->s_loc == '') {
			$temp_data = Product::where($where)
								->whereRaw($wherein)
								->leftJoin('color_master','color_master.id','=','stockmaster.colorid')
								->leftJoin('production_place','production_place.id','=','stockmaster.production_place_id')
								->leftJoin('stockcategory','stockcategory.categoryid','=','stockmaster.categoryid')
								->leftJoin('taxcategories','taxcategories.taxcatid','=','stockmaster.taxcatid')
								->leftJoin('prices',function($join){
										$join->on([ ['prices.stockid','=','stockmaster.stockid'],
													['prices.currabrev','=','stockmaster.currabrev'],
													['prices.typeabbrev','=',DB::raw("'01'")] ]);
								})
								->leftJoin('unitsofmeasure','unitsofmeasure.id','=','stockmaster.use_units')
								->leftJoin('brand','brand.id','=','stockmaster.stock_brand_id')
								->select('stockmaster.stockid','stockmaster.international_barcode','stockmaster.description',
										'stockcategory.categoryid','stockcategory.categorydescription',
										'unitsofmeasure.unit_name','stockmaster.taxcatid','taxcategories.taxcatname',
										'prices.price AS de_price',
										'stockmaster.safe_loc_qty','stockmaster.basic_order_qty',
										DB::raw('CASE	WHEN (stockmaster.cost_type = "1") THEN "??????"
														WHEN (stockmaster.cost_type = "2") THEN "??????"
														WHEN (stockmaster.cost_type = "3") THEN "??????"
														WHEN (stockmaster.cost_type = "4") THEN "??????"
												ELSE "???"
												END AS cost_type'),
										'stockmaster.stock_original_name','stockmaster.stock_original_code',
										DB::raw('CONCAT(brand.code,":",brand.name) AS brand_name,
												(SELECT SUM(quantity) FROM locstock WHERE locstock.stockid = stockmaster.stockid GROUP BY stockid) qty '),
										'stockmaster.english_name AS english_name',
										'stockmaster.stock_spec','stockmaster.stock_material_id',
										'color_master.name AS color_name','production_place.place_name',
										'stockmaster.dispurchase_date',
										'stockmaster.invoice_description',
										DB::raw("(	SELECT group_concat(suppliers.supplierid,':',suppliers.suppname ORDER BY suppliers.supplierid SEPARATOR ',')
													FROM stock_purch_suppliers 
													LEFT JOIN suppliers ON suppliers.supplierid = stock_purch_suppliers.supplierid
													WHERE stock_purch_suppliers.stockid = stockmaster.stockid
													AND stock_purch_suppliers.deleted = 0 ) AS show_suppname,
												(	SELECT price
													FROM stock_purch_price
													WHERE stop_date >= '".date('Y-m-d')."' AND start_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND currabrev = 'TWD' AND price IS NOT NULL LIMIT 1) AS purch_price,
												(	SELECT original_sales_price
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
												CASE	WHEN (stockmaster.is_inventory = '1') THEN '???'
														ELSE '???'
														END AS is_inventory,
												CASE	WHEN (stockmaster.is_no_accumulated_amount = '1') THEN '???'
														ELSE '???'
														END AS is_no_accumulated_amount,
												CASE	WHEN (stockmaster.is_free_price = '1') THEN '???'
														ELSE '???'
														END AS is_free_price,
												(	SELECT original_sales_price_notax
													FROM stock_prices_import_details
													WHERE effective_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_sales_price_notax IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_sales_price_notax,
												(	SELECT original_purch_price_notax
													FROM stock_prices_import_details
													WHERE effective_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_purch_price_notax IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_purch_price_notax,
												CASE	WHEN (stockmaster.is_no_discount = '1') THEN '???'
														ELSE '???'
														END AS is_no_discount,
												CASE	WHEN (stockmaster.is_invalid = '1') THEN '???'
														ELSE '???'
														END AS is_invalid"))
								->orderBy('stockmaster.stockid')
								->get();
		}else{
			$temp_data = Product::where($where)
								->whereRaw($wherein)
								->leftJoin('color_master','color_master.id','=','stockmaster.colorid')
								->leftJoin('production_place','production_place.id','=','stockmaster.production_place_id')
								->leftJoin('stockcategory','stockcategory.categoryid','=','stockmaster.categoryid')
								->leftJoin('taxcategories','taxcategories.taxcatid','=','stockmaster.taxcatid')
								->leftJoin('prices',function($join){
										$join->on([ ['prices.stockid','=','stockmaster.stockid'],
													['prices.currabrev','=','stockmaster.currabrev'],
													['prices.typeabbrev','=',DB::raw("'01'")] ]);
								})
								->leftJoin('unitsofmeasure','unitsofmeasure.id','=','stockmaster.use_units')
								->leftJoin('brand','brand.id','=','stockmaster.stock_brand_id')
								->select('stockmaster.stockid','stockmaster.international_barcode','stockmaster.description',
										'stockcategory.categoryid','stockcategory.categorydescription',
										'unitsofmeasure.unit_name','stockmaster.taxcatid','taxcategories.taxcatname',
										'prices.price AS de_price',
										'stockmaster.safe_loc_qty','stockmaster.basic_order_qty',
										DB::raw('CASE	WHEN (stockmaster.cost_type = "1") THEN "??????"
														WHEN (stockmaster.cost_type = "2") THEN "??????"
														WHEN (stockmaster.cost_type = "3") THEN "??????"
														WHEN (stockmaster.cost_type = "4") THEN "??????"
												ELSE "???"
												END AS cost_type'),
										'stockmaster.stock_original_name','stockmaster.stock_original_code',
										DB::raw('CONCAT(brand.code,":",brand.name) AS brand_name,
												(SELECT SUM(quantity) FROM locstock WHERE locstock.stockid = stockmaster.stockid GROUP BY stockid) qty '),
										'stockmaster.english_name AS english_name',
										'stockmaster.stock_spec','stockmaster.stock_material_id',
										'color_master.name AS color_name','production_place.place_name',
										'stockmaster.dispurchase_date',
										'stockmaster.invoice_description',
										DB::raw("(	SELECT group_concat(suppliers.supplierid,':',suppliers.suppname ORDER BY suppliers.supplierid SEPARATOR ',')
													FROM stock_purch_suppliers 
													LEFT JOIN suppliers ON suppliers.supplierid = stock_purch_suppliers.supplierid
													WHERE stock_purch_suppliers.stockid = stockmaster.stockid
													AND stock_purch_suppliers.deleted = 0 ) AS show_suppname,
												(	SELECT price
													FROM stock_purch_price
													WHERE stop_date >= '".date('Y-m-d')."' AND start_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND currabrev = 'TWD' AND price IS NOT NULL LIMIT 1) AS purch_price,
												(	SELECT original_sales_price
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
												CASE	WHEN (stockmaster.is_inventory = '1') THEN '???'
														ELSE '???'
														END AS is_inventory,
												CASE	WHEN (stockmaster.is_no_accumulated_amount = '1') THEN '???'
														ELSE '???'
														END AS is_no_accumulated_amount,
												CASE	WHEN (stockmaster.is_free_price = '1') THEN '???'
														ELSE '???'
														END AS is_free_price,
												(	SELECT original_sales_price_notax
													FROM stock_prices_import_details
													WHERE effective_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_sales_price_notax IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_sales_price_notax,
												(	SELECT original_purch_price_notax
													FROM stock_prices_import_details
													WHERE effective_date <= '".date('Y-m-d')."'
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_purch_price_notax IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_purch_price_notax,
												CASE	WHEN (stockmaster.is_no_discount = '1') THEN '???'
														ELSE '???'
														END AS is_no_discount,
												CASE	WHEN (stockmaster.is_invalid = '1') THEN '???'
														ELSE '???'
														END AS is_invalid"))
								->havingRaw($having)
								->orderBy('stockmaster.stockid')
								->get();
		}
		$now_date = date('YmdHis');
		$all_qty = 0;
		$row_data = [];
		$temp = [];
		foreach($temp_data as $k => $v){
			$all_qty += round($v['qty']);
			$temp[] = ['????????????' => (string)$v['stockid'],
						'????????????' => (string)$v['description'],
						'??????????????????' => (string)$v['stock_original_code'],
						'??????????????????' => (string)$v['stock_original_name'],
						'????????????' => (string)$v['english_name'],
						'??????' => (string)$v['brand_name'],
						'????????????' => (string)$v['categorydescription'],
						'??????' => (string)$v['unit_name'],
						'????????????' => (string)$v['stock_spec'],
						'??????' => (string)$v['stock_material_id'],
						'????????????' => (string)$v['taxcatname'],
						'????????????' => (string)$v['cost_type'],
						'???????????????' => round($v['basic_order_qty']),
						'???????????????' => round($v['purch_price']),
						'???????????????' => round($v['original_sales_price']),
						'???????????????' => round($v['original_purch_price']),
						'???????????????' => round($v['safe_loc_qty']),
						'??????' => round($v['qty']),
						'??????' => round($v['de_price']),
						'??????' => (string)$v['color_name'],
						'??????' => (string)$v['place_name'],
						'???????????????' => (string)$v['oldstockid'],
						'?????????' => (string)$v['show_suppname'],
						'????????????' => (string)$v['is_inventory'],
						'????????????????????????' => (string)$v['is_no_accumulated_amount'],
						'????????????' => (string)$v['is_free_price'],
						'???????????????(??????)' => round($v['original_sales_price_notax']),
						'???????????????(??????)' => round($v['original_purch_price_notax']),
						'???????????????' => (string)$v['dispurchase_date'],
						'???????????????' => (string)$v['is_no_discount'],
						'????????????' => (string)$v['is_invalid'],
						'????????????' => (string)$v['invoice_description'] ];
		}
		if(count($temp_data) > 0){
			$temp[] = ['????????????' => '',
						'????????????' => '',
						'??????????????????' => '',
						'??????????????????' => '',
						'????????????' => '',
						'??????' => '',
						'????????????' => '',
						'??????' => '',
						'????????????' => '',
						'??????' => '',
						'????????????' => '',
						'????????????' => '',
						'???????????????' => '',
						'???????????????' => '',
						'???????????????' => '',
						'???????????????' => '',
						'???????????????' => '??????',
						'??????' => $all_qty,
						'??????' => '',
						'??????' => '',
						'??????' => '',
						'???????????????' => '',
						'?????????' => '',
						'????????????' => '',
						'????????????????????????' => '',
						'????????????' => '',
						'???????????????(??????)' => '',
						'???????????????(??????)' => '',
						'???????????????' => '',
						'???????????????' => '',
						'????????????' => '',
						'????????????' => '' ];
		}
		$temp = collect($temp);
		return (new FastExcel($temp))->download('ProductInfoEx_'.$now_date.'.xlsx');
    }
    public function pdf(Request $data){
		ini_set("memory_limit","1300M");
		set_time_limit(0);

		$where = [];
		$having = '';
		$wherein = '1';
		if($data->s_product_code != null) $where[] = ['stockmaster.stockid', 'like', "%".$data->s_product_code."%"];
		if($data->s_product_name != null) $where[] = ['stockmaster.description', 'like', "%".$data->s_product_name."%"];
		if($data->s_original_code != null) $where[] = ['stockmaster.stock_original_code', 'like', "%".$data->s_original_code."%"];
		if($data->s_original_name != null) $where[] = ['stockmaster.stock_original_name', 'like', "%".$data->s_original_name."%"];
		if($data->s_supplier_id != null){
			$supplier_id = str_replace(",", "','",$data->s_supplier_id);
			$wherein .= " AND stockmaster.stockid IN (SELECT stockid FROM stock_purch_suppliers WHERE supplierid IN ('".$supplier_id."'))";
		}
		if($data->s_product_catid != null){
			$product_catid = str_replace(",", "','",$data->s_product_catid);
			$wherein .= " AND stockmaster.categoryid IN ('".$product_catid."')";
		}
		if($data->s_brand_id != null) $where[] = ['stockmaster.stock_brand_id', '=', $data->s_brand_id];
		if($data->s_is_close != null) $where[] = ['stockmaster.is_invalid', '=', $data->s_is_close];
		if($data->s_loc == 1){
			$having = " qty <> 0 ";
		}else if($data->s_loc == 2){
			$having = " qty >= ".$data->from_qty." AND qty <= ".$data->to_qty;
		}
		if($data->s_loc == '') {
			$row_data = Product::where($where)
								->whereRaw($wherein)
								->leftJoin('color_master','color_master.id','=','stockmaster.colorid')
								->leftJoin('production_place','production_place.id','=','stockmaster.production_place_id')
								->leftJoin('stockcategory','stockcategory.categoryid','=','stockmaster.categoryid')
								->leftJoin('taxcategories','taxcategories.taxcatid','=','stockmaster.taxcatid')
								->leftJoin('prices',function($join){
										$join->on([ ['prices.stockid','=','stockmaster.stockid'],
													['prices.currabrev','=','stockmaster.currabrev'],
													['prices.typeabbrev','=',DB::raw("'01'")] ]);
								})
								->leftJoin('unitsofmeasure','unitsofmeasure.id','=','stockmaster.use_units')
								->leftJoin('brand','brand.id','=','stockmaster.stock_brand_id')
								->select('stockmaster.stockid','stockmaster.international_barcode','stockmaster.description',
										'stockcategory.categoryid','stockcategory.categorydescription',
										'unitsofmeasure.unit_name','stockmaster.taxcatid','taxcategories.taxcatname',
										'prices.price AS de_price',
										'stockmaster.safe_loc_qty','stockmaster.basic_order_qty',
										DB::raw('CASE	WHEN (stockmaster.cost_type = "1") THEN "??????"
														WHEN (stockmaster.cost_type = "2") THEN "??????"
														WHEN (stockmaster.cost_type = "3") THEN "??????"
														WHEN (stockmaster.cost_type = "4") THEN "??????"
												ELSE "???"
												END AS cost_type'),
										'stockmaster.stock_original_name','stockmaster.stock_original_code',
										DB::raw('CONCAT(brand.code,":",brand.name) AS brand_name,
												(SELECT SUM(quantity) FROM locstock WHERE locstock.stockid = stockmaster.stockid GROUP BY stockid) qty '),
										'stockmaster.english_name AS english_name',
										'stockmaster.stock_spec','stockmaster.stock_material_id',
										'color_master.name AS color_name','production_place.place_name',
										'stockmaster.dispurchase_date',
										'stockmaster.invoice_description',
										DB::raw("(SELECT price
													FROM stock_purch_price
													WHERE stop_date >= ".date('Y-m-d')." AND start_date <= ".date('Y-m-d')."
													AND stockid = stockmaster.stockid AND deleted = 0
													AND currabrev = 'TWD' AND price IS NOT NULL LIMIT 1) AS purch_price"),
										DB::raw('(SELECT original_sales_price
													FROM stock_prices_import_details
													WHERE effective_date <= "'.date('Y-m-d').'"
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_sales_price IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_sales_price'),
										DB::raw('(SELECT original_purch_price
													FROM stock_prices_import_details
													WHERE effective_date <= "'.date('Y-m-d').'"
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_purch_price IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_purch_price'),
										DB::raw('CASE	WHEN (stockmaster.is_inventory = "1") THEN "???"
														ELSE "???"
														END AS is_inventory'),
										DB::raw('CASE	WHEN (stockmaster.is_no_accumulated_amount = "1") THEN "???"
														ELSE "???"
														END AS is_no_accumulated_amount'),
										DB::raw('CASE	WHEN (stockmaster.is_free_price = "1") THEN "???"
														ELSE "???"
														END AS is_free_price'),
										DB::raw('(SELECT original_sales_price_notax
													FROM stock_prices_import_details
													WHERE effective_date <= "'.date('Y-m-d').'"
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_sales_price_notax IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_sales_price_notax'),
										DB::raw('(SELECT original_purch_price_notax
													FROM stock_prices_import_details
													WHERE effective_date <= "'.date('Y-m-d').'"
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_purch_price_notax IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_purch_price_notax'),
										DB::raw('CASE	WHEN (stockmaster.is_no_discount = "1") THEN "???"
														ELSE "???"
														END AS is_no_discount'),
										DB::raw('CASE	WHEN (stockmaster.is_invalid = "1") THEN "???"
														ELSE "???"
														END AS is_invalid')
										)
								->orderBy('stockmaster.stockid')
								->get();
		}else{
			$row_data = Product::where($where)
								->whereRaw($wherein)
								->leftJoin('color_master','color_master.id','=','stockmaster.colorid')
								->leftJoin('production_place','production_place.id','=','stockmaster.production_place_id')
								->leftJoin('stockcategory','stockcategory.categoryid','=','stockmaster.categoryid')
								->leftJoin('taxcategories','taxcategories.taxcatid','=','stockmaster.taxcatid')
								->leftJoin('prices',function($join){
										$join->on([ ['prices.stockid','=','stockmaster.stockid'],
													['prices.currabrev','=','stockmaster.currabrev'],
													['prices.typeabbrev','=',DB::raw("'01'")] ]);
								})
								->leftJoin('unitsofmeasure','unitsofmeasure.id','=','stockmaster.use_units')
								->leftJoin('brand','brand.id','=','stockmaster.stock_brand_id')
								->select('stockmaster.stockid','stockmaster.international_barcode','stockmaster.description',
										'stockcategory.categoryid','stockcategory.categorydescription',
										'unitsofmeasure.unit_name','stockmaster.taxcatid','taxcategories.taxcatname',
										'prices.price AS de_price',
										'stockmaster.safe_loc_qty','stockmaster.basic_order_qty',
										DB::raw('CASE	WHEN (stockmaster.cost_type = "1") THEN "??????"
														WHEN (stockmaster.cost_type = "2") THEN "??????"
														WHEN (stockmaster.cost_type = "3") THEN "??????"
														WHEN (stockmaster.cost_type = "4") THEN "??????"
												ELSE "???"
												END AS cost_type'),
										'stockmaster.stock_original_name','stockmaster.stock_original_code',
										DB::raw('CONCAT(brand.code,":",brand.name) AS brand_name,
												(SELECT SUM(quantity) FROM locstock WHERE locstock.stockid = stockmaster.stockid GROUP BY stockid) qty '),
										'stockmaster.english_name AS english_name',
										'stockmaster.stock_spec','stockmaster.stock_material_id',
										'color_master.name AS color_name','production_place.place_name',
										'stockmaster.dispurchase_date',
										'stockmaster.invoice_description',
										DB::raw("(SELECT price
													FROM stock_purch_price
													WHERE stop_date >= ".date('Y-m-d')." AND start_date <= ".date('Y-m-d')."
													AND stockid = stockmaster.stockid AND deleted = 0
													AND currabrev = 'TWD' AND price IS NOT NULL LIMIT 1) AS purch_price"),
										DB::raw('(SELECT original_sales_price
													FROM stock_prices_import_details
													WHERE effective_date <= "'.date('Y-m-d').'"
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_sales_price IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_sales_price'),
										DB::raw('(SELECT original_purch_price
													FROM stock_prices_import_details
													WHERE effective_date <= "'.date('Y-m-d').'"
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_purch_price IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_purch_price'),
										DB::raw('CASE	WHEN (stockmaster.is_inventory = "1") THEN "???"
														ELSE "???"
														END AS is_inventory'),
										DB::raw('CASE	WHEN (stockmaster.is_no_accumulated_amount = "1") THEN "???"
														ELSE "???"
														END AS is_no_accumulated_amount'),
										DB::raw('CASE	WHEN (stockmaster.is_free_price = "1") THEN "???"
														ELSE "???"
														END AS is_free_price'),
										DB::raw('(SELECT original_sales_price_notax
													FROM stock_prices_import_details
													WHERE effective_date <= "'.date('Y-m-d').'"
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_sales_price_notax IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_sales_price_notax'),
										DB::raw('(SELECT original_purch_price_notax
													FROM stock_prices_import_details
													WHERE effective_date <= "'.date('Y-m-d').'"
													AND stockid = stockmaster.stockid AND deleted = 0
													AND original_purch_price_notax IS NOT NULL
													ORDER BY effective_date DESC LIMIT 1) AS original_purch_price_notax'),
										DB::raw('CASE	WHEN (stockmaster.is_no_discount = "1") THEN "???"
														ELSE "???"
														END AS is_no_discount'),
										DB::raw('CASE	WHEN (stockmaster.is_invalid = "1") THEN "???"
														ELSE "???"
														END AS is_invalid')
										)
								->havingRaw($having)
								->orderBy('stockmaster.stockid')
								->get();
		}
		$now_data = [];
		$all_qty = 0;
		$index = 0;
		foreach($row_data as $k => $v){
			$all_qty += round($v['qty']);
			$now_data[$index]['a'] = $v['stockid'];
			$now_data[$index]['b'] = mb_substr($v['description'],0,10,"utf-8");
			// $now_data[$index]['c'] = mb_substr($v['stock_original_code'],0,10,"utf-8");
			$now_data[$index]['d'] = $v['stock_original_name'];
			// $now_data[$index]['e'] = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', mb_substr($v['english_name'],0,10,"utf-8"));
			$now_data[$index]['f'] = mb_substr($v['brand_name'],0,5,"utf-8");
			$now_data[$index]['g'] = number_format($v['basic_order_qty']);
			$now_data[$index]['h'] = number_format($v['qty']);
			$now_data[$index]['i'] = number_format($v['de_price']);
			$index++;
		}
		if(count($row_data) > 0){
			$now_data[$index]['a'] = '';
			$now_data[$index]['b'] = '';
			// $now_data[$index]['c'] = '';
			$now_data[$index]['d'] = '';
			// $now_data[$index]['e'] = '';
			$now_data[$index]['f'] = '';
			$now_data[$index]['g'] = '';
			$now_data[$index]['h'] = number_format($all_qty);
			$now_data[$index]['i'] = '';
		}
		$PHPWord = new PhpWord();
		//??????????????????
		$doc = new TemplateProcessor(public_path("word/ProductInfo.docx"));
		$file_name = md5(time());
		//?????????????????????
		$doc->cloneRowAndSetValues('a', $now_data);
		$newDoc = storage_path($file_name.'.docx');
		//?????????word??????
		$doc->saveAs($newDoc);
		$newPdf = storage_path($file_name.'.pdf');
		\PhpOffice\PhpWord\Settings::setPdfRendererName(\PhpOffice\PhpWord\Settings::PDF_RENDERER_DOMPDF);
		\PhpOffice\PhpWord\Settings::setPdfRendererPath('.');
		$pdf = \PhpOffice\PhpWord\IOFactory::load($newDoc);
		$xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($pdf, 'PDF');
		// (exec('sudo find / -name unoconv -f pdf ' . $newDoc));
		//???????????????word??????
		unlink($newDoc);
		//??????pdf??????
		$xmlWriter->save($newPdf, 'PDF');
		return response()->download($newPdf)->deleteFileAfterSend(true);
    }

}