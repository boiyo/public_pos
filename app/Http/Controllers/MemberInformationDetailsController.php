<?php

namespace App\Http\Controllers;

use App\Models\BasicSet;
use App\Models\NewsLetterType;
use App\Models\Brand;
use App\Models\SalePoint;
use App\Models\Member;
use App\Models\IndexItem;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Rap2hpoutre\FastExcel\FastExcel;

use DB;

class MemberInformationDetailsController extends Controller
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
		$wherein = "";
		$wherein2 = "1";
		$c_date = "";
		if($data->s_c_from_date != null){
			$c_date .= "sales.date >= '".$data->s_c_from_date."'";
		}
		if($data->s_c_to_date != null){
			if($c_date != null) $c_date .= " AND ";
			$c_date .= "sales.date <= '".$data->s_c_to_date." 23:59:59'";
		}
		if($c_date == null) $c_date = "1";

		if($data->s_member_code != null) $where[] = ['member.member_code', 'like',"%". $data->s_member_code."%"];
		if($data->s_member_name != null) $where[] = ['member.member_name', 'like',"%". $data->s_member_name."%"];

		if($data->s_y_member != null) $where[] = ['member.y_member', '=', $data->s_y_member];
		if($data->s_s_member != null) $where[] = ['member.s_member', '=', $data->s_s_member];

		if($data->s_sale_point != null) $wherein .= 'exists (SELECT DISTINCT(sales.member) FROM sales 
																WHERE exists (	SELECT id FROM cash_register
																		WHERE sales.cash_register_id = cash_register.id
																		AND cash_register.sale_point_id = "'.$data->s_sale_point.'"
																) AND sales.member = member.id AND '.$c_date.')';
 		if($data->s_brand != null){
			if($wherein != '') $wherein .= ' AND ';
			$wherein .= 'exists ( SELECT DISTINCT(sales.member) FROM sales WHERE 
						exists (SELECT DISTINCT(salesdetails.sales_id) FROM salesdetails
							LEFT JOIN stockmaster ON stockmaster.stockid = salesdetails.stock_id
							WHERE salesdetails.sales_id = sales.id AND stockmaster.stock_brand_id = "'.$data->s_brand.'"
						) AND sales.member = member.id AND '.$c_date.')';
		}
		if($data->s_m_from_date != null) $where[] = ['member.create_time', '>=', $data->s_m_from_date];
		if($data->s_m_to_date != null) $where[] = ['member.create_time', '<=', $data->s_m_to_date." 23:59:59"];
		if($data->s_m_sale_point != null) $where[] = ['member.sale_point_id', '=', $data->s_m_sale_point];

		if($data->s_m_end_date != null) $where[] = ['member.member_end_date', '=', $data->s_m_end_date];
		if($data->s_year != null) $where[] = [DB::raw('substring(member.birthdate,1,4)'),$data->s_year];
		if($data->s_month != null) $where[] = [DB::raw('substring(member.birthdate,6,2)'),$data->s_month];

		if($data->s_is_receive_email != null) $where[] = ['member.is_receive_email', '=', $data->s_is_receive_email];
		if($data->s_is_receive_sms != null) $where[] = ['member.is_receive_sms', '=', $data->s_is_receive_sms];

		if($data->s_is_internet_member != null) $where[] = ['member.is_internet_member', '=', $data->s_is_internet_member];
		if($data->s_news_letter_type != null){
			if($wherein != '') $wherein .= ' AND ';
			$wherein .= 'member.id IN (SELECT member_id FROM member_news_letter_type WHERE news_letter_type_id = "'.$data->s_news_letter_type.'" GROUP BY member_id)';
		}
		if($data->s_is_no_accumulated != null) $where[] = ['member.is_no_accumulated', '=', $data->s_is_no_accumulated];
		if($wherein == null) $wherein = "1";

		if($data->s_from_amount != null) $wherein2 .= ' AND t_total >= '.$data->s_from_amount;
		if($data->s_to_amount != null) $wherein2 .= ' AND t_total <= '.$data->s_to_amount;
		if($data->s_from_accumulated_amount != null) $wherein2 .= ' AND total <= '.$data->s_from_accumulated_amount;
		if($data->s_to_accumulated_amount != null) $wherein2 .= ' AND total <= '.$data->s_to_accumulated_amount;
		$basic_set = BasicSet::get();
		if($wherein2 == '1'){
			$row_data = Member::where($where)
								->whereRaw($wherein)
								->select('member.member_code','member.transfer_member_code','member.internet_member_code',
										'member.member_name','gender.gender_name','member.birthdate','member.phone','member.home_phone',
										'member.email','member.company_name','member.company_uniform_number','member.company_areacode',
										'member.company_phone','member.company_ext','member.fax','occupation.occupation_name',
										'professional_title.professional_title_name',
										'member.contact_address_zip','member.contact_address_county','member.contact_address_city','member.contact_address_street',
										'member.residence_address_zip','member.residence_address_county','member.residence_address_city','member.residence_address_street',
										'member.company_address_zip','member.company_address_county','member.company_address_city','member.company_address_street',
										'member.transfer_total_consumption',
										'm_sp.sale_point_name',
										'member.y_member_time','member.member_end_date',
										DB::raw('CASE	WHEN member.y_member = "1" THEN "????????????" ELSE "????????????" END AS y_member_name,
												CASE	member.s_member WHEN "1" THEN "????????????" WHEN "2" THEN "????????????"
																		WHEN "3" THEN "??????" WHEN "4" THEN "????????????" WHEN "5" THEN "VIP"
																		WHEN "6" THEN "??????" ELSE "???" END AS s_member_name,
												CASE	WHEN member.is_no_accumulated = "1" THEN "???" ELSE "???" END AS is_no_accumulated,
												(	SELECT (SUM(IFNULL(salesdetails.subTotal,0)) + IFNULL(member.transfer_total_consumption,0)) AS t_total
													FROM sales USE INDEX(member)
													LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
													WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
												) AS t_total,
												(	SELECT IFNULL((CASE	WHEN (IFNULL(member.member_start_time,"") <> "") 
																		THEN SUM(IFNULL(salesdetails.subTotal,0)) 
																		ELSE (SUM(IFNULL(salesdetails.subTotal,0)) + IFNULL(member.transfer_total_consumption,0)) 
																	END),0) AS total
													FROM sales USE INDEX(member)
													LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
													WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
													AND salesdetails.is_no_accumulated_amount = "0"
													AND (	CASE	WHEN (IFNULL(member.member_start_time,"") <> "") 
																	THEN sales.date > member.member_start_time
																	ELSE 1
															END)) AS total,
												(	SELECT IFNULL(SUM(member_points_log.points),0) FROM member_points_log
													WHERE deleted = "0" AND is_invalid = "0" AND member_id = member.id
													AND (date_format(effective_date,"%Y-%m-%d") >= date_format(now(),"%Y-%m-%d"))) AS points,
												CASE	WHEN (	SELECT original_value 
																FROM member_continue_log  
																WHERE member_id= member.id AND member_column="y_member_time" AND new_value IS NULL 
																ORDER BY create_time DESC LIMIT 1
																) IS NULL
														THEN (	SELECT SUM(IFNULL(salesdetails.subTotal,0))
																FROM sales USE INDEX(member)
																LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id
																WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
																AND salesdetails.is_no_accumulated_amount = "0" + member.transfer_total_consumption)
														ELSE (	SELECT SUM(IFNULL(salesdetails.subTotal,0))
																FROM sales USE INDEX(member)
																LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id
																WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
																AND salesdetails.is_no_accumulated_amount = "0")
												END AS cumulative_amount,
												CASE	WHEN member.y_member = "1"
														THEN (
															CASE	WHEN (	SELECT COUNT(id) FROM sales WHERE is_return = "0" 
																			AND member = member.id AND sale_type in ("1","3","4") 
																			AND date > member.y_member_time AND substring(date,1,10) <= member.member_end_date
																			AND (CASE	WHEN (IFNULL(member.member_start_date,"") <> "")
																						THEN substring(sales.date,1,10) > member.member_start_date
																						ELSE 1
																				END)
																		) >= "'.$basic_set[0]["member_continue_time"].'"
																	THEN "<font color=green>?????????????????????</font><br>"
																	ELSE (	CASE	WHEN (	SELECT SUM(IFNULL(salesdetails.subTotal,0))
																					FROM sales USE INDEX(member)
																					LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
																					WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
																					AND sales.date > member.y_member_time AND substring(sales.date,1,10) <= member.member_end_date
																					AND salesdetails.is_no_accumulated_amount = "0"
																					AND (CASE	WHEN (IFNULL(member.member_start_date,"") <> "") 
																								THEN substring(sales.date,1,10) > member.member_start_date
																								ELSE 1
																						END)
																					) >= "'.$basic_set[0]["member_continue_amount"].'"
																					THEN "<font color=green>?????????????????????</font><br>"
																					ELSE	CONCAT((SELECT CONCAT("<font color=red>???????????????",COUNT(id),"???????????????",'.$basic_set[0]["member_continue_time"].' - COUNT(id),"??????????????????????????????</font><br>")
																									FROM sales
																									WHERE is_return = "0" AND member = member.id AND sale_type in ("1","3","4") 
																									AND date > member.y_member_time AND substring(date,1,10) <= member.member_end_date
																									AND (CASE	WHEN (IFNULL(member.member_start_date,"") <> "")
																												THEN substring(sales.date,1,10) > member.member_start_date
																												ELSE 1
																										END)
																									),
																									CASE	WHEN (	SELECT SUM(IFNULL(salesdetails.subTotal,0))
																													FROM sales USE INDEX(member)
																													LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
																													WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
																													AND sales.date > member.y_member_time AND substring(sales.date,1,10) <= member.member_end_date
																													AND salesdetails.is_no_accumulated_amount = "0"
																													AND (CASE	WHEN (IFNULL(member.member_start_date,"") <> "") 
																																THEN substring(sales.date,1,10) > member.member_start_date
																																ELSE 1
																														END)
																													) IS NULL
																											THEN CONCAT("<font color=red>???????????????0???????????????",'.$basic_set[0]["member_continue_amount"].',"??????????????????????????????</font><br>")
																											ELSE (	SELECT	CONCAT("<font color=red>???????????????",SUM(IFNULL(ROUND(salesdetails.subTotal),0)),"???????????????",'.$basic_set[0]["member_continue_amount"].' - SUM(IFNULL(ROUND(salesdetails.subTotal),0)),"??????????????????????????????</font><br>")
																													FROM sales USE INDEX(member)
																													LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
																													WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
																													AND sales.date > member.y_member_time AND substring(sales.date,1,10) <= member.member_end_date
																													AND salesdetails.is_no_accumulated_amount = "0"
																													AND (CASE	WHEN (IFNULL(member.member_start_date,"") <> "") 
																																THEN substring(sales.date,1,10) > member.member_start_date
																																ELSE 1
																														END)
																													)
																									END
																							)
																			END
																	)
															END
														)
														ELSE ""
												END AS memberlevelinfo,
												CASE	WHEN member.transfer_member_code IS NOT NULL
														THEN (SELECT COUNT(DISTINCT(member_oldsales.salesno)) FROM member_oldsales 
														WHERE member_oldsales.member_code = member.transfer_member_code OR member_oldsales.member_code_new = member.transfer_member_code)
														ELSE "0"
												END AS old_total,
												(	SELECT count(id) FROM sales USE INDEX(member) 
													WHERE sale_type IN ("1","3","4") AND sales.member = member.id AND '.$c_date.') AS consumptions_counts,
												(	SELECT date FROM sales USE INDEX(member) WHERE member = member.id AND '.$c_date.'
													ORDER BY date DESC LIMIT 0,1) AS consumptionsdate,
												CASE WHEN member.is_black = "1" THEN "???" ELSE "???" END AS is_black,
												CASE	WHEN member.is_receive_email = "1" THEN "???" ELSE "???" END AS is_receive_email,
												CASE	WHEN member.is_receive_sms = "1" THEN "???" ELSE "???" END AS is_receive_sms,
												CASE	WHEN member.is_internet_member = "1" THEN "???" ELSE "???" END AS is_internet_member,
												CASE	WHEN member.is_receive_message = "1" THEN "???" ELSE "???" END AS is_receive_message,
												CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
																LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
																WHERE news_letter_type.id = "1" AND member_news_letter_type.deleted = "0") > 0 
														THEN "???" 
														ELSE "???" 
												END AS news_letter_type_1,
												CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
																LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
																WHERE news_letter_type.id = "2" AND member_news_letter_type.deleted = "0") > 0 
														THEN "???"
														ELSE "???"
												END AS news_letter_type_2,
												CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
																LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
																WHERE news_letter_type.id = "3" AND member_news_letter_type.deleted = "0") > 0 
														THEN "???"
														ELSE "???"
												END AS news_letter_type_3,
												CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
																LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
																WHERE news_letter_type.id = "4" AND member_news_letter_type.deleted = "0") > 0 
														THEN "???" 
														ELSE "???" 
												END AS news_letter_type_4,
												CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
																LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
																WHERE news_letter_type.id = "5" AND member_news_letter_type.deleted = "0") > 0 
														THEN "???" 
														ELSE "???" 
												END AS news_letter_type_5,
												CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
																LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
																WHERE news_letter_type.id = "6" AND member_news_letter_type.deleted = "0") > 0 
														THEN "???" 
														ELSE "???" 
												END AS news_letter_type_6'),
										'foreign_visitor_country.country_name',
										'blacklist_reason.reason_name AS blacklist_reason_name',
										'available_time_interval.time_interval_name AS available_time_interval_name',
										'unavailable_time_interval.time_interval_name AS unavailable_time_interval_name',
										'member.note','member.description2','member.description',
										'member.create_user_name','member.create_time','member.modify_user_name','member.modify_time')
								->leftJoin('gender','gender.id','=','member.gender_id')
								->leftJoin('occupation','occupation.id','=','member.occupation_id')
								->leftJoin('professional_title','professional_title.id','=','member.professional_title_id')
								->leftJoin('sale_point AS m_sp','m_sp.id','=','member.sale_point_id')
								->leftJoin('foreign_visitor_country','foreign_visitor_country.id','=','member.foreign_visitor_country_id')
								->leftJoin('blacklist_reason','blacklist_reason.id','=','member.blacklist_reason_id')
								->leftJoin('time_interval AS available_time_interval','available_time_interval.id','=','member.available_time_interval_id')
								->leftJOin('time_interval AS unavailable_time_interval','unavailable_time_interval.id','=','member.unavailable_time_interval_id')
								//->orderBy('member.member_code','ASC')
								->paginate(session('max_page'))->appends($data->input());
		}else{
			$row_data = Member::where($where)
								->whereRaw($wherein)
								->select('member.member_code','member.transfer_member_code','member.internet_member_code',
										'member.member_name','gender.gender_name','member.birthdate','member.phone','member.home_phone',
										'member.email','member.company_name','member.company_uniform_number','member.company_areacode',
										'member.company_phone','member.company_ext','member.fax','occupation.occupation_name',
										'professional_title.professional_title_name',
										'member.contact_address_zip','member.contact_address_county','member.contact_address_city','member.contact_address_street',
										'member.residence_address_zip','member.residence_address_county','member.residence_address_city','member.residence_address_street',
										'member.company_address_zip','member.company_address_county','member.company_address_city','member.company_address_street',
										'member.transfer_total_consumption',
										'm_sp.sale_point_name',
										'member.y_member_time','member.member_end_date',
										DB::raw('CASE	WHEN member.y_member = "1" THEN "????????????" ELSE "????????????" END AS y_member_name,
												CASE	member.s_member WHEN "1" THEN "????????????" WHEN "2" THEN "????????????"
																		WHEN "3" THEN "??????" WHEN "4" THEN "????????????" WHEN "5" THEN "VIP"
																		WHEN "6" THEN "??????" ELSE "???" END AS s_member_name,
												CASE	WHEN member.is_no_accumulated = "1" THEN "???" ELSE "???" END AS is_no_accumulated,
												(	SELECT (SUM(IFNULL(salesdetails.subTotal,0)) + IFNULL(member.transfer_total_consumption,0)) AS t_total
													FROM sales USE INDEX(member)
													LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
													WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
												) AS t_total,
												(	SELECT IFNULL((CASE	WHEN (IFNULL(member.member_start_time,"") <> "") 
																		THEN SUM(IFNULL(salesdetails.subTotal,0)) 
																		ELSE (SUM(IFNULL(salesdetails.subTotal,0)) + IFNULL(member.transfer_total_consumption,0)) 
																	END),0) AS total
													FROM sales USE INDEX(member)
													LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
													WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
													AND salesdetails.is_no_accumulated_amount = "0"
													AND (	CASE	WHEN (IFNULL(member.member_start_time,"") <> "") 
																	THEN sales.date > member.member_start_time
																	ELSE 1
															END)) AS total,
												(	SELECT IFNULL(SUM(member_points_log.points),0) FROM member_points_log
													WHERE deleted = "0" AND is_invalid = "0" AND member_id = member.id
													AND (date_format(effective_date,"%Y-%m-%d") >= date_format(now(),"%Y-%m-%d"))) AS points,
												CASE	WHEN (	SELECT original_value 
																FROM member_continue_log  
																WHERE member_id= member.id AND member_column="y_member_time" AND new_value IS NULL 
																ORDER BY create_time DESC LIMIT 1
																) IS NULL
														THEN (	SELECT SUM(IFNULL(salesdetails.subTotal,0))
																FROM sales USE INDEX(member)
																LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id
																WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
																AND salesdetails.is_no_accumulated_amount = "0" + member.transfer_total_consumption)
														ELSE (	SELECT SUM(IFNULL(salesdetails.subTotal,0))
																FROM sales USE INDEX(member)
																LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id
																WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
																AND salesdetails.is_no_accumulated_amount = "0")
												END AS cumulative_amount,
												CASE	WHEN member.y_member = "1"
														THEN (
															CASE	WHEN (	SELECT COUNT(id) FROM sales WHERE is_return = "0" 
																			AND member = member.id AND sale_type in ("1","3","4") 
																			AND date > member.y_member_time AND substring(date,1,10) <= member.member_end_date
																			AND (CASE	WHEN (IFNULL(member.member_start_date,"") <> "")
																						THEN substring(sales.date,1,10) > member.member_start_date
																						ELSE 1
																				END)
																		) >= "'.$basic_set[0]["member_continue_time"].'"
																	THEN "<font color=green>?????????????????????</font><br>"
																	ELSE (	CASE	WHEN (	SELECT SUM(IFNULL(salesdetails.subTotal,0))
																					FROM sales USE INDEX(member)
																					LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
																					WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
																					AND sales.date > member.y_member_time AND substring(sales.date,1,10) <= member.member_end_date
																					AND salesdetails.is_no_accumulated_amount = "0"
																					AND (CASE	WHEN (IFNULL(member.member_start_date,"") <> "") 
																								THEN substring(sales.date,1,10) > member.member_start_date
																								ELSE 1
																						END)
																					) >= "'.$basic_set[0]["member_continue_amount"].'"
																					THEN "<font color=green>?????????????????????</font><br>"
																					ELSE	CONCAT((SELECT CONCAT("<font color=red>???????????????",COUNT(id),"???????????????",'.$basic_set[0]["member_continue_time"].' - COUNT(id),"??????????????????????????????</font><br>")
																									FROM sales
																									WHERE is_return = "0" AND member = member.id AND sale_type in ("1","3","4") 
																									AND date > member.y_member_time AND substring(date,1,10) <= member.member_end_date
																									AND (CASE	WHEN (IFNULL(member.member_start_date,"") <> "")
																												THEN substring(sales.date,1,10) > member.member_start_date
																												ELSE 1
																										END)
																									),
																									CASE	WHEN (	SELECT SUM(IFNULL(salesdetails.subTotal,0))
																													FROM sales USE INDEX(member)
																													LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
																													WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
																													AND sales.date > member.y_member_time AND substring(sales.date,1,10) <= member.member_end_date
																													AND salesdetails.is_no_accumulated_amount = "0"
																													AND (CASE	WHEN (IFNULL(member.member_start_date,"") <> "") 
																																THEN substring(sales.date,1,10) > member.member_start_date
																																ELSE 1
																														END)
																													) IS NULL
																											THEN CONCAT("<font color=red>???????????????0???????????????",'.$basic_set[0]["member_continue_amount"].',"??????????????????????????????</font><br>")
																											ELSE (	SELECT	CONCAT("<font color=red>???????????????",SUM(IFNULL(ROUND(salesdetails.subTotal),0)),"???????????????",'.$basic_set[0]["member_continue_amount"].' - SUM(IFNULL(ROUND(salesdetails.subTotal),0)),"??????????????????????????????</font><br>")
																													FROM sales USE INDEX(member)
																													LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
																													WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
																													AND sales.date > member.y_member_time AND substring(sales.date,1,10) <= member.member_end_date
																													AND salesdetails.is_no_accumulated_amount = "0"
																													AND (CASE	WHEN (IFNULL(member.member_start_date,"") <> "") 
																																THEN substring(sales.date,1,10) > member.member_start_date
																																ELSE 1
																														END)
																													)
																									END
																							)
																			END
																	)
															END
														)
														ELSE ""
												END AS memberlevelinfo,
												CASE	WHEN member.transfer_member_code IS NOT NULL
														THEN (SELECT COUNT(DISTINCT(member_oldsales.salesno)) FROM member_oldsales 
														WHERE member_oldsales.member_code = member.transfer_member_code OR member_oldsales.member_code_new = member.transfer_member_code)
														ELSE "0"
												END AS old_total,
												(	SELECT count(id) FROM sales USE INDEX(member) 
													WHERE sale_type IN ("1","3","4") AND sales.member = member.id AND '.$c_date.') AS consumptions_counts,
												(	SELECT date FROM sales USE INDEX(member) WHERE member = member.id AND '.$c_date.'
													ORDER BY date DESC LIMIT 0,1) AS consumptionsdate,
												CASE WHEN member.is_black = "1" THEN "???" ELSE "???" END AS is_black,
												CASE	WHEN member.is_receive_email = "1" THEN "???" ELSE "???" END AS is_receive_email,
												CASE	WHEN member.is_receive_sms = "1" THEN "???" ELSE "???" END AS is_receive_sms,
												CASE	WHEN member.is_internet_member = "1" THEN "???" ELSE "???" END AS is_internet_member,
												CASE	WHEN member.is_receive_message = "1" THEN "???" ELSE "???" END AS is_receive_message,
												CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
																LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
																WHERE news_letter_type.id = "1" AND member_news_letter_type.deleted = "0") > 0 
														THEN "???" 
														ELSE "???" 
												END AS news_letter_type_1,
												CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
																LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
																WHERE news_letter_type.id = "2" AND member_news_letter_type.deleted = "0") > 0 
														THEN "???"
														ELSE "???"
												END AS news_letter_type_2,
												CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
																LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
																WHERE news_letter_type.id = "3" AND member_news_letter_type.deleted = "0") > 0 
														THEN "???"
														ELSE "???"
												END AS news_letter_type_3,
												CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
																LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
																WHERE news_letter_type.id = "4" AND member_news_letter_type.deleted = "0") > 0 
														THEN "???" 
														ELSE "???" 
												END AS news_letter_type_4,
												CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
																LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
																WHERE news_letter_type.id = "5" AND member_news_letter_type.deleted = "0") > 0 
														THEN "???" 
														ELSE "???" 
												END AS news_letter_type_5,
												CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
																LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
																WHERE news_letter_type.id = "6" AND member_news_letter_type.deleted = "0") > 0 
														THEN "???" 
														ELSE "???" 
												END AS news_letter_type_6'),
										'foreign_visitor_country.country_name',
										'blacklist_reason.reason_name AS blacklist_reason_name',
										'available_time_interval.time_interval_name AS available_time_interval_name',
										'unavailable_time_interval.time_interval_name AS unavailable_time_interval_name',
										'member.note','member.description2','member.description',
										'member.create_user_name','member.create_time','member.modify_user_name','member.modify_time')
								->leftJoin('gender','gender.id','=','member.gender_id')
								->leftJoin('occupation','occupation.id','=','member.occupation_id')
								->leftJoin('professional_title','professional_title.id','=','member.professional_title_id')
								->leftJoin('sale_point AS m_sp','m_sp.id','=','member.sale_point_id')
								->leftJoin('foreign_visitor_country','foreign_visitor_country.id','=','member.foreign_visitor_country_id')
								->leftJoin('blacklist_reason','blacklist_reason.id','=','member.blacklist_reason_id')
								->leftJoin('time_interval AS available_time_interval','available_time_interval.id','=','member.available_time_interval_id')
								->leftJOin('time_interval AS unavailable_time_interval','unavailable_time_interval.id','=','member.unavailable_time_interval_id')
								->havingRaw($wherein2)
								//->orderBy('member.member_code','ASC')
								->paginate(session('max_page'))->appends($data->input());
		}
		return view('member.MemberInformationDetails', ['title' => MemberInformationDetailsController::get_title(),
					'item_id' => 'MemberInformationDetails',
					'userid' => session('userid'),
					'username' => session('username'),
					'modeldata' => session('modeldata'),
					'workdata' => session('workdata'),
					'sale_point_data' => SalePoint::select('id','sale_point_code','sale_point_name')->orderBy('sale_point_code')->get(),
					'brand_data' => Brand::select('id','code','name')->orderBy('code')->get(),
					'news_letter_type_data' => NewsLetterType::select('id','type_name')->orderBy('id')->get(),
					'row_data' => $row_data ]);
    }
    public function export(Request $data){
		ini_set("memory_limit","1028M");
		set_time_limit(0);
		$where = [];
		$wherein = "";
		$wherein2 = "1";
		$c_date = "";
		if($data->s_c_from_date != null){
			$c_date .= "sales.date >= '".$data->s_c_from_date."'";
		}
		if($data->s_c_to_date != null){
			if($c_date != null) $c_date .= " AND ";
			$c_date .= "sales.date <= '".$data->s_c_to_date." 23:59:59'";
		}
		if($c_date == null) $c_date = "1";

		if($data->s_member_code != null) $where[] = ['member.member_code', 'like',"%". $data->s_member_code."%"];
		if($data->s_member_name != null) $where[] = ['member.member_name', 'like',"%". $data->s_member_name."%"];

		if($data->s_y_member != null) $where[] = ['member.y_member', '=', $data->s_y_member];
		if($data->s_s_member != null) $where[] = ['member.s_member', '=', $data->s_s_member];

		if($data->s_sale_point != null) $wherein .= 'exists (	SELECT DISTINCT(sales.member) FROM sales 
																WHERE exists (	SELECT id FROM cash_register
																		WHERE sales.cash_register_id = cash_register.id
																		AND cash_register.sale_point_id = "'.$data->s_sale_point.'"
																) AND sales.member = member.id AND '.$c_date.')';
 		if($data->s_brand != null){
			if($wherein != '') $wherein .= ' AND ';
			$wherein .= 'exists (	SELECT DISTINCT(sales.member) FROM sales WHERE 
									exists (SELECT DISTINCT(salesdetails.sales_id) FROM salesdetails
										LEFT JOIN stockmaster ON stockmaster.stockid = salesdetails.stock_id
										WHERE salesdetails.sales_id = sales.id AND stockmaster.stock_brand_id = "'.$data->s_brand.'"
									) AND sales.member = member.id AND '.$c_date.')';
		}
		if($data->s_m_from_date != null) $where[] = ['member.create_time', '>=', $data->s_m_from_date];
		if($data->s_m_to_date != null) $where[] = ['member.create_time', '<=', $data->s_m_to_date." 23:59:59"];
		if($data->s_m_sale_point != null) $where[] = ['member.sale_point_id', '=', $data->s_m_sale_point];

		if($data->s_m_end_date != null) $where[] = ['member.member_end_date', '=', $data->s_m_end_date];
		if($data->s_year != null) $where[] = [DB::raw('substring(member.birthdate,1,4)'),$data->s_year];
		if($data->s_month != null) $where[] = [DB::raw('substring(member.birthdate,6,2)'),$data->s_month];

		if($data->s_is_receive_email != null) $where[] = ['member.is_receive_email', '=', $data->s_is_receive_email];
		if($data->s_is_receive_sms != null) $where[] = ['member.is_receive_sms', '=', $data->s_is_receive_sms];

		if($data->s_is_internet_member != null) $where[] = ['member.is_internet_member', '=', $data->s_is_internet_member];
			if($data->s_news_letter_type != null){
			if($wherein != '') $wherein .= ' AND ';
			$wherein .= 'member.id IN (SELECT member_id FROM member_news_letter_type WHERE news_letter_type_id = "'.$data->s_news_letter_type.'" GROUP BY member_id)';
		}
		if($data->s_is_no_accumulated != null) $where[] = ['member.is_no_accumulated', '=', $data->s_is_no_accumulated];
		if($wherein == null) $wherein = "1";
		if($data->s_from_amount != null) $wherein2 .= ' AND t_total >= '.$data->s_from_amount;
		if($data->s_to_amount != null) $wherein2 .= ' AND t_total <= '.$data->s_to_amount;
		if($data->s_from_accumulated_amount != null) $wherein2 .= ' AND total <= '.$data->s_from_accumulated_amount;
		if($data->s_to_accumulated_amount != null) $wherein2 .= ' AND total <= '.$data->s_to_accumulated_amount;
		//print_r($where);
		$basic_set = BasicSet::get();
		if($wherein2 == '1'){
			$row_datas = Member::where($where)
							->whereRaw($wherein)
							->select('member.member_code','member.transfer_member_code','member.internet_member_code',
									'member.member_name','gender.gender_name','member.birthdate','member.phone','member.home_phone',
									'member.email','member.company_name','member.company_uniform_number','member.company_areacode',
									'member.company_phone','member.company_ext','member.fax','occupation.occupation_name',
									'professional_title.professional_title_name',
									'member.contact_address_zip','member.contact_address_county','member.contact_address_city','member.contact_address_street',
									'member.residence_address_zip','member.residence_address_county','member.residence_address_city','member.residence_address_street',
									'member.company_address_zip','member.company_address_county','member.company_address_city','member.company_address_street',
									'member.transfer_total_consumption',
									'm_sp.sale_point_name',
									'member.y_member_time','member.member_end_date',
									DB::raw('CASE WHEN member.y_member = "1" THEN "????????????" ELSE "????????????" END AS y_member_name,
											CASE	member.s_member WHEN "1" THEN "????????????" WHEN "2" THEN "????????????"
													WHEN "3" THEN "??????" WHEN "4" THEN "????????????" WHEN "5" THEN "VIP"
													WHEN "6" THEN "??????" ELSE "???" END AS s_member_name,
											CASE WHEN member.is_no_accumulated = "1" THEN "???" ELSE "???" END AS is_no_accumulated,
											(	SELECT (SUM(IFNULL(salesdetails.subTotal,0)) + IFNULL(member.transfer_total_consumption,0)) AS t_total
												FROM sales USE INDEX(member)
												LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
												WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
											) AS t_total,
											(	SELECT IFNULL((CASE	WHEN (IFNULL(member.member_start_time,"") <> "") 
																	THEN SUM(IFNULL(salesdetails.subTotal,0)) 
																	ELSE (SUM(IFNULL(salesdetails.subTotal,0)) + IFNULL(member.transfer_total_consumption,0)) 
																END),0) AS total
												FROM sales USE INDEX(member)
												LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
												WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
												AND salesdetails.is_no_accumulated_amount = "0"
												AND (CASE	WHEN (IFNULL(member.member_start_time,"") <> "") 
															THEN sales.date > member.member_start_time
															ELSE 1
													 END)) AS total,
											(	SELECT IFNULL(SUM(member_points_log.points),0) FROM member_points_log
												WHERE deleted = "0" AND is_invalid = "0" AND member_id = member.id
												AND (date_format(effective_date,"%Y-%m-%d") >= date_format(now(),"%Y-%m-%d"))) AS points,
											CASE	WHEN (	SELECT original_value 
															FROM member_continue_log  
															WHERE member_id= member.id AND member_column="y_member_time" AND new_value IS NULL 
															ORDER BY create_time DESC LIMIT 1
															) IS NULL
													THEN (	SELECT SUM(IFNULL(salesdetails.subTotal,0))
															FROM sales USE INDEX(member)
															LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id
															WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
															AND salesdetails.is_no_accumulated_amount = "0" + member.transfer_total_consumption)
													ELSE (	SELECT SUM(IFNULL(salesdetails.subTotal,0))
															FROM sales USE INDEX(member)
															LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id
															WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
															AND salesdetails.is_no_accumulated_amount = "0")
											END AS cumulative_amount,
											CASE	WHEN member.y_member = "1"
													THEN (
														CASE WHEN (	SELECT COUNT(id) FROM sales WHERE is_return = "0" 
																	AND member = member.id AND sale_type in ("1","3","4") 
																	AND date > member.y_member_time AND substring(date,1,10) <= member.member_end_date
																	AND (CASE WHEN (IFNULL(member.member_start_date,"") <> "")
																		 THEN substring(sales.date,1,10) > member.member_start_date
																		 ELSE 1
																		 END)
																   ) >= "'.$basic_set[0]["member_continue_time"].'"
														THEN "<font color=green>?????????????????????</font><br>"
														ELSE (	CASE WHEN (	SELECT SUM(IFNULL(salesdetails.subTotal,0))
																			FROM sales USE INDEX(member)
																			LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
																			WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
																			AND sales.date > member.y_member_time AND substring(sales.date,1,10) <= member.member_end_date
																			AND salesdetails.is_no_accumulated_amount = "0"
																			AND (CASE WHEN (IFNULL(member.member_start_date,"") <> "") 
																	 THEN substring(sales.date,1,10) > member.member_start_date
																	 ELSE 1
																	 END)
																) >= "'.$basic_set[0]["member_continue_amount"].'"
																THEN "<font color=green>?????????????????????</font><br>"
																ELSE	CONCAT((SELECT CONCAT("<font color=red>???????????????",COUNT(id),"???????????????",'.$basic_set[0]["member_continue_time"].' - COUNT(id),"??????????????????????????????</font><br>")
																				FROM sales
																				WHERE is_return = "0" AND member = member.id AND sale_type in ("1","3","4") 
																				AND date > member.y_member_time AND substring(date,1,10) <= member.member_end_date
																				AND (CASE	WHEN (IFNULL(member.member_start_date,"") <> "")
																							THEN substring(sales.date,1,10) > member.member_start_date
																							ELSE 1
																							END)
																				),
																				CASE	WHEN (	SELECT SUM(IFNULL(salesdetails.subTotal,0))
																								FROM sales USE INDEX(member)
																								LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
																								WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
																								AND sales.date > member.y_member_time AND substring(sales.date,1,10) <= member.member_end_date
																								AND salesdetails.is_no_accumulated_amount = "0"
																								AND (CASE	WHEN (IFNULL(member.member_start_date,"") <> "") 
																											THEN substring(sales.date,1,10) > member.member_start_date
																											ELSE 1
																											END)
																								) IS NULL
																						THEN CONCAT("<font color=red>???????????????0???????????????",'.$basic_set[0]["member_continue_amount"].',"??????????????????????????????</font><br>")
																						ELSE (	SELECT	CONCAT("<font color=red>???????????????",SUM(IFNULL(ROUND(salesdetails.subTotal),0)),"???????????????",'.$basic_set[0]["member_continue_amount"].' - SUM(IFNULL(ROUND(salesdetails.subTotal),0)),"??????????????????????????????</font><br>")
																								FROM sales USE INDEX(member)
																								LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
																								WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
																								AND sales.date > member.y_member_time AND substring(sales.date,1,10) <= member.member_end_date
																								AND salesdetails.is_no_accumulated_amount = "0"
																								AND (CASE	WHEN (IFNULL(member.member_start_date,"") <> "") 
																											THEN substring(sales.date,1,10) > member.member_start_date
																											ELSE 1
																											END)
																								)
																						END
																		)
																END
														)
														END
													)
													ELSE ""
											END AS memberlevelinfo,
											CASE	WHEN member.transfer_member_code IS NOT NULL
													THEN (	SELECT COUNT(DISTINCT(member_oldsales.salesno)) FROM member_oldsales 
															WHERE member_oldsales.member_code = member.transfer_member_code OR member_oldsales.member_code_new = member.transfer_member_code)
													ELSE "0"
											END AS old_total,
											(	SELECT count(id) FROM sales USE INDEX(member) 
												WHERE sale_type IN ("1","3","4") AND sales.member = member.id AND '.$c_date.') AS consumptions_counts,
											(	SELECT date FROM sales USE INDEX(member) WHERE member = member.id AND '.$c_date.'
												ORDER BY date DESC LIMIT 0,1) AS consumptionsdate,
											CASE	WHEN member.is_black = "1" THEN "???" ELSE "???" END AS is_black,
											CASE	WHEN member.is_receive_email = "1" THEN "???" ELSE "???" END AS is_receive_email,
											CASE	WHEN member.is_receive_sms = "1" THEN "???" ELSE "???" END AS is_receive_sms,
											CASE	WHEN member.is_internet_member = "1" THEN "???" ELSE "???" END AS is_internet_member,
											CASE	WHEN member.is_receive_message = "1" THEN "???" ELSE "???" END AS is_receive_message,
											CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
														LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
														WHERE news_letter_type.id = "1" AND member_news_letter_type.deleted = "0") > 0 
													THEN "???" 
													ELSE "???" 
											END AS news_letter_type_1,
											CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
															LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
															WHERE news_letter_type.id = "2" AND member_news_letter_type.deleted = "0") > 0 
													THEN "???" 
													ELSE "???" 
											END AS news_letter_type_2,
											CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
															LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
															WHERE news_letter_type.id = "3" AND member_news_letter_type.deleted = "0") > 0 
													THEN "???" 
													ELSE "???" 
											END AS news_letter_type_3,
											CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
															LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
															WHERE news_letter_type.id = "4" AND member_news_letter_type.deleted = "0") > 0 
													THEN "???" 
													ELSE "???" 
											END AS news_letter_type_4,
											CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
															LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
															WHERE news_letter_type.id = "5" AND member_news_letter_type.deleted = "0") > 0 
													THEN "???" 
													ELSE "???" 
											END AS news_letter_type_5,
											CASE 	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
															LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
															WHERE news_letter_type.id = "6" AND member_news_letter_type.deleted = "0") > 0 
													THEN "???" 
													ELSE "???" 
											END AS news_letter_type_6'),
									'foreign_visitor_country.country_name',
									'blacklist_reason.reason_name AS blacklist_reason_name',
									'available_time_interval.time_interval_name AS available_time_interval_name',
									'unavailable_time_interval.time_interval_name AS unavailable_time_interval_name',
									'member.note','member.description2','member.description',
									'member.create_user_name','member.create_time','member.modify_user_name','member.modify_time')
							->leftJoin('gender','gender.id','=','member.gender_id')
							->leftJoin('occupation','occupation.id','=','member.occupation_id')
							->leftJoin('professional_title','professional_title.id','=','member.professional_title_id')
							->leftJoin('sale_point AS m_sp','m_sp.id','=','member.sale_point_id')
							->leftJoin('foreign_visitor_country','foreign_visitor_country.id','=','member.foreign_visitor_country_id')
							->leftJoin('blacklist_reason','blacklist_reason.id','=','member.blacklist_reason_id')
							->leftJoin('time_interval AS available_time_interval','available_time_interval.id','=','member.available_time_interval_id')
							->leftJOin('time_interval AS unavailable_time_interval','unavailable_time_interval.id','=','member.unavailable_time_interval_id')
							->orderBy('member.member_code','ASC')
							//->limit(30)
							//->toSql();
							//->get();
							// ->chunk(10, function($rows){
								// dd($rows);
								// foreach($rows as $row){
									
								// }
							// });
							->cursor();
		}else{
			$row_datas = Member::where($where)
							->whereRaw($wherein)
							->select('member.member_code','member.transfer_member_code','member.internet_member_code',
									'member.member_name','gender.gender_name','member.birthdate','member.phone','member.home_phone',
									'member.email','member.company_name','member.company_uniform_number','member.company_areacode',
									'member.company_phone','member.company_ext','member.fax','occupation.occupation_name',
									'professional_title.professional_title_name',
									'member.contact_address_zip','member.contact_address_county','member.contact_address_city','member.contact_address_street',
									'member.residence_address_zip','member.residence_address_county','member.residence_address_city','member.residence_address_street',
									'member.company_address_zip','member.company_address_county','member.company_address_city','member.company_address_street',
									'member.transfer_total_consumption',
									'm_sp.sale_point_name',
									'member.y_member_time','member.member_end_date',
									DB::raw('CASE WHEN member.y_member = "1" THEN "????????????" ELSE "????????????" END AS y_member_name,
											CASE	member.s_member WHEN "1" THEN "????????????" WHEN "2" THEN "????????????"
													WHEN "3" THEN "??????" WHEN "4" THEN "????????????" WHEN "5" THEN "VIP"
													WHEN "6" THEN "??????" ELSE "???" END AS s_member_name,
											CASE WHEN member.is_no_accumulated = "1" THEN "???" ELSE "???" END AS is_no_accumulated,
											(	SELECT (SUM(IFNULL(salesdetails.subTotal,0)) + IFNULL(member.transfer_total_consumption,0)) AS t_total
												FROM sales USE INDEX(member)
												LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
												WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
											) AS t_total,
											(	SELECT IFNULL((CASE	WHEN (IFNULL(member.member_start_time,"") <> "") 
																	THEN SUM(IFNULL(salesdetails.subTotal,0)) 
																	ELSE (SUM(IFNULL(salesdetails.subTotal,0)) + IFNULL(member.transfer_total_consumption,0)) 
																END),0) AS total
												FROM sales USE INDEX(member)
												LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
												WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
												AND salesdetails.is_no_accumulated_amount = "0"
												AND (CASE	WHEN (IFNULL(member.member_start_time,"") <> "") 
															THEN sales.date > member.member_start_time
															ELSE 1
													 END)) AS total,
											(	SELECT IFNULL(SUM(member_points_log.points),0) FROM member_points_log
												WHERE deleted = "0" AND is_invalid = "0" AND member_id = member.id
												AND (date_format(effective_date,"%Y-%m-%d") >= date_format(now(),"%Y-%m-%d"))) AS points,
											CASE	WHEN (	SELECT original_value 
															FROM member_continue_log  
															WHERE member_id= member.id AND member_column="y_member_time" AND new_value IS NULL 
															ORDER BY create_time DESC LIMIT 1
															) IS NULL
													THEN (	SELECT SUM(IFNULL(salesdetails.subTotal,0))
															FROM sales USE INDEX(member)
															LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id
															WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
															AND salesdetails.is_no_accumulated_amount = "0" + member.transfer_total_consumption)
													ELSE (	SELECT SUM(IFNULL(salesdetails.subTotal,0))
															FROM sales USE INDEX(member)
															LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id
															WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
															AND salesdetails.is_no_accumulated_amount = "0")
											END AS cumulative_amount,
											CASE	WHEN member.y_member = "1"
													THEN (
														CASE WHEN (	SELECT COUNT(id) FROM sales WHERE is_return = "0" 
																	AND member = member.id AND sale_type in ("1","3","4") 
																	AND date > member.y_member_time AND substring(date,1,10) <= member.member_end_date
																	AND (CASE WHEN (IFNULL(member.member_start_date,"") <> "")
																		 THEN substring(sales.date,1,10) > member.member_start_date
																		 ELSE 1
																		 END)
																   ) >= "'.$basic_set[0]["member_continue_time"].'"
														THEN "<font color=green>?????????????????????</font><br>"
														ELSE (	CASE WHEN (	SELECT SUM(IFNULL(salesdetails.subTotal,0))
																			FROM sales USE INDEX(member)
																			LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
																			WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
																			AND sales.date > member.y_member_time AND substring(sales.date,1,10) <= member.member_end_date
																			AND salesdetails.is_no_accumulated_amount = "0"
																			AND (CASE WHEN (IFNULL(member.member_start_date,"") <> "") 
																	 THEN substring(sales.date,1,10) > member.member_start_date
																	 ELSE 1
																	 END)
																) >= "'.$basic_set[0]["member_continue_amount"].'"
																THEN "<font color=green>?????????????????????</font><br>"
																ELSE	CONCAT((SELECT CONCAT("<font color=red>???????????????",COUNT(id),"???????????????",'.$basic_set[0]["member_continue_time"].' - COUNT(id),"??????????????????????????????</font><br>")
																				FROM sales
																				WHERE is_return = "0" AND member = member.id AND sale_type in ("1","3","4") 
																				AND date > member.y_member_time AND substring(date,1,10) <= member.member_end_date
																				AND (CASE	WHEN (IFNULL(member.member_start_date,"") <> "")
																							THEN substring(sales.date,1,10) > member.member_start_date
																							ELSE 1
																							END)
																				),
																				CASE	WHEN (	SELECT SUM(IFNULL(salesdetails.subTotal,0))
																								FROM sales USE INDEX(member)
																								LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
																								WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
																								AND sales.date > member.y_member_time AND substring(sales.date,1,10) <= member.member_end_date
																								AND salesdetails.is_no_accumulated_amount = "0"
																								AND (CASE	WHEN (IFNULL(member.member_start_date,"") <> "") 
																											THEN substring(sales.date,1,10) > member.member_start_date
																											ELSE 1
																											END)
																								) IS NULL
																						THEN CONCAT("<font color=red>???????????????0???????????????",'.$basic_set[0]["member_continue_amount"].',"??????????????????????????????</font><br>")
																						ELSE (	SELECT	CONCAT("<font color=red>???????????????",SUM(IFNULL(ROUND(salesdetails.subTotal),0)),"???????????????",'.$basic_set[0]["member_continue_amount"].' - SUM(IFNULL(ROUND(salesdetails.subTotal),0)),"??????????????????????????????</font><br>")
																								FROM sales USE INDEX(member)
																								LEFT JOIN salesdetails USE INDEX(sales_id) ON salesdetails.sales_id = sales.id 
																								WHERE sales.member = member.id AND sales.sale_type IN ("1","3","4")
																								AND sales.date > member.y_member_time AND substring(sales.date,1,10) <= member.member_end_date
																								AND salesdetails.is_no_accumulated_amount = "0"
																								AND (CASE	WHEN (IFNULL(member.member_start_date,"") <> "") 
																											THEN substring(sales.date,1,10) > member.member_start_date
																											ELSE 1
																											END)
																								)
																						END
																		)
																END
														)
														END
													)
													ELSE ""
											END AS memberlevelinfo,
											CASE	WHEN member.transfer_member_code IS NOT NULL
													THEN (	SELECT COUNT(DISTINCT(member_oldsales.salesno)) FROM member_oldsales 
															WHERE member_oldsales.member_code = member.transfer_member_code OR member_oldsales.member_code_new = member.transfer_member_code)
													ELSE "0"
											END AS old_total,
											(	SELECT count(id) FROM sales USE INDEX(member) 
												WHERE sale_type IN ("1","3","4") AND sales.member = member.id AND '.$c_date.') AS consumptions_counts,
											(	SELECT date FROM sales USE INDEX(member) WHERE member = member.id AND '.$c_date.'
												ORDER BY date DESC LIMIT 0,1) AS consumptionsdate,
											CASE	WHEN member.is_black = "1" THEN "???" ELSE "???" END AS is_black,
											CASE	WHEN member.is_receive_email = "1" THEN "???" ELSE "???" END AS is_receive_email,
											CASE	WHEN member.is_receive_sms = "1" THEN "???" ELSE "???" END AS is_receive_sms,
											CASE	WHEN member.is_internet_member = "1" THEN "???" ELSE "???" END AS is_internet_member,
											CASE	WHEN member.is_receive_message = "1" THEN "???" ELSE "???" END AS is_receive_message,
											CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
														LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
														WHERE news_letter_type.id = "1" AND member_news_letter_type.deleted = "0") > 0 
													THEN "???" 
													ELSE "???" 
											END AS news_letter_type_1,
											CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
															LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
															WHERE news_letter_type.id = "2" AND member_news_letter_type.deleted = "0") > 0 
													THEN "???" 
													ELSE "???" 
											END AS news_letter_type_2,
											CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
															LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
															WHERE news_letter_type.id = "3" AND member_news_letter_type.deleted = "0") > 0 
													THEN "???" 
													ELSE "???" 
											END AS news_letter_type_3,
											CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
															LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
															WHERE news_letter_type.id = "4" AND member_news_letter_type.deleted = "0") > 0 
													THEN "???" 
													ELSE "???" 
											END AS news_letter_type_4,
											CASE	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
															LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
															WHERE news_letter_type.id = "5" AND member_news_letter_type.deleted = "0") > 0 
													THEN "???" 
													ELSE "???" 
											END AS news_letter_type_5,
											CASE 	WHEN (	SELECT COUNT(member_news_letter_type.id) FROM news_letter_type
															LEFT JOIN member_news_letter_type ON member_news_letter_type.news_letter_type_id = news_letter_type.id
															WHERE news_letter_type.id = "6" AND member_news_letter_type.deleted = "0") > 0 
													THEN "???" 
													ELSE "???" 
											END AS news_letter_type_6'),
									'foreign_visitor_country.country_name',
									'blacklist_reason.reason_name AS blacklist_reason_name',
									'available_time_interval.time_interval_name AS available_time_interval_name',
									'unavailable_time_interval.time_interval_name AS unavailable_time_interval_name',
									'member.note','member.description2','member.description',
									'member.create_user_name','member.create_time','member.modify_user_name','member.modify_time')
							->leftJoin('gender','gender.id','=','member.gender_id')
							->leftJoin('occupation','occupation.id','=','member.occupation_id')
							->leftJoin('professional_title','professional_title.id','=','member.professional_title_id')
							->leftJoin('sale_point AS m_sp','m_sp.id','=','member.sale_point_id')
							->leftJoin('foreign_visitor_country','foreign_visitor_country.id','=','member.foreign_visitor_country_id')
							->leftJoin('blacklist_reason','blacklist_reason.id','=','member.blacklist_reason_id')
							->leftJoin('time_interval AS available_time_interval','available_time_interval.id','=','member.available_time_interval_id')
							->leftJOin('time_interval AS unavailable_time_interval','unavailable_time_interval.id','=','member.unavailable_time_interval_id')
							->havingRaw($wherein2)
							->orderBy('member.member_code','ASC')
							//->limit(30)
							//->toSql();
							//->get();
							// ->chunk(10, function($rows){
								// dd($rows);
								// foreach($rows as $row){
									
								// }
							// });
							->cursor();
		}
		$now_date = date('YmdHis');
		//echo memory_get_usage().'=A=<br>';
		//return;
		foreach ($row_datas as $i =>$row_data){
			$temp[] = ['??????' => ($i+1),
						'????????????'=>(string)$row_data->member_code,
						'????????????'=>(string)$row_data->transfer_member_code,
						'????????????'=>(string)$row_data->internet_member_code,
						'????????????'=>(string)$row_data->y_member_name,
						'????????????'=>(string)$row_data->s_member_name,
						'???????????????'=>$row_data->is_no_accumulated,
						'????????????'=>(string)$row_data->member_name,
						'??????'=>(string)$row_data->gender_name,
						'??????'=>(string)$row_data->birthdate,
						'????????????'=>(string)$row_data->phone,
						'??????'=>(string)$row_data->home_phone,
						'Email'=>(string)$row_data->email,
						'????????????'=>(string)$row_data->company_name,
						'????????????'=>(string)$row_data->company_uniform_number,
						'???????????????'=>(string)'??????'.$row_data->company_areacode.'??????'.$row_data->company_phone.'??????'.$row_data->company_ext,
						'??????'=>(string)$row_data->fax,
						'????????????'=>(string)$row_data->professional_title_name,
						'??????'=>(string)$row_data->occupation_name,
						'????????????'=>(string)$row_data->contact_address_zip.$row_data->contact_address_county.$row_data->contact_address_city.$row_data->contact_address_street,
						'????????????'=>(string)$row_data->residence_address_zip.$row_data->residence_address_county.$row_data->residence_address_city.$row_data->residence_address_street,
						'????????????'=>(string)$row_data->company_address_zip.$row_data->company_address_county.$row_data->company_address_city.$row_data->company_address_street,
						'????????????'=>(int)$row_data->transfer_total_consumption,
						'??????????????????'=>(int)$row_data->t_total,
						'??????????????????'=>(int)$row_data->total,
						'??????????????????'=>(int)$row_data->a,
						'????????????'=>(int)$row_data->points,
						'????????????'=>(string)$row_data->sale_point_name,
						'????????????'=>(string)$row_data->create_time,
						'?????????????????????'=>(string)substr($row_data ->y_member_time,0,10),
						'?????????????????????'=>(string)$row_data->member_end_date,
						'????????????'=>(string)$row_data->memberlevelinfo,
						'???????????????'=>(int)$row_data->old_total,
						'????????????'=>(int)$row_data->consumptions_counts,
						'????????????????????????'=>(string)$row_data->consumptionsdate,
						'????????????'=>(string)$row_data->country_name,
						'???????????????'=>(string)$row_data->is_black,
						'???????????????'=>(string)$row_data->blacklist_reason_name,
						'?????????'=>(string)$row_data->introduce_member_name,
						'??????'=>(string)$row_data->a,
						'??????????????????'=>(string)$row_data->available_time_interval_name,
						'?????????????????????'=>(string)$row_data->unavailable_time_interval_name,
						'???????????????Email'=>(string)$row_data->is_receive_email,
						'?????????????????????'=>(string)$row_data->is_receive_sms,
						'??????????????????????????????'=>(string)$row_data->is_internet_member,
						'??????????????????????????????'=>(string)$row_data->is_receive_message,
						'???????????????(??????)'=>(string)$row_data->news_letter_type_1,
						'???????????????(??????)'=>(string)$row_data->news_letter_type_2,
						'???????????????(??????)'=>(string)$row_data->news_letter_type_3,
						'???????????????(??????)'=>(string)$row_data->news_letter_type_4,
						'???????????????(??????)'=>(string)$row_data->news_letter_type_5,
						'???????????????(??????)'=>(string)$row_data->news_letter_type_6,
						'????????????'=>(string)$row_data->note,
						'??????'=>(string)$row_data->description2,
						'??????'=>(string)$row_data->description,
						'????????????'=>(string)$row_data->create_user_name,
						'????????????'=>(string)$row_data->create_time,
						'????????????'=>(string)$row_data->modify_user_name,
						'????????????'=>(string)$row_data->modify_time ];
			$aaa = memory_get_usage();
		};
		//unset($row_datas);
		//if(count($temp)>1){
			$temp = collect($temp);
			// $style = (new StyleBuilder())
				// ->setFontBold()
				//->setBackgroundColor(Color::YELLOW)
				// ->build();
			return (new FastExcel($temp))
				// ->headerStyle($style)
				->download('MemberInformationDetails_'.$now_date.'.xlsx');
			//return Excel::download($excel, 'MemberSalesDetails_'.$now_date.'.xlsx');
		//}
		//echo $aaa.'___'.memory_get_usage();
    }
	
}
