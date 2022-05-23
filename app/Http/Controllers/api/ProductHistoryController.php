<?php

namespace App\Http\Controllers\api;

use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class ProductHistoryController extends Controller
{
    public function show($types=null,Request $data)
    {
	if($types == 'transout'){
		$show_item = 'Trans';
		$row_data = DB::select("SELECT *
					FROM(	SELECT	loctransfers.reference ,
							substring(loctransfers.ship_time,1,10) AS ref_date ,
							loctransfers.shiploc ,
							ship.locationname AS shiploc_name ,
							loctransfers.recloc ,
							rec.locationname AS recloc_name ,
							stockmaster.stockid ,
							stockmaster.description AS stockname ,
							loctransfers.shipqty ,
							'0' AS is_cancel
						FROM loctransfers
						LEFT JOIN locations AS ship ON ship.loccode = loctransfers.shiploc
						LEFT JOIN locations AS rec ON rec.loccode = loctransfers.recloc
						LEFT JOIN stockmaster ON stockmaster.stockid = loctransfers.stockid
						WHERE	loctransfers.stockid = '".$data->product."'
								AND loctransfers.shiploc = '".$data->loccode."'
								AND DATE_FORMAT(loctransfers.ship_time,'%Y-%m-%d') >= '".$data->from_date."'
								AND DATE_FORMAT(loctransfers.ship_time,'%Y-%m-%d') <= '".$data->to_date."'
								AND ifnull(loctransfers.ship_user_id,'') <> ''
						UNION ALL
						SELECT	purch_picking.transno AS reference ,
								substring(purch_picking_details.ship_time,1,10) AS ref_date ,
								purch_picking_details.shiploc ,
								ship.locationname AS shiploc_name ,
								purch_picking_details.recloc ,
								rec.locationname AS recloc_name ,
								stockmaster.stockid ,
								stockmaster.description AS stockname ,
								purch_picking_details.qty AS shipqty ,
								CASE when ifnull(cancel_time,'') = '' then '0'
								ELSE '1'
								END AS is_cancel
						FROM purch_picking_details
						LEFT JOIN locations AS ship ON ship.loccode = purch_picking_details.shiploc
						LEFT JOIN locations AS rec ON rec.loccode = purch_picking_details.recloc
						LEFT JOIN purch_picking ON purch_picking.id = purch_picking_details.purch_picking_id
						LEFT JOIN stockmaster ON stockmaster.stockid = purch_picking_details.stockid
						WHERE	purch_picking_details.stockid = '".$data->product."'
								AND purch_picking_details.shiploc = '".$data->loccode."'
								AND DATE_FORMAT(purch_picking_details.ship_time,'%Y-%m-%d') >= '".$data->from_date."'
								AND DATE_FORMAT(purch_picking_details.ship_time,'%Y-%m-%d') <= '".$data->to_date."'
								AND ifnull(purch_picking_details.ship_user_id,'') <> ''
					) AS temp
					ORDER BY temp.ref_date DESC, temp.reference DESC");
	}else if($types == 'transin'){
		$show_item = 'Trans';
		$row_data = DB::select("SELECT * FROM
								(   SELECT	loctransfers.reference ,
											substring(loctransfers.ship_time,1,10) AS ref_date ,
											loctransfers.shiploc ,
											ship.locationname AS shiploc_name ,
											loctransfers.recloc ,
											rec.locationname AS recloc_name ,
											stockmaster.stockid ,
											stockmaster.description AS stockname ,
											loctransfers.recqty AS shipqty,
											'0' AS is_cancel
									FROM loctransfers
									LEFT JOIN locations AS ship ON ship.loccode = loctransfers.shiploc
									LEFT JOIN locations AS rec ON rec.loccode = loctransfers.recloc
									LEFT JOIN stockmaster ON stockmaster.stockid = loctransfers.stockid
									WHERE	loctransfers.stockid = '".$data->product."'
											AND loctransfers.recloc = '".$data->loccode."'
											AND DATE_FORMAT(loctransfers.rec_time,'%Y-%m-%d') >= '".$data->from_date."'
											AND DATE_FORMAT(loctransfers.rec_time,'%Y-%m-%d') <= '".$data->to_date."'
											AND ifnull(loctransfers.rec_user_id,'') <> ''
									UNION ALL
									SELECT  purch_picking.transno AS reference ,
											substring(purch_picking_details.rec_time,1,10) AS ref_date ,
											purch_picking_details.shiploc ,
											ship.locationname AS shiploc_name ,
											purch_picking_details.recloc ,
											rec.locationname AS recloc_name ,
											stockmaster.stockid ,
											stockmaster.description AS stockname ,
											purch_picking_details.qty AS shipqty ,
											CASE when ifnull(cancel_time,'') = '' then '0'
											ELSE '1'
											END AS is_cancel
									FROM purch_picking_details
									LEFT JOIN locations AS ship ON ship.loccode = purch_picking_details.shiploc
									LEFT JOIN locations AS rec ON rec.loccode = purch_picking_details.recloc
									LEFT JOIN purch_picking ON purch_picking.id = purch_picking_details.purch_picking_id
									LEFT JOIN stockmaster ON stockmaster.stockid = purch_picking_details.stockid
									WHERE	purch_picking_details.stockid = '".$data->product."'
											AND purch_picking_details.recloc = '".$data->loccode."'
											AND DATE_FORMAT(purch_picking_details.rec_time,'%Y-%m-%d') >= '".$data->from_date."'
											AND DATE_FORMAT(purch_picking_details.rec_time,'%Y-%m-%d') <= '".$data->to_date."'
											AND ifnull(purch_picking_details.rec_user_id,'') <> ''
									UNION ALL
									SELECT	purch_picking.transno AS reference ,
											substring(purch_picking_details.cancel_time,1,10) AS ref_date ,
											purch_picking_details.shiploc ,
											ship.locationname AS shiploc_name ,
											purch_picking_details.recloc ,
											rec.locationname AS recloc_name ,
											stockmaster.stockid ,
											stockmaster.description AS stockname ,
											purch_picking_details.qty AS shipqty ,
											'1' AS is_cancel
									FROM purch_picking_details
									LEFT JOIN locations AS ship ON ship.loccode = purch_picking_details.shiploc
									LEFT JOIN locations AS rec ON rec.loccode = purch_picking_details.recloc
									LEFT JOIN purch_picking ON purch_picking.id = purch_picking_details.purch_picking_id
									LEFT JOIN stockmaster ON stockmaster.stockid = purch_picking_details.stockid
									WHERE 	purch_picking_details.stockid = '".$data->product."'
											AND purch_picking_details.shiploc = '".$data->loccode."'
											AND DATE_FORMAT(purch_picking_details.cancel_time,'%Y-%m-%d') >= '".$data->from_date."'
											AND DATE_FORMAT(purch_picking_details.cancel_time,'%Y-%m-%d') <= '".$data->to_date."'
											AND ifnull(purch_picking_details.cancel_time,'') <> ''
							) AS temp
							ORDER BY temp.ref_date DESC, temp.reference DESC");
	}else if($types == 'sales'){
		$show_item = 'Sales';
		$row_data = DB::select("SELECT	sales.no ,
										sale_point.sale_point_name ,
										cash_register.cash_register_name ,
										member.member_code ,
										member.member_name ,
										salesdetails.stock_id ,
										salesdetails.stock_description ,
										salesdetails.quantity ,
										salesdetails.subTotal ,
										substring(sales.date,1,10) AS sales_date
								FROM sales
								LEFT JOIN salesdetails ON sales.id = salesdetails.sales_id
								LEFT JOIN cash_register ON cash_register.id = sales.cash_register_id
								LEFT JOIN sale_point ON sale_point.id = cash_register.sale_point_id
								LEFT JOIN member ON sales.member = member.id
								WHERE	salesdetails.stock_id = '".$data->product."'
										AND sales.loccode = '".$data->loccode."'
										AND DATE_FORMAT(sales.date,'%Y-%m-%d') >= '".$data->from_date."'
										AND DATE_FORMAT(sales.date,'%Y-%m-%d') <= '".$data->to_date."'
										AND sales.sale_type IN ('1','3')
								ORDER BY sales.date DESC");
	}else if($types == 'sales_return'){
		$show_item = 'Sales';
		$row_data = DB::select("SELECT	sales.no ,
										sale_point.sale_point_name ,
										cash_register.cash_register_name ,
										member.member_code ,
										member.member_name ,
										salesdetails.stock_id ,
										salesdetails.stock_description ,
										salesdetails.quantity ,
										salesdetails.subTotal ,
										substring(sales.date,1,10) AS sales_date
								FROM sales
								LEFT JOIN salesdetails ON sales.id=salesdetails.sales_id
								LEFT JOIN cash_register ON cash_register.id=sales.cash_register_id
								LEFT JOIN sale_point ON sale_point.id=cash_register.sale_point_id
								LEFT JOIN member ON sales.member = member.id
								WHERE	salesdetails.stock_id = '".$data->product."'
										AND sales.loccode = '".$data->loccode."'
										AND DATE_FORMAT(sales.date,'%Y-%m-%d') >= '".$data->from_date."'
										AND DATE_FORMAT(sales.date,'%Y-%m-%d') <= '".$data->to_date."'
										AND sales.sale_type = '4'
								ORDER BY sales.date DESC");
	}else if($types == 'counts_in'){
		$show_item = 'Adjust';
		$row_data = DB::select("SELECT	stock_adjust.transno ,
										substring(stock_adjust.trandate,1,10) AS trandate ,
										stock_adjust.loccode ,
										locations.locationname,
										stockmaster.stockid ,
										stockmaster.description AS stockname ,
										stock_adjust_details.qty
								FROM stock_adjust_details 
								LEFT JOIN stock_adjust ON stock_adjust.id = stock_adjust_details.stock_adjust_id
								LEFT JOIN locations ON locations.loccode = stock_adjust.loccode
								LEFT JOIN stockmaster ON stockmaster.stockid = stock_adjust_details.stockid
								WHERE	stock_adjust_details.stockid = '".$data->product."'
										AND stock_adjust.loccode = '".$data->loccode."'
										AND DATE_FORMAT(stock_adjust.trandate,'%Y-%m-%d') >= '".$data->from_date."'
										AND DATE_FORMAT(stock_adjust.trandate,'%Y-%m-%d') <= '".$data->to_date."'
										AND ifnull(stock_adjust.is_adjust,'') = '1'
								ORDER BY stock_adjust.trandate DESC, stock_adjust.transno DESC");
	}
	return view('manage.'.$show_item, ['title' => '',
				'item_id' => $show_item,
				'userid' => session('userid'),
				'username' => session('username'),
				'modeldata' => session('modeldata'),
				'workdata' => session('workdata'),
				'row_data' => $row_data ]);
    }
}
