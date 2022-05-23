<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\User;
use App\Models\Product;
use App\Models\Locations;
use App\Models\Supplier;
use App\Models\TaxCategory;
use App\Models\Brand;
use App\Models\BasicSet;
use App\Models\Bom;
use App\Models\Member;
use App\Models\MemberPointLog;
use App\Models\MemberContinueLog;
use App\Models\PromotionYMemberAmountSet;
use App\Models\Sales;
use App\Models\SalesDetails;
use App\Models\SalesPayments;
use App\Models\SalePointPayments;
use App\Models\CreditCard;
use App\Models\ProductKeyPage;

use App\Http\Controllers\api\SalesDayController;
use App\Models\PosDayEndLog;
use App\Models\PosDayEndLogDetails;
use App\Models\PosShiftLog;
use App\Models\PosShiftLogDetails;

use App\Models\StockMoves;
use App\Models\LocStock;
use App\Models\SalesTemp;
use App\Models\SalePoint;
use App\Models\CashRegister;
use App\Models\Invoice;
use App\Models\InvoiceDetails;
use App\Models\InvoicePage;
use App\Models\InvoicePageItem;
use App\Models\InvoicePrint;
use App\Models\ElectricInvoiceInfo;
use App\Models\Company;
use App\Models\BotData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webpatser\Uuid\Uuid;

use DB;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
// require 'autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\CupsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use GuzzleHttp\Client;

class PosController extends Controller
{
    function Replace_Symbol_Decode($sParameters){
		if(!empty($sParameters)){
			$sParameters = str_replace('%2d','-',$sParameters);
			$sParameters = str_replace('%5f','_',$sParameters);
			$sParameters = str_replace('%2e','.',$sParameters);
			$sParameters = str_replace('%21','!',$sParameters);
			$sParameters = str_replace('%7e','~',$sParameters);
			$sParameters = str_replace('%2a','*',$sParameters);
			$sParameters = str_replace('%28','(',$sParameters);
			$sParameters = str_replace('%29',')',$sParameters);
			$sParameters = str_replace('%20',' ',$sParameters);
			$sParameters = str_replace('%40','@',$sParameters);
			$sParameters = str_replace('%23','#',$sParameters);
			$sParameters = str_replace('%24','$',$sParameters);
			$sParameters = str_replace('%25','%',$sParameters);
			$sParameters = str_replace('%5e','^',$sParameters);
			$sParameters = str_replace('%26','&',$sParameters);
			$sParameters = str_replace('%3d','=',$sParameters);
			$sParameters = str_replace('%2b','+',$sParameters);
			$sParameters = str_replace('%3b',';',$sParameters);
			$sParameters = str_replace('%3f','?',$sParameters);
			$sParameters = str_replace('%2f','/',$sParameters);
			$sParameters = str_replace('%5c','\\',$sParameters);
			$sParameters = str_replace('%3e','>',$sParameters);
			$sParameters = str_replace('%3c','<',$sParameters);
			$sParameters = str_replace('%25','%',$sParameters);
			$sParameters = str_replace('%60','`',$sParameters);
			$sParameters = str_replace('%5b','[',$sParameters);
			$sParameters = str_replace('%5d',']',$sParameters);
			$sParameters = str_replace('%7b','{',$sParameters);
			$sParameters = str_replace('%7d','}',$sParameters);
			$sParameters = str_replace('%3a',':',$sParameters);
			$sParameters = str_replace('%27',"'",$sParameters);
			$sParameters = str_replace('%22','"',$sParameters);
			$sParameters = str_replace('%2c',',',$sParameters);
			$sParameters = str_replace('%7c','|',$sParameters);
		}
		return $sParameters;
	}
	function g_inv_open($v2,$ecinfo,$data_arr){
		$ch = curl_init();
		$url_path = '/B2CInvoice/Issue';
		$url = $ecinfo['url_base'].$url_path;
		//RelateNumber 銷售單號
		//CustomerID 會員代碼
		//CustomerIdentifier 統編
		//CustomerName 會員姓名
		//CustomerAddr 銷售點地址
		//CustomerPhone 會員手機
		//CustomerEmail 會員信箱
		//ClearanceMark 1：非經海關出口 2：經海關出口
		//Print 0：不列印 1：要列印
		// 注意事項：
		// 1. 當捐贈註記[Donation]=1(要捐贈)或載具類別[CarrierType]有值時，此參數請帶 0
		// 2. 當統一編號[CustomerIdentifier]有值時，此參數請帶 1
		//Donation 0：不捐贈 1：要捐贈
		// 注意事項：當統一編號[CustomerIdentifier]有值或載具類別[CarrierType]有值時，此參數請帶 0
		//LoveCode 當捐贈註記[Donation]=1(要捐贈)時，為必填。格式為阿拉伯數字為限，最少三碼，最多七碼，首位可以為零。
		//CarrierType 空字串：無載具 1：綠界電子發票載具 2：自然人憑證號碼 3：手機條碼載具
		// 注意事項：
		// 1. 當列印註記[Print] =1(要列印)或統一編號[CustomerIdentifier]有值時，請帶空字串
		// 2. 只有存在綠界電子發票載具(此參數帶 1)的發票，中獎後才能在ibon 列印領取
		//CarrierNum 載具條碼
		//TaxType 當字軌類別[InvType]為 07 時，則此欄位請填入 1、2、3 或 9 ； 當字軌類別[InvType]為 08 時，則此欄位請填入 3 或 4
		// 1：應稅。 2：零稅率。 3：免稅。 4：應稅（特種稅率） 9：混合應稅與免稅或零稅率時(限收銀機發票無法分辨時使用，且需通過申請核可)。
		//SalesAmount 發票總金額(含稅)
		//InvoiceRemark 發票備註
		//InvType 07:一般稅額08:特種稅額
		//vat 商品單價是否含稅 1：含稅 0：未稅
		//Items 陣列 START
		//ItemSeq 商品項次
		//ItemName 商品名稱
		//ItemCount 商品數量
		//ItemWord 商品單位
		//ItemPrice 商品單價
		//ItemTaxType 商品課稅別
		//ItemAmount 商品合計
		//ItemRemark 商品備註
		//Items 陣列 END
		$params =	json_encode(array(	"MerchantID"=>$ecinfo['MerchantID'],
							"RqHeader"=> array("Timestamp"=>time(),"Revision"=>"3.6.0"),
							"Data"=>PosController::Replace_Symbol_Decode((openssl_encrypt(urlencode(
										json_encode(
										array(	"MerchantID"=> $ecinfo['MerchantID'],
												"RelateNumber"=> $v2['sales_no'],
												"CustomerID"=> $v2['member_code'],
												"CustomerIdentifier"=> $v2['uniform_number'],
												"CustomerName"=> $v2['member_name'],
												"CustomerAddr"=> "Normal",
												"CustomerPhone"=> "",
												"CustomerEmail"=> "帳號@gmail.com",
												"ClearanceMark"=> "1",
												"Print"=> $v2['is_print'],
												"Donation"=> ($v2['love_code']=='')?"0":"1",
												"LoveCode"=> ($v2['love_code']=='')?"":$v2['love_code'],
												"CarrierType"=> ($v2['mobile_device']=='')?"":"3",
												"CarrierNum"=> $v2['mobile_device'],
												"TaxType"=> $v2['inv_type'],
												"SalesAmount"=> $v2['total'],
												"InvoiceRemark"=> "",
												"InvType"=> "07",
												"vat"=> "1",
												"Items"=> $data_arr
										))
								), 'AES-128-CBC', $ecinfo['key'], 0, $ecinfo['iv'])))
					));
		$get_header[] = "Content-Type: application/json";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $get_header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST ,'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		$data = json_decode($data, TRUE);
		curl_close($ch);
		return $data;
	}
	function g_inv_invalid($v2,$ecinfo){
		$ch = curl_init();
		$url_path = '/B2CInvoice/Invalid';
		$url = $ecinfo['url_base'].$url_path;
		$params =	json_encode(array(	"MerchantID"=>$ecinfo['MerchantID'],
							"RqHeader"=> array("Timestamp"=>time(),"Revision"=>"3.6.0"),
							"Data"=>PosController::Replace_Symbol_Decode((openssl_encrypt(urlencode(
										json_encode(
										array(	"MerchantID"=> $ecinfo['MerchantID'],
												"InvoiceNo"=> $v2['invoice_no'],
												"InvoiceDate"=> $v2['invoice_date'],
												"Reason"=> "退貨",
										))
								), 'AES-128-CBC', $ecinfo['key'], 0, $ecinfo['iv'])))
					));
		$get_header[] = "Content-Type: application/json";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $get_header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST ,'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		$data = json_decode($data, TRUE);
		curl_close($ch);
		return $data;
	}
	function g_inv_allowance($v2,$ecinfo,$data_arr){
		$ch = curl_init();
		$url_path = '/B2CInvoice/Allowance';
		$url = $ecinfo['url_base'].$url_path;
		$params =	json_encode(array(	"MerchantID"=>$ecinfo['MerchantID'],
							"RqHeader"=> array("Timestamp"=>time(),"Revision"=>"3.6.0"),
							"Data"=>PosController::Replace_Symbol_Decode((openssl_encrypt(urlencode(
										json_encode(
										array(	"MerchantID"=> $ecinfo['MerchantID'],
												"InvoiceNo"=> $v2['invoice_no'],
												"InvoiceDate"=> $v2['invoice_date'],
												"AllowanceNotify"=> "N",
												"AllowanceAmount"=> $v2['total'],
												"Items"=> $data_arr
										))
								), 'AES-128-CBC', $ecinfo['key'], 0, $ecinfo['iv'])))
					));
		$get_header[] = "Content-Type: application/json";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $get_header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST ,'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		$data = json_decode($data, TRUE);
		curl_close($ch);
		return $data;
	}
	function g_inv_getprint($v2,$ecinfo){
		$ch = curl_init();
		$url_path = '/B2CInvoice/GetIssue';
		$url = $ecinfo['url_base'].$url_path;
		$params =	json_encode(array(	"MerchantID"=>$ecinfo['MerchantID'],
							"RqHeader"=> array("Timestamp"=>time(),"Revision"=>"3.6.0"),
							"Data"=>PosController::Replace_Symbol_Decode((openssl_encrypt(urlencode(
										json_encode(
										array(	"MerchantID"=> $ecinfo['MerchantID'],
												"InvoiceNo"=> $v2['invoice_no'],
												"InvoiceDate"=> $v2['invoice_date'],
										))
								), 'AES-128-CBC', $ecinfo['key'], 0, $ecinfo['iv'])))
					));
		$get_header[] = "Content-Type: application/json";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $get_header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST ,'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		$data = json_decode($data, TRUE);
		curl_close($ch);
		return $data;
	}
	function getmonth($month){
		$str = '';
		switch($month){
			case '01':
				$str = '1';
			break;
			case '02':
				$str = '1';
			break;
			case '03':
				$str = '3';
			break;
			case '04':
				$str = '3';
			break;
			case '05':
				$str = '5';
			break;
			case '06':
				$str = '5';
			break;
			case '07':
				$str = '7';
			break;
			case '08':
				$str = '7';
			break;
			case '09':
				$str = '9';
			break;
			case '10':
				$str = '9';
			break;
			case '11':
				$str = '11';
			break;
			case '12':
				$str = '11';
			break;
		}
		return $str;
	}
	public function index(Request $data){
		// $httpClient = new \GuzzleHttp\Client();
		// $headers = ['Content-Type' => 'application/json'];
		// $params = [];
		// $params['headers'] = ['Content-Type' => 'application/json'];
		// $params['form_params'] = array(	"MerchantID"=>$ecinfo['MerchantID'],
							// "RqHeader"=> array("Timestamp"=>time(),"Revision"=>"3.6.0"),
							// "Data"=>PosController::Replace_Symbol_Decode((openssl_encrypt(urlencode(
										// json_encode(
										// array(	"MerchantID"=> $ecinfo['MerchantID'],
												// "RelateNumber"=> $v2['sales_no'],
												// "CustomerID"=> "",
												// "CustomerIdentifier"=> "",
												// "CustomerName"=> "綠界科技股份有限公司",
												// "CustomerAddr"=> "106 台北市南港區發票一街 1 號 1 樓",
												// "CustomerPhone"=> "",
												// "CustomerEmail"=> "test@ecpay.com.tw",
												// "ClearanceMark"=> "1",
												// "Print"=> "1",
												// "Donation"=> "0",
												// "LoveCode"=> "",
												// "CarrierType"=> "",
												// "CarrierNum"=> "",
												// "TaxType"=> "1",
												// "SalesAmount"=> 100,
												// "InvoiceRemark"=> "發票備註",
												// "InvType"=> "07",
												// "vat"=> "1",
												// "Items"=> array(
														// array(	"ItemSeq"=> 1,
																// "ItemName"=> "item01",
																// "ItemCount"=> 1,
																// "ItemWord"=> "件",
																// "ItemPrice"=> 50,
																// "ItemTaxType"=> "1",
																// "ItemAmount"=> 50,
																// "ItemRemark"=> "item01_desc"),
														// array(	"ItemSeq"=> 2,
																// "ItemName"=> "item02",
																// "ItemCount"=> 1,
																// "ItemWord"=> "件",
																// "ItemPrice"=> 20,
																// "ItemTaxType"=> "1",
																// "ItemAmount"=> 20,
																// "ItemRemark"=> "item02_desc"),
														// array(	"ItemSeq"=> 3,
																// "ItemName"=> "item03",
																// "ItemCount"=> 1,
																// "ItemWord"=> "件",
																// "ItemPrice"=> 30,
																// "ItemTaxType"=> "1",
																// "ItemAmount"=> 30,
																// "ItemRemark"=> "item03_desc")
													// )
										// ))
								// ), 'AES-128-CBC', $ecinfo['key'], 0, $ecinfo['iv'])))
					// );
		// $response = $httpClient->post("https://einvoice-stage.ecpay.com.tw/B2CInvoice/Issue", $params);
        // $response = $httpClient->request('POST', "https://einvoice-stage.ecpay.com.tw/B2CInvoice/Issue",$headers,json_encode($params));
		// if ($response->getBody()) {
			// echo ( $response->getBody() );
		// }
		// return;
		$row_data = [];
		return view('pos.Pos', ['title' => '',
					'item_id' => 'Pos',
					'userid' => session('userid'),
					'username' => session('username'),
					'modeldata' => session('modeldata'),
					'workdata' => session('workdata'),
					'locations_data' => Locations::get(),
					'payments_data' => SalePointPayments::orderBy(DB::raw('round(sale_point_payments_type_id)'))->get(),
					'points_conversion_money' => BasicSet::get(),
					'row_data' => $row_data ]);
    }
    function check_str($str,$key){
		if(strpos($str,$key)!==false){
			return true; 
		}else{
			return false;
		}
    }
    public function items($items,$id=null,Request $data){
		$cash_register_id = '';
		$msg = '';
		$head_data = [];
		$head_data2 = [];
		$row_data = [];
		$row_data2 = [];
		$where = [];
		switch ($items){
			case 'check_info':
				$check_data = CashRegister::where([['cash_register.computerName','=',$id],['cash_register_employee.userid','=',session('userid')]])
								->leftJoin('cash_register_employee','cash_register_employee.cash_register_id','=','cash_register.id')
								->leftJoin('sale_point','sale_point.id','=','cash_register.sale_point_id')
								->select('cash_register.id','cash_register.current_no','cash_register.credit_card_com',
										'sale_point.id AS sp_id','sale_point.is_api',
										'sale_point.sale_point_code','sale_point.locations_loccode')
								->get();
				if(count($check_data) == 1){
					//給非使用綠界的門市，自動抓號
					$inv_data = Invoice::where([['invoice.invoice_type_id','=','5'],['invoice.year','=',(date('Y')-1911)],
												['invoice.month','=',((date('n')%2==1)?date('n'):(date('n')-1))],['invoice.cash_register_id','=',$check_data[0]->id],
												['invoice_details.is_use','=','0'],['invoice_details.is_disabled','=','0'] ])
										->leftJoin('invoice_details','invoice_details.invoice_id','=','invoice.id')
										->select('invoice_details.no')
										->orderBy('invoice_details.no')
										->limit(1)
										->get();
					$sale_data = DB::select("SELECT MAX(no) no FROM sales WHERE no LIKE 'PS".$check_data[0]->sale_point_code."%'");
					if ($sale_data[0]->no != ''){
						$temp_no = substr($sale_data[0]->no,-6);
						$temp_no++;
						$now_temp_no = substr("000000".$temp_no,-6);
					}else{
						$now_temp_no = "000001";
					}
					$sale_data2 = DB::select("SELECT MAX(no) no FROM sales WHERE no LIKE 'PP".$check_data[0]->sale_point_code."%'");
					if ($sale_data2[0]->no != ''){
						$temp_no = substr($sale_data2[0]->no,-6);
						$temp_no++;
						$pre_now_temp_no = substr("000000".$temp_no,-6);
					}else{
						$pre_now_temp_no = "000001";
					}
					//增加是否要撈檔期促銷的判斷
					$promotion_data = DB::select("	SELECT CASE WHEN COUNT(promotion_project.id) = 0 THEN '0' ELSE '1' END AS total
													FROM promotion_project 
													LEFT JOIN promotion_porject_sale_point ON promotion_porject_sale_point.promotion_project_id = promotion_project.id
													WHERE (promotion_porject_sale_point.sale_point_id IS NULL 
													OR promotion_porject_sale_point.sale_point_id = '".$check_data[0]->sp_id."') 
													AND '".date('Y-m-d H:i:s')."' >= promotion_project.start_time
													AND '".date('Y-m-d H:i:s')."' <= promotion_project.stop_time 
													AND promotion_project.is_locked = '0' AND promotion_project.deleted = '0'");
					//因為綠界所以不自動抓發票號碼
					// if(count($inv_data) > 0){
					if($check_data[0]->is_api == '1'){
						// return 'ok@'.$check_data[0]->id.'@'.$check_data[0]->credit_card_com.'@'.$check_data[0]->locations_loccode.'@'.$check_data[0]->sp_id.'@PS'.$check_data[0]->sale_point_code.$now_temp_no.'@PP'.$check_data[0]->sale_point_code.$pre_now_temp_no.'@'.$promotion_data[0]->total.'@'.$inv_data[0]->no;
						return 'ok@'.$check_data[0]->id.'@'.$check_data[0]->credit_card_com.'@'.$check_data[0]->locations_loccode.'@'.$check_data[0]->sp_id.'@PS'.$check_data[0]->sale_point_code.$now_temp_no.'@PP'.$check_data[0]->sale_point_code.$pre_now_temp_no.'@'.$promotion_data[0]->total.'@';
					}else if(count($inv_data) > 0){
						return 'ok@'.$check_data[0]->id.'@'.$check_data[0]->credit_card_com.'@'.$check_data[0]->locations_loccode.'@'.$check_data[0]->sp_id.'@PS'.$check_data[0]->sale_point_code.$now_temp_no.'@PP'.$check_data[0]->sale_point_code.$pre_now_temp_no.'@'.$promotion_data[0]->total.'@'.$inv_data[0]->no;
					}else{
						return 'ok@'.$check_data[0]->id.'@'.$check_data[0]->credit_card_com.'@'.$check_data[0]->locations_loccode.'@'.$check_data[0]->sp_id.'@PS'.$check_data[0]->sale_point_code.$now_temp_no.'@PP'.$check_data[0]->sale_point_code.$pre_now_temp_no.'@'.$promotion_data[0]->total.'@';
					}
				}else if(count($check_data) > 1){
					return 'error@請確認收銀機電腦名稱是否有  重複  配置為 '.$id;
				}else{
					return 'error@請確認收銀機的電腦名稱是否配置為 '.$id.' 及銷售員有無設置為登入的帳號';
				}
			break;
			case 'click_product':
				$row_data = ProductKeyPage::where([['sale_point_id','=',$id],['is_sys','=','1']])
											->orderBy('page')
											->get();
				$temp_data = ProductKeyPage::where([['goodskeypage.sale_point_id','=',$id],['goodskeypage.is_sys','=','1']])
											->leftJoin('goodskey','goodskey.goodskey_id','=','goodskeypage.id')
											->select('goodskeypage.page','goodskeypage.label','goodskey.id','goodskey.stockid',
													'goodskey.description','goodskey.alias','goodskey.color',
													'goodskey.rowIndex','goodskey.columnIndex','goodskey.goodskey_id',
													'goodskey.promotion_project_id','goodskey.promotion_selection_id')
											->orderBy('goodskeypage.page')
											->orderBy('goodskey.rowIndex')
											->orderBy('goodskey.columnIndex')
											->get();
				foreach($temp_data as $k => $v){
					$row_details_data[$v->page][$v->rowIndex][$v->columnIndex]['d_id'] = $v->id;
					$row_details_data[$v->page][$v->rowIndex][$v->columnIndex]['d_stockid'] = $v->stockid;
					$row_details_data[$v->page][$v->rowIndex][$v->columnIndex]['d_description'] = $v->description;
					$row_details_data[$v->page][$v->rowIndex][$v->columnIndex]['d_alias'] = $v->alias;
					$row_details_data[$v->page][$v->rowIndex][$v->columnIndex]['d_color'] = dechex($v->color);
					$row_details_data[$v->page][$v->rowIndex][$v->columnIndex]['d_page'] = $v->page;
					$row_details_data[$v->page][$v->rowIndex][$v->columnIndex]['d_rowIndex'] = $v->rowIndex;
					$row_details_data[$v->page][$v->rowIndex][$v->columnIndex]['d_columnIndex'] = $v->columnIndex;
					$row_details_data[$v->page][$v->rowIndex][$v->columnIndex]['d_goodskey_id'] = $v->goodskey_id;
					$row_details_data[$v->page][$v->rowIndex][$v->columnIndex]['d_promotion_project_id'] = $v->promotion_project_id;
					$row_details_data[$v->page][$v->rowIndex][$v->columnIndex]['d_promotion_selection_id'] = $v->promotion_selection_id;
				}
				return view('pos.ProductKey', ['title' => '',
							'item_id' => 'ProductKey',
							'userid' => session('userid'),
							'username' => session('username'),
							'modeldata' => session('modeldata'),
							'workdata' => session('workdata'),
							'row_data' => $row_data,
							'row_details_data' => $row_details_data ]);
			break;
			case 'payments':
				$row_data = SalePointPayments::where(function ($query) use ($id){
													$query->whereNull('payments_permit_sale_point.sale_point_id')
															->orWhere('payments_permit_sale_point.sale_point_id',$id);
												})
												->leftJoin('payments_permit_sale_point','payments_permit_sale_point.sale_point_payments_id','=','sale_point_payments.id')
												->select('sale_point_payments.*')
												->orderBy(DB::raw('round(sale_point_payments_type_id)'))->get();
				return view('pos.Payments', ['title' => '',
							'item_id' => 'Payments',
							'userid' => session('userid'),
							'username' => session('username'),
							'modeldata' => session('modeldata'),
							'workdata' => session('workdata'),
							'row_data' => $row_data ]);
			break;
			case 'sales_user':
				$row_data = User::where([['salepoint_id','=',$id],['blocked','=','0'],['is_master','=','1']])
									->select('userid','realname')
									->orderBy('userid')->get();
				$show_str = "<option value=''>請選銷售員</option>";
				foreach($row_data as $k => $v){
					$selected = '';
					// 取消預選
					// if(session('userid') == $v->userid) $selected = 'selected';
					$show_str .= "<option value='".$v->userid."' $selected>".$v->realname."</option>";
				}
				return $show_str;
			break;
			case 'card':
				$check_data = BotData::where('types','=',$items)
							->where('cash_register_id','=',$id)
							->where('create_time','>',$data->now_time)
							// ->where('id','=',$data->now_id)
							->get();
				if(count($check_data) == 0){
					$second = strtotime(date('Y-m-d H:i:s')) - strtotime($data->now_time);
					if($second > 60){
						return 'error@連線逾時';
					}else{
						sleep(1);
						return $this->items($items,$id,$data);
					}
				}else{
					//避免其他狀況都多補一個x
					$error_code = substr($check_data[0]->response,78,4);
					if($error_code == '0000'){
						return 'ok@'.$data->now_index.'@'.$data->payment_total.'@'.$data->payment_id.'@'.$data->payment_name.'@'.substr($check_data[0]->response,22,4).'@'.substr($check_data[0]->response,57,6);
					}else if($error_code =='0001' || $error_code =='001x'){
						return 'error@請檢查刷卡機或com設定有誤！代碼：0001';
					}else if($error_code == '0002' || $error_code =='002x'){
						return 'error@請致電銀行，刷卡失敗！代碼：0002';
					}else if($error_code == '0003' || $error_code =='003x'){
						return 'error@刷卡失敗！代碼：0003';
					}else if($error_code == '0004' || $error_code =='004x'){
						return 'error@功能未開啟，請聯繫銀行！代碼：0004';
					//銀行端檔案組錯 正確為 0005
					}else if($error_code == '0005' || $error_code =='005x'){
						return 'error@未傳入刷卡機！代碼：0005';
					}else if($error_code == '0006' || $error_code =='006x'){
						return 'error@交易進行中！代碼：0006';
					}else if($error_code == '0007' || $error_code =='007x'){
						return 'error@檢核碼錯誤！代碼：0007';
					}else if($error_code == '0008' || $error_code =='008x'){
						return 'error@檢核碼長度錯誤！代碼：0008';
					}else if($error_code == '0009' || $error_code =='009x'){
						return 'error@MD5錯誤！代碼：0009';
					}else{
						return 'error@'.$check_data[0]->response;
					}
				}
			break;
			case 'invoice_no':
				$check_data = Invoice::where([ 	['invoice.invoice_type_id','=','5'],['invoice.year','=',(date('Y')-1911)],
												['invoice.month','=',((date('n')%2==1)?date('n'):(date('n')-1))],
												['invoice_details.no','=',$data->s_invoice_no] ])
										->leftJoin('invoice_details','invoice_details.invoice_id','=','invoice.id')
										->select('invoice.invoice_type_id','invoice_details.is_use','invoice_details.is_disabled')
										->get();
				if(count($check_data) == 1){
					if($check_data[0]->is_use == 0 && $check_data[0]->is_disabled == 0){
						return 'ok';
					}else if($check_data[0]->is_disabled == 1){
						return 'error@發票號碼已作廢';
					}else if($check_data[0]->is_use == 1){
						return 'error@發票號碼已使用';
					}
				}else{
					return 'error@請確認輸入號碼是否存在';
				}
			break;
			case 'hand_invoice_no':
				$check_data = Invoice::where([ 	['invoice.year','=',(date('Y')-1911)],['invoice.month','=',((date('n')%2==1)?date('n'):(date('n')-1))],
												['invoice_details.no','=',$data->s_invoice_no] ])
										->whereIn('invoice.invoice_type_id', ['1','3'])
										->leftJoin('invoice_details','invoice_details.invoice_id','=','invoice.id')
										->select('invoice.invoice_type_id','invoice_details.is_use','invoice_details.is_disabled')
										->get();
				if(count($check_data) == 1){
					if($check_data[0]->is_use == 0 && $check_data[0]->is_disabled == 0){
						return 'ok';
					}else if($check_data[0]->is_disabled == 1){
						return 'error@手開發票已作廢';
					}else if($check_data[0]->is_use == 1){
						return 'error@手開發票已使用';
					}
				}else{
					return 'error@請確認輸入號碼是否存在';
				}
			break;
			case 'key_product':
				$salestypes = '01';
				$where[] = ['stockmaster.is_invalid','=','0'];
				$row_data = Product::where(function ($query) use ($data){
										$query->where('stockmaster.stockid','like',"%".$data->s_product_code."%")
												->orWhere('stockmaster.international_barcode','like',"%".$data->s_product_code."%")
												->orWhere('stockmaster.barcode','like',"%".$data->s_product_code."%");
									})
							->where($where)
							->leftJoin('stockcategory AS C','C.categoryid','=','stockmaster.categoryid')
							->leftJoin('prices AS p',function($join) use ($salestypes){
								$join->on('p.stockid','=','stockmaster.stockid');
								$join->on('p.typeabbrev','=',DB::raw("'".$salestypes."'"));
							})
							->leftJoin('taxcategories AS T','T.taxcatid','=','stockmaster.taxcatid')
							->selectRaw("stockmaster.stockid,stockmaster.description,stockmaster.taxcatid,stockmaster.is_no_discount,
									stockmaster.sale_start_date,stockmaster.sale_stop_date,stockmaster.mbflag,'' AS promotion_id,
									stockmaster.is_no_accumulated_amount,stockmaster.is_free_price,'0' AS is_bom,'' AS package_id,'' AS parent,
									IFNULL(p.price,0) AS price,IFNULL(p.price,0) AS se_price,'1' AS quantity,IFNULL(p.price,0) AS total,
									T.taxcatname,T.taxcatcode,T.taxrate,'' AS msg ")
							->get();
				if(count($row_data) > 1){
					$msg = ['msg'=>'error','data'=>'有複數符合商品資料'];
				}else if(count($row_data) == 0){
					$msg = ['msg'=>'error','data'=>'找不到符合商品資料'];
				}else{
					if(($row_data[0]->sale_start_date == '' || $row_data[0]->sale_start_date <= date('Y-m-d')) && 
						($row_data[0]->sale_stop_date == '' || $row_data[0]->sale_stop_date >= date('Y-m-d'))){
						// dd($data->s_is_salepoint_promotion);
						//先看是否商品組合 才做促銷跟A+B判斷
						if($row_data[0]->mbflag == 'K'){
							$row_data = Bom::where('bom.parent','=',$row_data[0]->stockid)
												->leftJoin('stockmaster','stockmaster.stockid','=','bom.component')
												->leftJoin('stockcategory AS C','C.categoryid','=','stockmaster.categoryid')
												->leftJoin('prices AS p',function($join) use ($salestypes){
													$join->on('p.stockid','=','stockmaster.stockid');
													$join->on('p.typeabbrev','=',DB::raw("'".$salestypes."'"));
												})
												->leftJoin('taxcategories AS T','T.taxcatid','=','stockmaster.taxcatid')
												->selectRaw("stockmaster.stockid,stockmaster.description,stockmaster.taxcatid,stockmaster.is_no_discount,'' AS promotion_id,
															stockmaster.is_no_accumulated_amount,stockmaster.is_free_price,'1' AS is_bom,uuid() AS package_id,bom.parent,
															bom.quantity,ROUND(bom.amount/bom.quantity,6) AS price,
															ROUND(bom.amount/bom.quantity,6) AS se_price,bom.amount AS total,
															T.taxcatname,T.taxcatcode,T.taxrate,'' AS msg ")
												->get();
							// dd($row_data->toJson());
						}else if($data->s_is_salepoint_promotion == '1'){
							//促銷內容的 一般折價跟一般折扣的判斷，滿額贈跟加價購在 小計後處理
							$promotion_data = DB::select("SELECT promotion_qty.id AS promotion_id 
																,promotion_qty.promotion_qty_type_id
																,promotion_qty.promotion_qty_code
																,promotion_qty.promotion_qty_name
																,promotion_qty.achieve_stockid
																,promotion_qty.achieve_categoryid 
																,promotion_qty.achieve_qty
																,promotion_qty.exclude_stockids 
																,promotion_qty.exclude_categoryids 
																,promotion_qty.achieve_total 
																,promotion_qty.promotion_qty_gift_type_id
																,promotion_qty.promotion_qty_achieve_total_type_id
																,promotion_qty.achieve_is_mix_count
																,promotion_qty.give_qty
																,promotion_qty.is_interval
																,promotion_qty.is_single
																,promotion_qty.discount_value
																,promotion_qty.sub_categoryid
																,promotion_qty.sub_achieve_total
																,promotion_qty_type.name AS promotion_qty_type_name
																,promotion_project_selection.is_single as sel_single
																,promotion_project_selection.is_parallel as sel_parallel
																,promotion_project_selection.promotion_project_id
														FROM promotion_qty
														LEFT JOIN promotion_qty_type ON promotion_qty_type.id = promotion_qty.promotion_qty_type_id 
														LEFT JOIN promotion_project_selection ON promotion_project_selection.promotion_selection_id = promotion_qty.id 
														LEFT JOIN promotion_project ON promotion_project.id = promotion_project_selection.promotion_project_id
														LEFT JOIN promotion_porject_sale_point ON promotion_porject_sale_point.promotion_project_id = promotion_project.id
														WHERE promotion_project_selection.promotion_selection_type = 'PQ' AND promotion_qty.deleted = '0'
														AND promotion_qty.promotion_qty_type_id <= '7' AND promotion_qty.achieve_stockid = '".$row_data[0]->stockid."'
														AND (promotion_porject_sale_point.sale_point_id IS NULL OR promotion_porject_sale_point.sale_point_id = '".$data->s_sale_point_id."') 
														AND '".date('Y-m-d H:i:s')."' >= promotion_project.start_time
														AND '".date('Y-m-d H:i:s')."' <= promotion_project.stop_time
														AND promotion_project.is_locked = '0' AND promotion_project.deleted = '0'
														ORDER BY promotion_project.transno,promotion_qty.promotion_qty_type_id");
							$dis_total = 0;
							foreach($promotion_data as $k => $v){
								if($v->promotion_qty_type_id != 6 && $v->promotion_qty_type_id != 7) continue;
								// dump($v);
								$is_pass = 1;
								if($v->promotion_qty_type_id == '6'){
									$now_total = $row_data[0]->price - round($row_data[0]->price*$v->discount_value/100);
									if($dis_total < $now_total){
										$dis_total = $now_total;
										$is_pass = 0;
									}
								}else if($v->promotion_qty_type_id == '7'){
									$now_total = $row_data[0]->price - $v->discount_value;
									if($dis_total < $now_total){
										$dis_total = $now_total;
										$is_pass = 0;
									}
								}
								if($is_pass == 1) continue;
								$row_data[0]->promotion_id = $v->promotion_id;
								$row_data[0]->msg = $v->promotion_qty_name.':'.$v->promotion_qty_type_name.$v->discount_value.(($v->promotion_qty_type_id == '6')?'%':'元');
							}
							if(count($promotion_data) > 0) $row_data[0]->price = $row_data[0]->se_price = $row_data[0]->total = $row_data[0]->price - $dis_total;
							// dd($row_data);
						}
						$msg = ['msg'=>'ok','data'=>json_decode($row_data->toJson())];
					}else{
						$msg = ['msg'=>'error','data'=>'商品有效期限已過'];
					}
				}
				return $msg;
			break;
			case 'product':
				$salestypes = '01';
				$where[] = ['stockmaster.is_invalid','=','0'];
				if($data->s_product_name != null) $where[] = ['stockmaster.description', 'like', "%".$data->s_product_name."%"];
				$row_data = Product::where(function ($query) use ($data){
										$query->where('stockmaster.stockid','like',"%".$data->s_product."%")
												->orWhere('stockmaster.international_barcode','like',"%".$data->s_product."%")
												->orWhere('stockmaster.barcode','like',"%".$data->s_product."%");
									})
							//->whereNotNull('p.price')
							//$noTax = round(小計/(1.05));
							//$noTaxPrice = round(小計/(1.05)/$v3['quantity'],2);
							//$tax = round(小計-$noTax);
							->where($where)
							->whereRaw(" (stockmaster.sale_start_date <= '".date('Y-m-d')."' OR stockmaster.sale_start_date IS NULL)
										AND (stockmaster.sale_stop_date >= '".date('Y-m-d')."' OR stockmaster.sale_stop_date IS NULL)")
							->leftJoin('stockcategory AS C','C.categoryid','=','stockmaster.categoryid')
							->leftJoin('prices AS p',function($join) use ($salestypes){
								$join->on('p.stockid','=','stockmaster.stockid');
								$join->on('p.typeabbrev','=',DB::raw("'".$salestypes."'"));
							})
							->leftJoin('taxcategories AS T','T.taxcatid','=','stockmaster.taxcatid')
							->select('stockmaster.stockid','stockmaster.description','stockmaster.taxcatid',
									'stockmaster.sale_start_date','stockmaster.sale_stop_date',
									'stockmaster.is_no_accumulated_amount','stockmaster.is_free_price','p.price',
									'T.taxcatname','T.taxcatcode','T.taxrate')
							->orderBy('stockmaster.stockid')
							->paginate(session('max_page'))->appends($data->input());
			break;
			case 'key_member':
				$basic_set = BasicSet::get();
				$row_data = Member::where('member.deleted', '=', '0')
									->where(function ($query) use ($data){
										$query->where('member.member_code', 'like', "%".$data->s_member."%")
												->orWhere('member.member_name', 'like', "%".$data->s_member_name."%")
												->orWhere('member.phone', 'like', "%".$data->s_phone."%")
												->orWhere('member.home_phone', 'like', "%".$data->s_phone."%");
									})
									->select('member.id','member.member_code','member.member_name',
											'member.email','member.phone','member.home_phone',
											'member.member_end_date','member.birthdate',
											'member.y_member','member.s_member',
											DB::raw('LPAD(month(now()),2,0) now_month'),
											DB::raw('CASE WHEN member.s_member = "5"
												THEN (SELECT COUNT(id) FROM sales WHERE is_return = "0"
													AND is_member_birthday_discount = "1" AND member = member.id
													AND substring(date,1,4) = year(now()) )
												ELSE (SELECT COUNT(id) FROM sales WHERE is_return = "0"
														AND is_employee_birthday_discount = "1" AND member = member.id
														AND substring(date,1,4) = year(now()) )
												END AS year_is_use_hb'),
											DB::raw('CASE WHEN member.y_member = "1" THEN "正式會員" ELSE "一般會員" END AS y_member_name'),
											DB::raw('CASE member.s_member WHEN "1" THEN "正式員工" WHEN "2" THEN "媒體公關"
												WHEN "3" THEN "廠商" WHEN "4" THEN "累積會員" WHEN "5" THEN "VIP"
												WHEN "6" THEN "散客" ELSE "無" END AS s_member_name'),'member.transfer_member_code',
											DB::raw('(	SELECT IFNULL((CASE	WHEN (IFNULL(member.member_start_time,"") <> "") 
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
																END)) AS total'),
											DB::raw('(	SELECT IFNULL(SUM(member_points_log.points),0) FROM member_points_log
														WHERE deleted = "0" AND is_invalid = "0" AND member_id = member.id
														AND (date_format(effective_date,"%Y-%m-%d") >= date_format(now(),"%Y-%m-%d"))) AS point,
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
																			THEN "<font color=green>已達續會資格。</font><br>"
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
																							THEN "<font color=green>已達續會資格。</font><br>"
																							ELSE	CONCAT((SELECT CONCAT("<font color=red>消費次數：",COUNT(id),"，尚需消費",'.$basic_set[0]["member_continue_time"].' - COUNT(id),"次，才具備續會資格。</font><br>")
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
																													THEN CONCAT("<font color=red>消費金額：0，尚需消費",'.$basic_set[0]["member_continue_amount"].',"元，才具備續會資格。</font><br>")
																													ELSE (	SELECT	CONCAT("<font color=red>消費金額：",SUM(IFNULL(ROUND(salesdetails.subTotal),0)),"，尚需消費",'.$basic_set[0]["member_continue_amount"].' - SUM(IFNULL(ROUND(salesdetails.subTotal),0)),"元，才具備續會資格。</font><br>")
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
														END AS memberlevelinfo') )
									->get();
				if(count($row_data) > 1){
					$msg = ['msg'=>'error','data'=>'有複數符合會員資料'];
				}else if(count($row_data) == 0){
					$msg = ['msg'=>'error','data'=>'找不到符合會員資料'];
				}else{
					$msg = ['msg'=>'ok','data'=>json_decode($row_data->toJson())];
				}
				return $msg;
			break;
			case 'member':
				$basic_set = BasicSet::get();
				$wherein = "1";
				$where[] = ['member.deleted', '=', '0'];
				if($data->s_member != null) $where[] = ['member.member_code', 'like', "%".$data->s_member."%"];
				if($data->s_member_name != null) $where[] = ['member.member_name', 'like', "%".$data->s_member_name."%"];
				if($data->s_phone != null) $wherein = "(member.phone like '%".$data->s_phone."%' OR member.home_phone like '%".$data->s_phone."%')";
				if($data->s_transfer_member_code != null) $where[] = ['member.transfer_member_code', 'like', "%".$data->s_transfer_member_code."%"];
				if($data->s_email != null) $where[] = ['member.email', 'like', "%".$data->s_email."%"];
				//LPAD(month(now()),2,0)
				$row_data = Member::where($where)->whereRaw($wherein)
									->select('member.id','member.member_code','member.member_name',
											'member.email','member.phone','member.home_phone',
											'member.member_end_date','member.birthdate',
											'member.y_member','member.s_member',
											DB::raw('LPAD(month(now()),2,0) now_month'),
											DB::raw('CASE WHEN member.s_member = "5"
												THEN (SELECT COUNT(id) FROM sales WHERE is_return = "0"
													AND is_member_birthday_discount = "1" AND member = member.id
													AND substring(date,1,4) = year(now()) )
												ELSE (SELECT COUNT(id) FROM sales WHERE is_return = "0"
														AND is_employee_birthday_discount = "1" AND member = member.id
														AND substring(date,1,4) = year(now()) )
												END AS year_is_use_hb'),
											DB::raw('CASE WHEN member.y_member = "1" THEN "正式會員" ELSE "一般會員" END AS y_member_name'),
											DB::raw('CASE member.s_member WHEN "1" THEN "正式員工" WHEN "2" THEN "媒體公關"
												WHEN "3" THEN "廠商" WHEN "4" THEN "累積會員" WHEN "5" THEN "VIP"
												WHEN "6" THEN "散客" ELSE "無" END AS s_member_name'),'member.transfer_member_code',
											DB::raw('(	SELECT IFNULL((CASE	WHEN (IFNULL(member.member_start_time,"") <> "") 
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
																END)) AS total'),
											DB::raw('(	SELECT IFNULL(SUM(member_points_log.points),0) FROM member_points_log
														WHERE deleted = "0" AND is_invalid = "0" AND member_id = member.id
														AND (date_format(effective_date,"%Y-%m-%d") >= date_format(now(),"%Y-%m-%d"))) AS point,
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
																			THEN "<font color=green>已達續會資格。</font><br>"
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
																							THEN "<font color=green>已達續會資格。</font><br>"
																							ELSE	CONCAT((SELECT CONCAT("<font color=red>消費次數：",COUNT(id),"，尚需消費",'.$basic_set[0]["member_continue_time"].' - COUNT(id),"次，才具備續會資格。</font><br>")
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
																													THEN CONCAT("<font color=red>消費金額：0，尚需消費",'.$basic_set[0]["member_continue_amount"].',"元，才具備續會資格。</font><br>")
																													ELSE (	SELECT	CONCAT("<font color=red>消費金額：",SUM(IFNULL(ROUND(salesdetails.subTotal),0)),"，尚需消費",'.$basic_set[0]["member_continue_amount"].' - SUM(IFNULL(ROUND(salesdetails.subTotal),0)),"元，才具備續會資格。</font><br>")
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
														END AS memberlevelinfo') )
									->orderBy('member.member_code','DESC')
									->paginate(session('max_page'))->appends($data->input());
			break;
			case 'del_sales_temp':
				DB::beginTransaction();
				try{
					SalesTemp::where('id','=',$id)->delete();
					DB::commit();
				}catch(\Exception $e){
					$msg = '儲存發生錯誤，請重新操作';
					DB::rollback();
				}
				if($msg == '') $msg = 'ok';
				return $msg;
			break;
			case 'sales_temp':
				DB::beginTransaction();
				try{
					// json_decode
					SalesTemp::insert(['id'=>UUID::generate(),'member_id'=>$data->member_id,'sale_type'=>$data->sale_type,
								'cash_register_id'=>$data->cash_register_id,'sales_data'=>json_encode($data->sales_data),
								'create_time'=>date('Y-m-d H:i:s'),'create_user_id'=>session('userid'),'create_user_name'=>session('username') ]);
					DB::commit();
				}catch(\Exception $e){
					$msg = '儲存發生錯誤，請重新操作';
					DB::rollback();
				}
				if($msg == '') $msg = 'ok';
				return $msg;
			break;
			case 'sales_back':
				$cash_register_id = $data->cash_register_id;
				$where = [];
				if($data->cash_register_id != null) $where[] = ['sales_temp.cash_register_id', '=', $data->cash_register_id];
				if($data->s_from_date != null) $where[] = ['sales_temp.create_time','>=',$data->s_from_date];
				if($data->s_to_date != null) $where[] = ['sales_temp.create_time','<=',$data->s_to_date." 23:59:59"];
				if($data->s_member_code != null) $where[] = ['member.member_code', 'like', "%".$data->s_member_code."%"];
				if($data->s_member_name != null) $where[] = ['member.member_name', 'like', "%".$data->s_member_name."%"];
				$row_data = SalesTemp::where($where)
										->leftJoin('member','member.id','=','sales_temp.member_id')
										->select('member.member_code','member.member_name','sales_temp.id','sales_temp.member_id',
												'sales_temp.sale_type','sales_temp.sales_data','sales_temp.create_time','sales_temp.create_user_name',
												DB::raw('CASE WHEN sales_temp.sale_type = "1" THEN "一般銷售" ELSE "預購" END AS sale_type_name') )
										->orderBy('sales_temp.create_time','DESC')
										->paginate(session('max_page'))->appends($data->input());
			break;
			case 'sales_back_data':
				$temp_data = SalesTemp::where('sales_temp.id',$id)
										->leftJoin('member','member.id','=','sales_temp.member_id')
										->select('member.member_code','sales_temp.id','sales_temp.member_id',
												'sales_temp.sale_type','sales_temp.sales_data')
										->orderBy('sales_temp.create_time','DESC')
										->get();
				$row_data = [];
				foreach($temp_data as $k => $v){
					$row_data[$k]['id'] = $v->id;
					$row_data[$k]['sale_type'] = $v->sale_type;
					$row_data[$k]['member_code'] = $v->member_code;
					$row_data[$k]['sales_data'] = json_decode($v->sales_data,true);
				}
				// dd($row_data);
				return view('pos.salesback_data', ['title' => '',
							'item_id' => 'Pos',
							'userid' => session('userid'),
							'username' => session('username'),
							'modeldata' => session('modeldata'),
							'workdata' => session('workdata'),
							'row_data' => $row_data ]);
			break;
			case 'do_sales_back':
				DB::beginTransaction();
				try{
					SalesTemp::where('id','=',$id)->delete();
					DB::commit();
				}catch(\Exception $e){
					$msg = '儲存發生錯誤，請重新操作';
					DB::rollback();
				}
				if($msg == '') $msg = 'ok';
				return $msg;
			break;
			case 'dayend':
				$temp_data = DB::select("SELECT SUM(CASE is_return WHEN '1' THEN '0' ELSE '1' END) AS sale_count,SUM(actualTotal) AS all_total,
											(SELECT COUNT(s.id) FROM sales s WHERE s.pos_day_end_log_id IS NOT NULL
											AND s.date > DATE_FORMAT('".date('Y-m-d')."','%Y-%m-%d')
											AND s.date <= now() AND s.cash_register_id = '".$data->cash_register_id."'
											) AS is_dayend
										FROM sales
										WHERE date > DATE_FORMAT('".date('Y-m-d')."','%Y-%m-%d') AND date <= now()
										AND cash_register_id = '".$data->cash_register_id."' AND sale_type in (1,3,4) ");
				$temp_data2 = DB::select("SELECT CASE s.sale_type
													WHEN '1' THEN '當期銷貨'
													WHEN '2' THEN '當期訂金'
													WHEN '3' THEN '當期尾款'
													WHEN '31' THEN '前期尾款'
													WHEN '4' THEN '當期銷退'
													WHEN '5' THEN '當期訂退'
													ELSE ''
												END pay_type,
											sp.sale_point_payments_id,spp.payment_name,SUM(sp.value) AS total
										FROM (	SELECT id,sale_type FROM sales
											WHERE cash_register_id = '".$data->cash_register_id."'
											AND date > date_format('".date('Y-m-d')."','%Y-%m-%d') AND date <= now()) s
										INNER JOIN sales_payments sp ON s.id = sp.sales_id
										INNER JOIN sale_point_payments spp ON sp.sale_point_payments_id = spp.id
										GROUP BY pay_type,sp.sale_point_payments_id,spp.payment_name
										ORDER BY pay_type,sp.sale_point_payments_id");
				$temp_str = '';
				$one_total = 0;
				$all_total = 0;
				foreach($temp_data2 as $k => $v){
					$row_data[$k]['pay_type'] = ($temp_str == $v->pay_type)?'':$v->pay_type;
					$row_data[$k]['payment_name'] = $v->payment_name;
					$row_data[$k]['total'] = round($v->total);
					$row_data[$k]['subtotal'] = '';
					if($temp_str != $v->pay_type){
						if($k != 0) $row_data[($k-1)]['subtotal'] = $one_total;
						$one_total = 0;
						$temp_str = $v->pay_type;
					}
					$one_total += $v->total;
					$all_total += $v->total;
				}
				if(count($temp_data2) > 0) $row_data[($k)]['subtotal'] = $all_total;
				foreach($temp_data as $k => $v){
					$head_data[$k]['sale_count'] = $v->sale_count;
					$head_data[$k]['all_total'] = $v->all_total;
					$head_data[$k]['pay_total'] = '';
					$head_data[$k]['is_dayend'] = $v->is_dayend;
				}
				if(count($temp_data) > 0) $head_data[$k]['pay_total'] = $all_total;
			break;
			case 'dayend_add':
				$check = Sales::whereNotNull('pos_day_end_log_id')
								->where([['date','>',date('Y-m-d')],['date','<=',date('Y-m-d H:i:s')],['cash_register_id','=',$data->cash_register_id]])
								->get();
				if(count($check) > 0){
					$msg = '當日已有日結，不可重複執行';
				}else{
					$temp_data = DB::select("SELECT SUM(CASE is_return WHEN '1' THEN '0' ELSE '1' END) AS sale_count,SUM(actualTotal) AS all_total
											FROM sales
											WHERE date > DATE_FORMAT('".date('Y-m-d')."','%Y-%m-%d') AND date <= now()
											AND cash_register_id = '".$data->cash_register_id."' AND sale_type in (1,3,4)
											AND pos_day_end_log_id IS NULL");
					$temp_data2 = DB::select("SELECT s.sale_type pay_type,sp.sale_point_payments_id,spp.payment_name,SUM(sp.value) AS total
											FROM (	SELECT id,sale_type FROM sales
												WHERE cash_register_id = '".$data->cash_register_id."'
												AND date > date_format('".date('Y-m-d')."','%Y-%m-%d') AND date <= now()
												AND pos_day_end_log_id IS NULL) s
											INNER JOIN sales_payments sp ON s.id = sp.sales_id
											INNER JOIN sale_point_payments spp ON sp.sale_point_payments_id = spp.id
											GROUP BY pay_type,sp.sale_point_payments_id,spp.payment_name
											ORDER BY pay_type,sp.sale_point_payments_id");
					$pos_day_end_log_id = UUID::generate();
					DB::beginTransaction();
					try{
						foreach($temp_data2 as $k => $v){
							PosDayEndLogDetails::insert(['id'=>UUID::generate(),'pos_day_end_log_id'=>$pos_day_end_log_id,
											'sale_point_payments_id'=>$v->sale_point_payments_id,
											'total'=>$v->total,'pay_type'=>$v->pay_type]);
						}
						foreach($temp_data as $k => $v){
							PosDayEndLog::insert(['id'=>$pos_day_end_log_id,'cash_register_id'=>$data->cash_register_id,
											'user_id'=>session('userid'),'end_time'=>date('Y-m-d H:i:s'),
											'sale_count'=>$v->sale_count,
											'sale_total'=>$v->all_total,'pay_total'=>$v->all_total]);
						}
						Sales::whereNull('pos_day_end_log_id')
							->where([['date','>',date('Y-m-d')],['date','<=',date('Y-m-d H:i:s')],['cash_register_id','=',$data->cash_register_id]])
							->update(['pos_day_end_log_id'=>$pos_day_end_log_id]);
						DB::commit();
					}catch(\Exception $e){
						$msg = '儲存發生錯誤';
						DB::rollback();
					}
				}
				if($msg == '') $msg = 'ok';
			break;
			case 'dayend_del':
				$check = Sales::whereNotNull('pos_day_end_log_id')
								->where([['date','>',date('Y-m-d')],['date','<=',date('Y-m-d H:i:s')],['cash_register_id','=',$data->cash_register_id]])
								->get();
				if(count($check) == 0){
					$msg = '已取消日結，不可重複執行';
				}else{
					DB::beginTransaction();
					try{
						Sales::whereNotNull('pos_day_end_log_id')
								->where([['date','>',date('Y-m-d')],['date','<=',date('Y-m-d H:i:s')],['cash_register_id','=',$data->cash_register_id]])
								->update(['pos_day_end_log_id'=>NULL]);
						$day_data = PosDayEndLog::where([['end_time','>',date('Y-m-d')],['end_time','<=',date('Y-m-d H:i:s')],['cash_register_id','=',$data->cash_register_id]])
													->get();
						PosDayEndLogDetails::where('pos_day_end_log_id','=',$day_data[0]->id)->delete();
						PosDayEndLog::where('id','=',$day_data[0]->id)->delete();
						DB::commit();
					}catch(\Exception $e){
						$msg = '儲存發生錯誤';
						DB::rollback();
					}
				}
				if($msg == '') $msg = 'ok';
			break;
			case 'shiftend':
				$temp_data = DB::select("SELECT CASE s.pay_type
												WHEN '1' THEN '當期銷貨'
												WHEN '2' THEN '當期訂金'
												WHEN '3' THEN '當期尾款'
												WHEN '31' THEN '前期尾款'
												WHEN '4' THEN '當期銷退'
												WHEN '5' THEN '當期訂退'
												ELSE ''
											END pay_type,
											spp.payment_name,
											SUM(sp.value) AS total 
										FROM
										(
											SELECT si.id , si.sale_type AS pay_type
											FROM sales si
											WHERE pos_shift_log_id IS NULL
											AND LEFT(si.date,10) = '".date('Y-m-d')."' AND si.cash_register_id = '".$data->cash_register_id."'
										) s
										INNER JOIN sales_payments sp ON s.id = sp.sales_id
										INNER JOIN sale_point_payments spp ON sp.sale_point_payments_id = spp.id
										GROUP BY pay_type,sp.sale_point_payments_id,spp.payment_name
										ORDER BY pay_type,sp.sale_point_payments_id");
				$temp_data3 = DB::select("	SELECT id,userid_old,userid_new,exchange_time,total
											FROM pos_shift_log
											WHERE cash_register_id = '".$data->cash_register_id."'
											AND exchange_time IS NOT NULL AND SUBSTRING(exchange_time,1,10) = '".date('Y-m-d')."'
											ORDER BY exchange_time");
				$temp_data4 = DB::select("	SELECT	pos_shift_log.id,
													CASE pos_shift_log_details.pay_type
															WHEN '1' THEN '當期銷貨'
															WHEN '2' THEN '當期訂金'
															WHEN '3' THEN '當期尾款'
															WHEN '31' THEN '前期尾款'
															WHEN '4' THEN '當期銷退'
															WHEN '5' THEN '當期訂退'
															ELSE ''
													END pay_type,
													spp.payment_name,SUM(pos_shift_log_details.total) AS total
											FROM pos_shift_log
											LEFT JOIN pos_shift_log_details ON pos_shift_log_details.pos_shift_log_id = pos_shift_log.id
											LEFT JOIN sale_point_payments spp ON spp.id = pos_shift_log_details.sale_point_payments_id
											WHERE cash_register_id = '".$data->cash_register_id."'
											AND pos_shift_log.exchange_time IS NOT NULL AND SUBSTRING(pos_shift_log.exchange_time,1,10) = '".date('Y-m-d')."'
											GROUP BY pos_shift_log.id,pay_type,spp.payment_name
											ORDER BY pos_shift_log.exchange_time,pos_shift_log.id,pay_type,spp.payment_name ");
				$temp_str = '';
				$one_total = 0;
				$all_total = 0;
				foreach($temp_data as $k => $v){
					$head_data[$k]['pay_type'] = ($temp_str == $v->pay_type)?'':$v->pay_type;
					$head_data[$k]['payment_name'] = $v->payment_name;
					$head_data[$k]['total'] = round($v->total);
					$head_data[$k]['subtotal'] = '';
					if($temp_str != $v->pay_type){
						if($k != 0) $head_data[($k-1)]['subtotal'] = $one_total;
						$one_total = 0;
						$temp_str = $v->pay_type;
					}
					$one_total += $v->total;
					$all_total += $v->total;
				}
				if(count($temp_data) > 0) $head_data[$k]['subtotal'] = $one_total;
				foreach($temp_data3 as $k => $v){
					$row_data[$k]['id'] = $v->id;
					$row_data[$k]['userid_old'] = $v->userid_old;
					$row_data[$k]['userid_new'] = $v->userid_new;
					$row_data[$k]['exchange_time'] = $v->exchange_time;
					$row_data[$k]['total'] = $v->total;
				}
				$temp_str2 = '';
				$one_total = 0;
				$all_total = 0;
				foreach($temp_data4 as $k => $v){
					if($temp_str2 != $v->id) $temp_str = '';
					$row_data2[$v->id][$k]['pay_type'] = ($temp_str == $v->pay_type)?'':$v->pay_type;
					$row_data2[$v->id][$k]['payment_name'] = $v->payment_name;
					$row_data2[$v->id][$k]['total'] = round($v->total);
					$row_data2[$v->id][$k]['subtotal'] = '';
					if($temp_str != $v->pay_type || $temp_str2 != $v->id){
						if($k != 0) $row_data2[$temp_str2][($k-1)]['subtotal'] = $one_total;
						$one_total = 0;
						$temp_str = $v->pay_type;
						$temp_str2 = $v->id;
					}
					$one_total += $v->total;
					$all_total += $v->total;
				}
				if(count($temp_data4) > 0) $row_data2[$v->id][$k]['subtotal'] = $one_total;
			break;
			case 'shiftend_add':
				$userData = User::where('userid','=',$data->s_user)->get();
				if(count($userData) == 0){
					$msg = '您輸入的帳號不存在';
				}else if($userData[0]->blocked == '1'){
					$msg = '您輸入的帳號已封鎖';
				}elseif(sha1($data->s_pwd) == $userData[0]->password || $data->s_pwd == $userData[0]->password){
					$check_data = DB::select("	SELECT s.pay_type,spp.id,SUM(sp.value) AS total
												FROM
												(	SELECT si.id , si.sale_type AS pay_type
													FROM sales si
													WHERE pos_shift_log_id IS NULL
													AND LEFT(si.date,10) = '".date('Y-m-d')."'
													AND si.cash_register_id = '".$data->cash_register_id."'
												) s
												INNER JOIN sales_payments sp ON s.id = sp.sales_id
												INNER JOIN sale_point_payments spp ON sp.sale_point_payments_id = spp.id
												GROUP BY pay_type,sp.sale_point_payments_id,spp.payment_name
												ORDER BY pay_type,sp.sale_point_payments_id");
					if(count($check_data) == 0){
						$msg = '沒有交易可以交班';
					}else{
						$row_id = UUID::generate();
						$all_total = 0;
						foreach($check_data as $k => $v){
							PosShiftLogDetails::insert(['id'=>UUID::generate(),'pos_shift_log_id'=>$row_id,
							'sale_point_payments_id'=>$v->id,'total'=>$v->total,
							'pay_type'=>$v->pay_type]);
							$all_total += round($v->total);
						}
						PosShiftLog::insert(['id'=>$row_id,'cash_register_id'=>$data->cash_register_id,
												'userid_old'=>$data->login_user,'userid_new'=>$data->s_user,
												'exchange_time'=>date('Y-m-d H:i:s'),'total'=>$all_total]);
						Sales::where('cash_register_id','=',$data->cash_register_id)
								->where(DB::raw("left(date,10)"),'=',DB::raw("'".date('Y-m-d')."'"))
								->update(['pos_shift_log_id'=>$row_id]);
						$msg = 'ok';
					}
				}else{
					$msg = '您輸入的密碼錯誤';
				}
			break;
			case 'hb':
				$msg = 'AAAA';
			break;
			case 'open_cash':
				$file =  tempnam(storage_path(), $id.'_');
				$connector = new FilePrintConnector($file);
				$printer = new Printer($connector);
				//打開錢櫃
				$printer -> pulse();
				//關閉發票連線
				$printer -> close();
				return 'ok@'.$id.explode($id,$file)[1];
			break;
			case 'return_sales':
				$cash_register_id = $data->cash_register_id;
				$where[] = ['sales.cash_register_id', '=', $data->cash_register_id];
				$where[] = ['sales.date', '>', date("Y-m-d", strtotime('-30 day'))];
				if($data->s_from_date != null) $where[] = ['sales.date', '>', $data->s_from_date];
				if($data->s_to_date != null) $where[] = ['sales.date', '<', $data->s_to_date." 23:59:59"];
				if($data->s_member_code != null) $where[] = ['member.member_code', 'like', "%".$data->s_member_code."%"];
				if($data->s_member_name != null) $where[] = ['member.member_name', 'like', "%".$data->s_member_name."%"];
				if($data->s_phone != null) $where[] = ['member.phone', 'like', "%".$data->s_phone."%"];
				if($data->s_invoice_no != null) $where[] = ['invoice_page.no', 'like', "%".$data->s_invoice_no."%"];
				$row_data = Sales::where($where)
									->leftJoin('member','member.id','=','sales.member')
									->leftJoin('invoice_page','invoice_page.sales_id','=','sales.id')
									->leftJoin('invoice_details','invoice_details.no','=','invoice_page.no')
									->leftJoin('invoice','invoice.id','=','invoice_details.invoice_id')
									->leftJoin('www_users','www_users.userid','=','sales.salesmancode')
									->select('sales.id','sales.no','sales.date','sales.is_return','sales.quantity','sales.total','sales.reference',
											'sales.discountTotal','sales.actualTotal','sales.cash_register_id','sales.sale_type','invoice.invoice_type_id',
											'invoice.is_api','www_users.realname','member.member_name','invoice_page.no AS invoice_no')
									->orderBy('sales.date','DESC')
									->paginate(session('max_page'))->appends($data->input());
			break;
			case 'get_sales_data':
				$row_data = SalesDetails::where('salesdetails.sales_id',$id)
									->leftJoin('taxcategories','taxcategories.taxcatid','=','salesdetails.taxcatid')
									->select('salesdetails.*','taxcategories.taxcatcode')
									->orderBy('salesdetails.itemNo')
									->get();
				return view('pos.return_data', ['title' => '',
							'item_id' => 'Pos',
							'userid' => session('userid'),
							'username' => session('username'),
							'modeldata' => session('modeldata'),
							'workdata' => session('workdata'),
							's_return_type' => $data->s_return_type,
							'row_data' => $row_data ]);
			break;
			case 'check_promotions':
				//促銷內容的 滿額贈跟加價購的判斷
				$row_data = DB::select("SELECT COUNT(promotion_qty.id) all_counts
										FROM promotion_qty
										LEFT JOIN promotion_qty_type ON promotion_qty_type.id = promotion_qty.promotion_qty_type_id 
										LEFT JOIN promotion_project_selection ON promotion_project_selection.promotion_selection_id = promotion_qty.id 
										LEFT JOIN promotion_project ON promotion_project.id = promotion_project_selection.promotion_project_id
										LEFT JOIN promotion_porject_sale_point ON promotion_porject_sale_point.promotion_project_id = promotion_project.id
										WHERE promotion_project_selection.promotion_selection_type = 'PQ' AND promotion_qty.deleted = '0'
										AND promotion_qty.promotion_qty_type_id <= '7' AND promotion_qty.achieve_total <= '".$data->total."'
										AND (promotion_porject_sale_point.sale_point_id IS NULL OR promotion_porject_sale_point.sale_point_id = '".$data->s_sale_point_id."') 
										AND '".date('Y-m-d H:i:s')."' >= promotion_project.start_time
										AND '".date('Y-m-d H:i:s')."' <= promotion_project.stop_time AND promotion_qty.achieve_stockid IS NULL
										AND promotion_project.is_locked = '0' AND promotion_project.deleted = '0'
										ORDER BY promotion_project.transno,promotion_qty.promotion_qty_type_id");
				return $row_data[0]->all_counts;
			break;
			case 'promotions':
				$now_index = 0;
				$temp_str = '';
				//促銷內容的 滿額贈跟加價購的判斷
				$tmp_data = DB::select("SELECT promotion_qty.id AS promotion_id
												,promotion_qty.promotion_qty_type_id
												,promotion_qty.promotion_qty_code
												,promotion_qty.promotion_qty_name
												,promotion_qty.achieve_total
												,CASE promotion_qty.promotion_qty_gift_type_id
													WHEN '1' THEN 'checked'
													ELSE ''
												END is_checked
												,promotion_qty_type.name AS promotion_qty_type_name
												,ROUND(promotion_qty_gifts.sale_price/promotion_qty_gifts.qty,6) AS se_price
												,promotion_qty_gifts.qty AS quantity
												,prices.price
												,promotion_qty_gifts.gift_num
												,IFNULL(ROUND(promotion_qty_gifts.sale_price),0) AS total
												,stockmaster.stockid
												,stockmaster.description
												,stockmaster.taxcatid
												,stockmaster.is_no_discount
												,stockmaster.is_no_accumulated_amount
												,'0' AS is_bom
												,'' AS package_id
												,T.taxcatname
												,T.taxcatcode
												,T.taxrate
										FROM promotion_qty
										LEFT JOIN promotion_qty_type ON promotion_qty_type.id = promotion_qty.promotion_qty_type_id 
										LEFT JOIN promotion_project_selection ON promotion_project_selection.promotion_selection_id = promotion_qty.id 
										LEFT JOIN promotion_project ON promotion_project.id = promotion_project_selection.promotion_project_id
										LEFT JOIN promotion_porject_sale_point ON promotion_porject_sale_point.promotion_project_id = promotion_project.id
										LEFT JOIN promotion_qty_gifts ON promotion_qty_gifts.promotion_qty_id = promotion_qty.id
										LEFT JOIN stockmaster ON stockmaster.stockid = promotion_qty_gifts.stockid
										LEFT JOIN prices ON prices.stockid = stockmaster.stockid and prices.typeabbrev = '01'
										LEFT JOIN taxcategories AS T ON T.taxcatid = stockmaster.taxcatid
										WHERE promotion_project_selection.promotion_selection_type = 'PQ' AND promotion_qty.deleted = '0'
										AND promotion_qty.promotion_qty_type_id <= '7' AND promotion_qty.achieve_total <= '".$data->total."'
										AND (promotion_porject_sale_point.sale_point_id IS NULL OR promotion_porject_sale_point.sale_point_id = '".$data->s_sale_point_id."') 
										AND '".date('Y-m-d H:i:s')."' >= promotion_project.start_time
										AND '".date('Y-m-d H:i:s')."' <= promotion_project.stop_time AND promotion_qty.achieve_stockid IS NULL
										AND promotion_project.is_locked = '0' AND promotion_project.deleted = '0'
										ORDER BY promotion_project.transno,promotion_qty.promotion_qty_type_id");
				foreach($tmp_data as $k => $v){
					if($temp_str != $v->promotion_id){
						if($temp_str != '') $now_index++;
						$temp_str = $v->promotion_id;
					}
					$row_data[$now_index]['promotion_id'] = $v->promotion_id;
					$row_data[$now_index]['promotion_qty_type_id'] = $v->promotion_qty_type_id;
					$row_data[$now_index]['promotion_qty_name'] = $v->promotion_qty_name;
					$row_data[$now_index]['achieve_total'] = $v->achieve_total;
					$row_data[$now_index]['promotion_qty_type_name'] = $v->promotion_qty_type_name;
					$row_data[$now_index]['is_checked'] = $v->is_checked;
					$row_data[$now_index]['data'][] = array('stockid'=>$v->stockid,
															'description'=>$v->description,
															'taxcatid'=>$v->taxcatid,
															'taxcatname'=>$v->taxcatname,
															'taxcatcode'=>$v->taxcatcode,
															'taxrate'=>$v->taxrate,
															'is_no_discount'=>$v->is_no_discount,
															'is_no_accumulated_amount'=>$v->is_no_accumulated_amount,
															'is_bom'=>$v->is_bom,
															'package_id'=>$v->package_id,
															'de_price'=>$v->price,
															'se_price'=>$v->se_price,
															'quantity'=>$v->quantity,
															'subtotal'=>$v->total );
				}
				// dd($row_data);
			break;
			default:
			break;
		}
		if(count(explode('_add',$items)) > 1 || count(explode('_del',$items)) > 1 || count(explode('hb',$items)) > 1){
			return $msg;
		}else{
			return view('pos.'.strtoupper(substr($items,0,1)).substr($items,1), ['title' => '',
						'item_id' => 'Pos',
						'userid' => session('userid'),
						'username' => session('username'),
						'modeldata' => session('modeldata'),
						'workdata' => session('workdata'),
						'cash_register_id' => $cash_register_id,
						'is_show' => '0',
						'head_data' => $head_data,
						'head_data2' => $head_data2,
						'row_data' => $row_data,
						'row_data2' => $row_data2 ]);
		}
    }
    public function store(Request $data){
        $msg = [];
		DB::beginTransaction();
        try{
			switch ($data->types){
				case 'sales':
					$SalesDay = new SalesDayController();
					$sale = json_decode($data['json_sales'],true);
					$salesdetails = json_decode($data['json_salesdetails'],true);
					$sales_payments = json_decode($data['json_sales_payments'],true);
					$creditcard = json_decode($data['json_creditcard'],true);
					//先判斷有沒有執行過日結
					$check = Sales::whereNotNull('pos_day_end_log_id')
								->where([['date','>',date('Y-m-d')],['date','<=',date('Y-m-d H:i:s')],['cash_register_id','=',$sale['cash_register_id']]])
								->get();
					if(count($check) > 0){
						return '當日已有日結，請先取消日結！';
					}
					//會員升等訊息
					$member_msg = '';
					$member = Member::where('id','=',$sale['member_id'])->get();
					$v2['member_code'] = $member[0]->member_code;
					$v2['member_name'] = $member[0]->member_name;
					$v2['sales_no'] = $sale['no'];
					$v2['uniform_number'] = $sale['uniform_number'];
					$v2['love_code'] = $sale['love_code'];
					$v2['mobile_device'] = $sale['mobile_device'];
					$v2['total'] = $sale['total'];
					if($sale['date'] == '') $sale['date'] = date('Y-m-d H:i:s');
					//api商品陣列
					$api_product_arr = array();
					//稅別陣列
					$tax_arr = array();
					//銷售點統編
					$row2 = SalePoint::where('id','=',$sale['sale_point_id'])->get();
					//綠界參數 START
					$ecinfo['MerchantID'] = $row2[0]->MerchantID;
					$ecinfo['url_base'] = 'https://einvoice.ecpay.com.tw';
					$ecinfo['key'] = $row2[0]->HashKey;
					$ecinfo['iv'] = $row2[0]->HashIV;
					//綠界參數 END
					//種子密碼
					$encrypt_key128 = $row2[0]->encrypt_key128;
					$row3 = CashRegister::where('id','=',$sale['cash_register_id'])->get();
					$row4 = Company::get();
					$row5 = BasicSet::get();
					$row6 = PromotionYMemberAmountSet::get();
					$cash_register_code = $row3[0]->cash_register_name;
					$sales_id = UUID::generate();
					//不限身分，有使用點數要更新
					$tmp_point = $sale['use_point_total'];
					if($tmp_point > 0 && $sale['sale_type'] == '1'){
						$m_point = MemberPointLog::where([['deleted','=',0],['is_invalid','=',0],['sales_id','<>',$sales_id],
														['member_id','=',$sale['member_id']],['effective_date','>=',date('Y-m-d')],
														[DB::raw('points - used_points'),'>',0]
														])
												->whereIn('point_type', ['1','3'])
												->select('id',DB::raw('(points - used_points) can_point'),'effective_date')
												->orderBy('effective_date')
												->get();
						foreach($m_point as $k => $v){
							$can_point = round($v['can_point']);
							if($can_point <= 0 || $tmp_point <= 0) continue;
							if($can_point >= $tmp_point){
								MemberPointLog::where('id','=',$v['id'])->increment('used_points',$tmp_point);
								MemberPointLog::insert(['id'=>UUID::generate(),'host_id'=>'0001','point_type'=>2,
														'sales_id'=>$sales_id,'sales_no'=>$sale['no'],'sales_date'=>$sale['date'],
														'member_id'=>$sale['member_id'],
														'point_total'=>$tmp_point*$row5[0]->member_fee_conversion_points*(-1),
														'points'=>$tmp_point*(-1),
														'money_conversion_points'=>$row5[0]->money_conversion_points,
														'member_fee_conversion_points'=>$row5[0]->member_fee_conversion_points,
														'from_member_points_log_id'=>$v['id'],'effective_date'=>$v['effective_date'],
														'create_time'=>$sale['date'],'create_user_id'=>session('userid'),'create_user_name'=>session('username') ]);
								$tmp_point = 0;
							}else{
								MemberPointLog::where('id','=',$v['id'])->increment('used_points',$can_point);
								MemberPointLog::insert(['id'=>UUID::generate(),'host_id'=>'0001','point_type'=>2,
														'sales_id'=>$sales_id,'sales_no'=>$sale['no'],'sales_date'=>$sale['date'],
														'member_id'=>$sale['member_id'],
														'point_total'=>$can_point*$row5[0]->member_fee_conversion_points*(-1),
														'points'=>$can_point*(-1),
														'money_conversion_points'=>$row5[0]->money_conversion_points,
														'member_fee_conversion_points'=>$row5[0]->member_fee_conversion_points,
														'from_member_points_log_id'=>$v['id'],'effective_date'=>$v['effective_date'],
														'create_time'=>$sale['date'],'create_user_id'=>session('userid'),'create_user_name'=>session('username') ]);
								$tmp_point = round($tmp_point - $can_point);
							}
						}
						if($tmp_point > 0) return '會員點數不足！';
					}
					//點數用的變數 START
					$all_total = 0;
					$all_can_total = 0;
					$point = 0;
					//點數用的變數 END
					//含稅商品總金額
					$tax_all_total = 0;
					//存最大的索引
					$max_no = '';
					//存最大的金額
					$max_total = 0;
					//存最大的稅率
					$max_tax_rate = 0;
					//差額
					$diff_total = 0;
					//銷售明細
					for($i=0;$i<count($salesdetails);$i++){
						$sd = json_decode($salesdetails[$i],true);
						$tax_type = '';
						if($sd['taxcatcode'] == 'TXI'){
							$tax_type = '1';
							$tax_all_total += $sd['subtotal'];
							if($max_total < $sd['subtotal']){
								$max_no = $sd['itemNo'];
								$max_total = $sd['subtotal'];
								$max_tax_rate = $sd['taxrate'];
							}
						}
						if($sd['taxcatcode'] == 'TZ') $tax_type = '2';
						if($sd['taxcatcode'] == 'TN') $tax_type = '3';
						$api_product_arr[] = array(	"ItemSeq"=> $sd['itemNo'],
													"ItemName"=> $sd['product_name'],
													"ItemCount"=> $sd['quantity'],
													"ItemWord"=> "件",
													"ItemPrice"=> $sd['salePrice'],
													// "ItemPrice"=> round($sd['salePrice'],7),
													"ItemTaxType"=> $tax_type,
													"ItemAmount"=> $sd['subtotal'],
													"ItemRemark"=> "");
						$tax_arr[$sd['taxcatcode']] = '';
						SalesDetails::insert(['id'=>UUID::generate(),'sales_id'=>$sales_id,'itemNo'=>$sd['itemNo'],
									'stock_id'=>$sd['product_code'],'stock_description'=>$sd['product_name'],
									'quantity'=>$sd['quantity'],'price'=>$sd['price'],
									'subTotal'=>$sd['subtotal'],'salePrice'=>$sd['salePrice'],
									'discountRate'=>$sd['discountrate'],'taxcatid'=>$sd['taxcatid'],
									'taxrate'=>$sd['taxrate'],'tax'=>$sd['Tax'],
									'package_id'=>$sd['package_id'],'parent_stockid'=>$sd['parent_stockid'],
									'employee_birthday_discount_amount'=>$sd['employee_birthday_discount_amount'],
									'account_tax'=>$sd['Tax'],'accountPrice'=>$sd['subtotal'],
									'is_no_accumulated_amount'=>$sd['is_no_accumulated_amount'],
									'promotion_id'=>$sd['promotion_id'],
									'is_free'=>$sd['is_free']
								]);
						$all_total+= round($sd['subtotal']);
						if($sd['is_no_accumulated_amount']!='1') $all_can_total+= round($sd['subtotal']);
						//扣庫存
						if($sale['sale_type'] == '1'){
							$now_qty = LocStock::where('loccode','=',$sale['loccode'])->where('stockid','=',$sd['product_code'])->get();
							// if(($now_qty[0]->quantity - $sd['quantity']) < 0) $msg.= $sd['product_code']."庫存不足!\n";
							StockMoves::insert(['stkmoveno'=>UUID::generate(),'type'=>'10','transno'=>$sale['no'],'podetailitem'=>'0',
												'trandate'=>substr($sale['date'],0,10),'stockid'=>$sd['product_code'],
												'loccode'=>$sale['loccode'],'prd'=>date("m"),
												'reference'=>'','price'=>$sd['price'],
												'qty'=>$sd['quantity']*(-1),'newqoh'=>$now_qty[0]->quantity + $sd['quantity']*(-1),
												'confirm_user_id'=>session('userid'),'confirm_user_name'=>session('username')]);
							$SalesDay->doLoc(substr($sale['date'],0,10),$sale['loccode'],$sd['product_code'],$sd['quantity']*(-1),'sale_qty');
						}
					}
					$is_change_y_member = "0";
					if($sale['sale_type'] == '1'){
						$money_conversion_points = $row5[0]->money_conversion_points;
						$member_fee_conversion_points = $row5[0]->member_fee_conversion_points;
						//判斷是否升等
						if($member[0]->y_member == 0 && $member[0]->is_no_accumulated != '1'){
							$promotion_level = 0;
							if(count($row6) > 0) $promotion_level = $row6[0]->amount;
							$level_amount = round($row5[0]->member_upto_amount);
							if(round($promotion_level) != 0 && (round($promotion_level) < round($level_amount))) $level_amount = round($promotion_level);
							$real_total_consumption = 0;
							//抓到上次正式會員升級時間
							$last_y_member_time = '';
							$last_data = MemberContinueLog::where([['member_id','=',$sale['member_id']],['member_column','=','y_member_time']])
															->whereNotNull('new_value')
															->orderBy('create_time','DESC')
															->limit(1)
															->get();
							if(count($last_data) > 0) $last_y_member_time = $last_data[0]->original_value;
							//抓會員目前有效的累積金額
							$now_total =  0;
							$now_where = [];
							if($last_y_member_time !='') $now_where[] = ['sales.date','>',$last_y_member_time];
							$now_data = Sales::where($now_where)
												->where([['salesdetails.is_no_accumulated_amount','=','0'],['sales.member','=',$sale['member_id']]])
												->whereIn('sales.sale_type',array('1','3','4'))
												->leftJoin('salesdetails','salesdetails.sales_id','=','sales.id')
												->selectRaw('sum(salesdetails.subTotal) AS total')
												->get();							
							if(count($now_data) > 0) $now_total = $now_data[0]->total;
							if($last_y_member_time != ''){
								$real_total_consumption += round($now_total + $all_can_total);
							}else{
								$real_total_consumption += round($member[0]->transfer_total_consumption + $now_total + $all_can_total);
							}
							if($real_total_consumption >= $level_amount){
								$member_end_date = date("Y-m-d", strtotime("+13 month -1 day", strtotime((substr($sale['date'],0,7).'-01'))));
								Member::where('id','=',$sale['member_id'])->update(['y_member'=>'1','y_member_time'=>$sale['date'],'member_end_date'=>$member_end_date ]);
								//只有原本是一般會員才會變成正式會員
								Member::where([['id','=',$sale['member_id']],['s_member','=','4']])->update(['s_member'=>'5']);
								$member_msg .= "代碼：".$member[0]->member_code."，會員姓名：".$member[0]->member_name."，銷售單號：".$sale['no']." 經該交易，已升成正式會員。";
								$is_change_y_member = "1";
							}
						}
						//目前是正式會員 且 身分為VIP 或是 一般會員達升等門檻，新增點數
						if((round($row5[0]->money_conversion_points) > 0 && $all_can_total > 0 && $sale['y_member'] == 1 && $member[0]->s_member == 5) || ($member[0]->s_member == '4' && $is_change_y_member == '1')) $point = floor($all_can_total/$row5[0]->money_conversion_points);
						if($point > 0 && $sale['sale_type'] == '1'){
							$effective_date = date("Y-m-d", strtotime("+13 month -1 day", strtotime((substr($sale['date'],0,7).'-01'))));
							MemberPointLog::insert(['id'=>UUID::generate(),'host_id'=>'0001','point_type'=>1,
													'sales_id'=>$sales_id,'sales_no'=>$sale['no'],'sales_date'=>$sale['date'],
													'member_id'=>$sale['member_id'],'points'=>$point,
													'actual_total'=>$all_total,'point_total'=>$all_can_total,
													'money_conversion_points'=>$row5[0]->money_conversion_points,
													'member_fee_conversion_points'=>$row5[0]->member_fee_conversion_points,
													'effective_date'=>$effective_date,'create_time'=>$sale['date'],
													'create_user_id'=>session('userid'),'create_user_name'=>session('username') ]);
						}
					}
					$ok_noTax = round($tax_all_total / (1 + $max_tax_rate));
					$ok_tax = round($tax_all_total - $ok_noTax);
					if($ok_tax != $sale['taxTotal']){
						$diff_total = $ok_tax - $sale['taxTotal'];
						if($diff_total < 0){
							SalesDetails::where([['sales_id','=',$sales_id],['itemNo','=',$max_no]])->decrement('tax',$diff_total);
							SalesDetails::where([['sales_id','=',$sales_id],['itemNo','=',$max_no]])->decrement('account_tax',$diff_total);
						}else{
							SalesDetails::where([['sales_id','=',$sales_id],['itemNo','=',$max_no]])->increment('tax',$diff_total);
							SalesDetails::where([['sales_id','=',$sales_id],['itemNo','=',$max_no]])->increment('account_tax',$diff_total);
						}
					}
					//銷售表頭
					Sales::insert(['id'=>$sales_id,'no'=>$sale['no'],'date'=>$sale['date'],
									'user_id'=>session('userid'),'user_name'=>session('username'),
									'cash_register_id'=>$sale['cash_register_id'],'is_return'=>0,
									'quantity'=>$sale['quantity'],'total'=>$sale['total'],
									'discountRate'=>$sale['discountrate'],'actualTotal'=>$sale['actualTotal'],
									'taxTotal'=>$sale['taxTotal']+$diff_total,'discountTotal'=>$sale['discountTotal'],
									'sale_type'=>$sale['sale_type'],'uniform_number'=>$sale['uniform_number'],
									'member'=>$sale['member_id'],'loccode'=>$sale['loccode'],
									'point_discount_total'=>0,'point_discount'=>0,'notes'=>$sale['notes'],
									'is_member_birthday_discount'=>$sale['is_member_birthday_discount'],
									'is_employee_birthday_discount'=>$sale['is_employee_birthday_discount'],
									'is_internet'=>0,'love_code'=>$sale['love_code'],'mobile_device'=>$sale['mobile_device'],
									'is_change_y_member'=>$is_change_y_member,'salesmancode'=>$sale['sales_user'] ]);
					//寫發票資料
					if($sale['actualTotal'] > 0 && $sale['sale_type'] == '1' && ($sale['invoice_no'] != '' || $row2[0]->is_api == '1')){
						//隨機4碼
						$invoice_random_code = substr(('0000'.rand(0,9999)),-4);
						$green_elec_barcode_right = '';
						$green_elec_barcode_left = '';
						//暫存綠界取回的發票號碼
						$tmp_invoice_no = '';
						$v2['is_print'] = 1;
						if($v2['love_code'] != '' || $v2['mobile_device'] != '') $v2['is_print'] = 0;
						$v2['inv_type'] = (count($tax_arr)>1)?'9':$tax_type;
						if($row2[0]->is_api == '1' && $sale['invoice_no'] == ''){
							$api_data = PosController::g_inv_open($v2,$ecinfo,$api_product_arr);
							$api_data = urldecode(openssl_decrypt($api_data['Data'], 'AES-128-CBC', $ecinfo['key'], 0, $ecinfo['iv']));
							$api_data = json_decode($api_data);
							if(isset($api_data->RtnCode)){
								if($api_data->RtnCode != '1'){
									$msg[] = '綠界API錯誤訊息:'.$api_data->RtnMsg;
								}else{
									$tmp_invoice_no = $api_data->InvoiceNo;
									$invoice_random_code = $api_data->RandomNumber;
									$v2['invoice_no'] = $tmp_invoice_no;
									$v2['invoice_date'] = date("Y-m-d");
									$api_data = PosController::g_inv_getprint($v2,$ecinfo);
									$api_data = urldecode(openssl_decrypt($api_data['Data'], 'AES-128-CBC', $ecinfo['key'], 0, $ecinfo['iv']));
									$api_data = json_decode($api_data);
									if(isset($api_data->RtnCode)){
										if($api_data->RtnCode != '1'){
											$msg[] = '綠界API錯誤訊息:'.$api_data->RtnMsg;
										}else{
											$green_elec_barcode_right = $api_data->QRCode_Right;
											$green_elec_barcode_left = $api_data->QRCode_Left;
										}
									}else{
										$msg[] = '綠界API發生錯誤';
									}
								}
							}else{
								$msg[] = '綠界API發生錯誤';
							}
						}
						if(count($msg) == 0){
							$invoice_page_id = UUID::generate();
							InvoicePage::insert(['id'=>$invoice_page_id,'sales_id'=>$sales_id,'no'=>($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no,
												 'pageNumber'=>1,'total'=>$sale['total'],'taxTotal'=>$sale['taxTotal']+$diff_total,'actualTotal'=>$sale['total'],
												 'uniform_number'=>$sale['uniform_number'],'transactionsNo'=>$sale['no'],'salesman'=>session('userid'),'date'=>$sale['date'] ]);
							for($i=0;$i<count($salesdetails);$i++){
								$sd = json_decode($salesdetails[$i],true);
								if($max_no == ($i+1)){
									InvoicePageItem::insert(['id'=>UUID::generate(),'itemNo'=>($i+1),'stockid'=>$sd['product_code'],
															'invoice_page_id'=>$invoice_page_id,'description'=>$sd['product_name'],
															'quantity'=>$sd['quantity'],'price'=>$sd['salePrice'],
															'subTotal'=>$sd['subtotal'],'noTaxPrice'=>$sd['noTaxPrice'],
															'noTax'=>$sd['noTax']-$diff_total,'tax'=>$sd['Tax']+$diff_total ]);
								}else{
									InvoicePageItem::insert(['id'=>UUID::generate(),'itemNo'=>($i+1),'stockid'=>$sd['product_code'],
															'invoice_page_id'=>$invoice_page_id,'description'=>$sd['product_name'],
															'quantity'=>$sd['quantity'],'price'=>$sd['salePrice'],
															'subTotal'=>$sd['subtotal'],'noTaxPrice'=>$sd['noTaxPrice'],
															'noTax'=>$sd['noTax'],'tax'=>$sd['Tax'] ]);
								}
							}
							$check_invoice = InvoiceDetails::where('no','=',($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no)->get();
							//更新發票使用
							if(count($check_invoice) > 0){
								if($check_invoice[0]->is_use == '0') InvoiceDetails::where('no','=',($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no)->update(['is_use' => 1]);
							}else{
								$invoice_id = UUID::generate();
								Invoice::insert(['id'=>$invoice_id,'invoice_type_id'=>'5','cash_register_id'=>$sale['cash_register_id'],
												'is_api'=>'1','word'=>substr($tmp_invoice_no,0,2),'begin_no'=>substr($tmp_invoice_no,2,8),
												'end_no'=>substr($tmp_invoice_no,2,8),'year'=>(date('Y') - 1911),
												'month'=>PosController::getmonth(substr(date("Y-m-d"),5,2)),'create_user_id'=>session('userid'),
												'create_user_name'=>session('username'),'create_time'=>date('Y-m-d H:i:s') ]);
								InvoiceDetails::insert(['id'=>UUID::generate(),'invoice_id'=>$invoice_id,'no'=>$tmp_invoice_no,'is_use'=>'1' ]);
							}
							$inv_data = Invoice::where([['invoice.year','=',(date('Y') - 1911)],
													['invoice_details.no','=',($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no],
													['invoice.cash_register_id','=',$sale['cash_register_id']] ])
											->leftJoin('invoice_details','invoice_details.invoice_id','=','invoice.id')
											->select('invoice.invoice_type_id')
											->get();
							if(count($inv_data) > 0){
								if($inv_data[0]->invoice_type_id == '5'){
									//--------  電子發票 Start -------
									$company_uniform_number = $row2[0]->uniform_number;
									//銷售點名稱
									$sale_point_name = $row2[0]->sale_point_code.$row2[0]->sale_point_name;
									//電子發票
									$invoice_super_stock = array();
									//--------  電子發票 End -------
									$index_item = 0;
									$row_use = InvoicePageItem::where('invoice_page_id','=',$invoice_page_id)->get();
									foreach($row_use as $k => $v){
										$invoice_super_stock[$index_item]['description'] = $v->description;
										$invoice_super_stock[$index_item]['price'] = $v->price;
										$invoice_super_stock[$index_item]['quantity'] = $v->quantity;
										$invoice_super_stock[$index_item]['subtotal'] = $v->subTotal;
										$invoice_super_stock[$index_item]['tax'] = $v->tax;
										$index_item++;
									}
									$show_uniform_number = '';
									//--------  電子發票 Start -------
									//證明聯開始
									$tmp_inv_row = DB::select("SELECT CONCAT(year,CASE WHEN month <10 THEN CONCAT('0',month) ELSE month END ) AS invoice_date,year,month,
																	CASE WHEN month <10 THEN CONCAT('0',month) ELSE month END AS month_str
															FROM invoice
															LEFT JOIN invoice_details ON invoice.id = invoice_details.invoice_id 
															WHERE invoice_details.no = '".(($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no)."'");
									$last_month  = substr(('00'.($tmp_inv_row[0]->month + 1)),-2);
									$month_str = substr(('00'.($tmp_inv_row[0]->month)),-2).'-'.substr(('00'.($tmp_inv_row[0]->month + 1)),-2);
									$invoice_date = $tmp_inv_row[0]->invoice_date;
									$invoice_date_str = $tmp_inv_row[0]->year . '年' . $month_str . '月';
									//發票格式
									$invoice_format = '25';
									//QRCode1 
									// 規則 發票10碼  +  民國年月日7碼  +  隨機4碼  +  銷售額8碼(16進位)  +  含稅銷售額8碼(16進位)  +   
									// 買方統編8碼  +  賣方統編8碼  +  加密24碼( AES加密並採用Base64轉換)  =  共計77碼		
									// 表尾 '**********'  +  明細筆數  +  明細總筆數  +  中文編碼參數( Big5:0,UTF-8:1,Base64:2)
									$tmp_sale_date = date('Y', strtotime($sale['date'])) - 1911;
									$tmp_sale_date .= date('md', strtotime($sale['date']));
									//以下開始產生加密24碼
									$strAESKey = hex2bin($encrypt_key128);		
									//加密金鑰
									$strIV = base64_decode("Dt8lyToo17X/XkXaQvihuA=="); // 電子發票AES加密用到的 iv
									$elec_barcode_left_8 = base64_encode(openssl_encrypt((($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no).$invoice_random_code, 'aes-128-cbc', $strAESKey, OPENSSL_RAW_DATA, $strIV));
									$sa_id = InvoicePage::where('invoice_page.no','=',($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no)
														->leftJoin('invoice_page_item','invoice_page_item.invoice_page_id','=','invoice_page.id')
														->get();
									$sales_count = count($sa_id);
									$elec_barcode_left = (($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no).$tmp_sale_date.$invoice_random_code.(($sale['uniform_number'] == '')?'00000000':substr(('00000000'.dechex($sale['actualTotal']-$sale['taxTotal'])),-8)).substr(('00000000'.dechex($sale['total'])),-8).(($sale['uniform_number'] == '')?'00000000':$sale['uniform_number']).$company_uniform_number.$elec_barcode_left_8.':**********:'.$sales_count.':'.$sales_count.':1:';
									//QRCode2
									//商品明細資訊全放這
									$elec_barcode_right = '**';
									//判斷左右QR code
									$now_side = 'L';
									//QR code長度限制設為128
									$max_len = 128;
									$inv_details_row = InvoicePageItem::where('invoice_page_id','=',$invoice_page_id)->get();
									foreach($inv_details_row as $k => $v){
										$elec_barcode_stock_tmp = PosController::CheckStrLength($v['description'],0,30).':'.$v['quantity'].':'.$v['price'].':';
										if($now_side == 'L'){
											//目前在左邊的QR code
											if(PosController::CheckStrLen($elec_barcode_left) + PosController::CheckStrLen($elec_barcode_stock_tmp) <= $max_len){	
												$elec_barcode_left .= $elec_barcode_stock_tmp;
											}else{
												//超過長度,存到右邊QR code
												$elec_barcode_right .= $elec_barcode_stock_tmp;
												$now_side = 'R';
											}
										}else if($now_side == 'R'){
											//目前在右邊的QR code
											if(PosController::CheckStrLen($elec_barcode_right) + PosController::CheckStrLen($elec_barcode_stock_tmp) <= $max_len){
												$elec_barcode_right .= $elec_barcode_stock_tmp;
											}else{
												//超過長度,不存
												break;
											}
										}
									}
									//補齊長度
									$tmp_len = PosController::CheckStrLen($elec_barcode_left);
									for($i=0;$i<$max_len-$tmp_len;$i++){
										$elec_barcode_left .= ' ';
									}
									$elec_barcode_right = substr($elec_barcode_right,0,-1);	//移除最後':'
									$tmp_len = PosController::CheckStrLen($elec_barcode_right);
									for($i=0;$i<$max_len-$tmp_len;$i++)	$elec_barcode_right .= ' ';
									$invoice_super_array = Array();
									$invoice_super_array[0]['invoice_no'] = ($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no;
									//公司名稱
									$invoice_super_array[0]['invoice_title1'] = $row4[0]->coyname;
									//放機碼
									$invoice_super_array[0]['invoice_title2'] = $row3[0]->pos_no;
									$invoice_super_array[0]['invoice_title3'] = $row2[0]->sale_point_name;
									$invoice_super_array[0]['invoice_title4'] = $row2[0]->invoice_head5;
									//銷售日期
									$invoice_super_array[0]['invoice_title5'] = $sale['date'];
									//地址
									$invoice_super_array[0]['invoice_title6'] = $row2[0]->residence_address_county.$row2[0]->residence_address_city.$row2[0]->residence_address_street;
									//收銀機名稱
									$invoice_super_array[0]['invoice_title7'] = $row3[0]->cash_register_name;
									$invoice_super_array[0]['total'] = $sale['total'];
									$invoice_super_array[0]['max_row'] = '32';
									//發票日期
									$invoice_super_array[0]['invoice_date'] = $invoice_date_str;
									//賣家統編
									$invoice_super_array[0]['company_uniform_number'] = $company_uniform_number;
									//買家統編
									$invoice_super_array[0]['customer_uniform_number'] = $sale['uniform_number'];
									//發票格式
									$invoice_super_array[0]['invoice_format'] = $invoice_format;
									//隨機碼
									$invoice_super_array[0]['invoice_random_code'] = $invoice_random_code;
									//門市
									$invoice_super_array[0]['sale_point_name'] = $sale_point_name;
									//Barcode
									// 規則 民國年月5碼 + 發票10碼 + 隨機4碼 = 共計19碼
									$invoice_super_array[0]['barcode'] = $tmp_inv_row[0]->year . $last_month . (($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no) . $invoice_random_code;
									//以上開始產生加密24碼
									$invoice_super_array[0]['qrcode1'] = $elec_barcode_left;
									//把產生的隨機碼存入invoice_page內部
									$invoice_super_array[0]['qrcode2'] = $elec_barcode_right;
									//可放門市代碼
									$invoice_super_array[0]['invoice_remark2'] = $row2[0]->sale_point_code;
									//可放收銀機代碼
									$invoice_super_array[0]['invoice_remark3'] = $row3[0]->cash_register_name;
									$m_point = MemberPointLog::where([['deleted','=',0],['is_invalid','=',0],
														['member_id','=',$sale['member_id']],['effective_date','>=',date('Y-m-d')],
														[DB::raw('points - used_points'),'>',0]
														])
												->whereIn('point_type', ['1','3'])
												->selectRaw('IFNULL(SUM(points - used_points),0) can_point')
												->get();
									//放當下會員點數
									$invoice_super_array[0]['invoice_remark4'] = "會員點數：".$m_point[0]->can_point;
									//商品內容
									$invoice_super_array[0]['invoice_super_stock'] = $invoice_super_stock;
									InvoicePage::where('no','=',($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no)->update(['random_number' => $invoice_random_code]);
									ElectricInvoiceInfo::insert(['id'=>UUID::generate(),'host_id'=>'0001','sales_id'=>$sales_id,'sales_no'=>$sale['no'],
																'sales_time'=>$sale['date'],'elec_barcode_1'=>$tmp_inv_row[0]->year.$month_str,
																'elec_barcode_2'=>($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no,'elec_barcode_3'=>$invoice_random_code,
																'elec_barcode'=>($tmp_inv_row[0]->year.$last_month.(($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no).$invoice_random_code),
																'encrypt_key'=>$encrypt_key128,'elec_barcode_left_1'=>($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no,'elec_barcode_left_2'=>$tmp_sale_date,
																'elec_barcode_left_3'=>$invoice_random_code,'elec_barcode_left_4'=>(($sale['uniform_number'] == '')?'00000000':substr(('00000000'.dechex($sale['actualTotal']-$sale['taxTotal'])),-8)),
																'elec_barcode_left_5'=>substr(('00000000'.dechex($sale['total'])),-8),
																'elec_barcode_left_6'=>(($sale['uniform_number'] == '') ? '00000000' : $sale['uniform_number']),
																'elec_barcode_left_7'=>$company_uniform_number,'elec_barcode_left_8'=>$elec_barcode_left_8,
																'elec_barcode_left_foot'=>(':**********:'.(($sales_count>2)?2:$sales_count).':'.$sales_count.':1:'),
																'elec_barcode_left'=>$elec_barcode_left,'elec_barcode_right'=>$elec_barcode_right,
																'green_elec_barcode_left'=>$green_elec_barcode_left,'green_elec_barcode_right'=>$green_elec_barcode_right,
																'create_user_id'=>session('userid'),'create_user_name'=>session('username'),'create_time'=>$sale['date'] ]);
									//--------  電子發票 End  -------
									$invoice_super = array();
									$invoice_super['COMPORT'] = $row3[0]->register_com;
									$invoice_super['invoice'] = $invoice_super_array;
									//是否有找零
									$pay_back_money = 0;
									for($k=0;$k<count($sales_payments);$k++){
										$sales_payments_item = json_decode($sales_payments[$k],true);
										$sales_payments_item['pay_back_money'] = '';
										$pay_back_money = $sales_payments_item['pay_back_money'];
										$invoice_super['sale_point_payments'][$k]['name'] = $sales_payments_item['sale_point_payments_name'];
										if($pay_back_money > 0 && $invoice_super['sale_point_payments'][$k]['name'] == '現金'){
											$invoice_super['sale_point_payments'][$k]['value'] = ($sales_payments_item['value'] - $pay_back_money);
										}else{
											$invoice_super['sale_point_payments'][$k]['value'] = $sales_payments_item['value'];
										}
									}
									$invoice_super['noTaxTotal'] = $sale['actualTotal']-$sale['taxTotal'];
									$invoice_super['taxTotal'] = $sale['taxTotal'];

									$print_id = UUID::generate();
									InvoicePrint::insert(['id'=>$print_id,'invoice_print'=>base64_encode(json_encode($invoice_super,true)),'sales_id'=>$sales_id]);
								}
							}
						}
					}
					$paycard = '';
					//付款方式
					for($k=0;$k<count($sales_payments);$k++){
						$sales_payments_item = json_decode($sales_payments[$k],true);
						//是否有找零
						if($sale['all_backcash'] > 0 && ($k+1) == count($sales_payments)) $sales_payments_item['value'] = ($sales_payments_item['value'] - $sale['all_backcash']);
						
						if($sales_payments_item['sale_point_payments_id'] == '2' || $sales_payments_item['sale_point_payments_id'] == 'c9ac80d7-10cf-11ea-8764-0862666d1d41'){
							//信用卡資訊
							for($t=0;$t<count($creditcard);$t++){
								$Creditcard_item = json_decode($creditcard[$t],true);
								if($sales_payments_item['sale_point_payments_id'] != $Creditcard_item['card_payment_id']) continue;
								if($paycard=='') $paycard = $Creditcard_item['cardNo'];
								$sales_payments_id = UUID::generate();
								SalesPayments::insert(['id'=>$sales_payments_id,'sales_id'=>$sales_id,'sale_point_payments_id'=>$sales_payments_item['sale_point_payments_id'],
											'value'=>$Creditcard_item['value'],'date'=>$sale['date']]);
								CreditCard::insert(['id'=>UUID::generate(),'sales_payments_id'=>$sales_payments_id,'transType'=>$Creditcard_item['transType'],
												'transDate'=>str_replace('-','',substr($sale['date'],2,8)),
												'transTime'=>str_replace(':','',substr($sale['date'],11,10)),
												'cardNo'=>$Creditcard_item['cardNo'],'approvalCode'=>$Creditcard_item['approval_code']]);
							}
						}else{
							$sales_payments_id = UUID::generate();
							SalesPayments::insert(['id'=>$sales_payments_id,'sales_id'=>$sales_id,'sale_point_payments_id'=>$sales_payments_item['sale_point_payments_id'],
											'value'=>$sales_payments_item['value'],'date'=>$sale['date']]);
						}
					}
					//判斷找零
					//if($pay_back_money != 0) $sales_payments_item['sale_point_payments_id'] = getColumnValue($db,'sale_point_payments','id',"sale_point_payments_type_id='20'");
					$check_loc_qty = '';
					//判斷是否關帳
					//$check_close = CheckCostClose($db,$sale['date']);
					$invoice_details_all = array();
					$check_close = '';
					if($check_loc_qty != ''){
						echo $check_loc_qty ;
					}else if($check_close == 'close'){
						echo "此日期以關帳不可結帳!!";
					}else if(count($msg) == 0){
						DB::commit();
						// if($sale['actualTotal'] > 0 && $sale['sale_type'] == '1' && $sale['invoice_no'] != ''){
						//因為用綠界是自動配發票號碼
						if($sale['actualTotal'] > 0 && $sale['sale_type'] == '1' && $row2[0]->is_api == '1' && $sale['invoice_no'] == ''){
						// if(($sale['actualTotal'] > 0 && $sale['sale_type'] == '1' && $row2[0]->is_api == '1') || (session('userid') == 'admin')){
							$file =  tempnam(storage_path(), $row3[0]->id.'_');
							$connector = new FilePrintConnector($file);
							$printer = new Printer($connector);
							$buy_code = $sale['uniform_number'];
							if($v2['is_print'] == '1'){
								$printer -> printLogo(32,$row2[0]->inv_keycode);
								$printer -> setPrintLayout(52,64);
								$printer -> setPrintWidth(512);
								$printer -> setPosition(6,0);
								$printer -> setLineSpacing(25);
								$printer -> setPrintLeftMargin(0);
								$printer -> setTextSize(2,2);
								$printer -> textchinese("電子發票證明聯\n");
								$printer -> setTextSize(2,2);
								$printer -> setPosition(13,5);
								$printer -> setLineSpacing(2);
								$printer -> textchinese($invoice_super_array[0]['invoice_date']."\n");
								$printer -> setPosition(20,5);
								$printer -> textchinese(substr(($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no,0,2)."-".substr(($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no,2)."\n");
								/* Reset */
								$printer -> setFont(Printer::FONT_A); 
								$printer -> setTextSize(1,1);
								$printer -> setPrintWidth(512);
								$printer -> setPosition(24,0);
								$printer -> setLineSpacing(15);
								$printer -> setPrintLeftMargin(0);
								if($buy_code != ''){
									$printer -> textchinese($sale['date']."   格式：25\n");
								}else{
									$printer -> textchinese($sale['date']."\n");
								}
								$printer -> setPosition(28,0);
								$printer -> textchinese("隨機碼：".$invoice_random_code."   總計：".$sale['actualTotal']."\n");
								$printer -> setPosition(32,0);
								if($buy_code != ''){
									$printer -> textchinese("賣方 ".$company_uniform_number);
									$printer -> textchinese("  買方 ".$buy_code."\n");
								}else{
									$printer -> textchinese("賣方 ".$company_uniform_number."\n");
								}
								$printer -> setPosition(37,2);
								$printer -> setPrintLeftMargin(0);
								$printer -> setBarcodeWidth(1);
								$printer -> setBarcodeHeight(30);
								$printer -> barcode(($tmp_inv_row[0]->year.$last_month.(($sale['invoice_no'] != '')?$sale['invoice_no']:$tmp_invoice_no).$invoice_random_code), Printer::BARCODE_CODE39);
								$printer -> setPrintLeftMargin(25);
								$x = 38;
								$y = 2;
								$y2 = 25;
								$printer-> printImage($x,$y,$y2,$elec_barcode_left."\n",$elec_barcode_right."\n",Printer::QR_ECLEVEL_M, 3, Printer::QR_MODEL_2);
								//綠界的qrcode資訊，但印出來會不符合qrcode大小
								// $printer-> printImage($x,$y,$y2,$green_elec_barcode_left."\n",$green_elec_barcode_right."\n",Printer::QR_ECLEVEL_M, 3, Printer::QR_MODEL_2);
								$printer -> setPosition(59,0);
								$printer -> setPrintLeftMargin(0);
								// $printer -> textchinese($row2[0]->sale_point_name."   序".substr($sale['no'],-6)."  機".$invoice_super_array[0]['invoice_title2']."\n");
								$printer -> textchinese($row2[0]->sale_point_name."   序".substr($sale['no'],-6)."\n");
								$printer -> setPosition(63,0);
								$printer -> textchinese("退貨憑電子發票證明聯正本辦理\n");
								$printer -> feedForm();
								//裁切紙張
								if($buy_code == ''){
									$printer -> cut();
								}else{
									$printer -> feed(2);
								}
							}
							$printer -> printLogo(32,$row2[0]->inv_keycode);
							$printer -> textchinese("     交  易  明  細\n");
							$printer -> textchinese("門市：".$invoice_super_array[0]['invoice_title3']."\n");
							$printer -> textchinese($invoice_super_array[0]['invoice_title4']."\n");
							$printer -> textchinese("日期：".$sale['date']."\n");
							$printer -> textchinese("單號：".$sale['no']."\n");
							$printer -> textchinese("收銀員：".$sale['sales_user']."\n");
							$printer -> textchinese("會員代碼：".$v2['member_code']."\n");
							$printer -> textchinese("會員點數：".$m_point[0]->can_point."\n");
							if($sale['mobile_device'] != '') $printer -> textchinese("載具：".$sale['mobile_device']."\n");
							if($sale['love_code'] != '') $printer -> textchinese("愛心碼：".$sale['love_code']."\n");
							foreach($inv_details_row as $k => $v){
								$limit_count = 15;
								if(strlen($v['description']) == mb_strlen($v['description'],"utf-8")) $limit_count = 30;
								// $printer -> textchinese(substr($v['description'],0,40)."\n");
								for($i=0;$i < ceil(mb_strlen($v['description'],"utf-8")/$limit_count);$i++){
									if($i == 0){
										$printer -> textchinese(($k+1).".".mb_substr($v['description'],$i*$limit_count,$limit_count,"utf-8")."\n");
									}else{
										$printer -> textchinese(mb_substr($v['description'],$i*$limit_count,$limit_count,"utf-8")."\n");	
									}
								}
								$printer -> textchinese($v['price']."       ".$v['quantity']."        ".$v['subTotal'].(($v['tax'] >0)?'TX':'')."\n");
							}
							$printer -> textchinese("發票金額：".$sale['actualTotal']."\n");
							if($sale['discountTotal'] > 0) $printer -> textchinese("折扣金額：".$sale['discountTotal']."\n");
							if($buy_code != ''){
								$printer -> textchinese("未稅合計：".($sale['actualTotal'] - $sale['taxTotal'] - $diff_total)."\n");
								$printer -> textchinese("營業稅：".($sale['taxTotal'] + $diff_total)."\n");
							}
							for($k=0;$k<count($sales_payments);$k++){
								$sales_payments_item = json_decode($sales_payments[$k],true);
								$printer -> textchinese($sales_payments_item['sale_point_payments_name']."：".$sales_payments_item['value']."\n");
								if($sales_payments_item['sale_point_payments_name'] == '信用卡'){
									$printer -> textchinese("信用卡末四碼：".$paycard."\n");
								}
							}
							$printer -> cut();
							//打開錢櫃
							$printer -> pulse();
							//關閉發票連線
							$printer -> close();
							return 'ok@'.$member_msg.'@'.$row3[0]->id.explode($row3[0]->id,$file)[1];
						}else{
							return 'ok@'.$member_msg.'@';
						}
					}else{
						DB::rollback();
						return $msg[0];
					}
				break;
				case 'nosales':
					$userData = User::where('userid','=',$data->s_user)->get();
					if(count($userData) == 0){
						return '您輸入的帳號不存在';
					}else if($userData[0]->blocked == '1'){
						return '您輸入的帳號已封鎖';
					}elseif(sha1($data->s_pwd) == $userData[0]->password || $data->s_pwd == $userData[0]->password){
						$row_data = Sales::where('sales.id','=',$data->sales_id)
											->leftJoin('invoice_page','invoice_page.sales_id','=','sales.id')
											->leftJoin('member','member.id','=','sales.member')
											->select('sales.date','sales.uniform_number','member.member_name',
													'sales.sale_type','sales.is_return','sales.member','sales.loccode',
													'sales.is_internet','sales.love_code','sales.mobile_device',
													'sales.is_member_birthday_discount','sales.is_employee_birthday_discount',
													'sales.is_change_y_member','invoice_page.no','sales.no AS transactionsNo')
											->get();
						if($row_data[0]->is_return == 0){
							$row_data2 = SalesPayments::where('sales_id','=',$data->sales_id)->get();
							$row_data3 = MemberPointLog::where('sales_id','=',$data->sales_id)->get();
							//部分折讓，確認付款方式是不是一個以上
							if($data->s_return_type == '3'){
								if(count($row_data2) > 1) return '付款方式有一種以上，不允許部分折讓！';
								if(count($row_data3) > 1) return '當筆銷售單有獲得或使用點數，不允許部分折讓！';
							}
							$now_date = date('Y-m-d H:i:s');
							$row_data3 = MemberPointLog::where([['sales_id','=',$data->sales_id],['deleted','=','0']])->orderBy('point_type')->get();
							foreach($row_data3 as $point_k => $point_v){
								if($point_v->point_type == '1' || $point_v->point_type == '3'){
									if(round($point_v->used_points) > 0){
										$new_member_point_id = UUID::generate();
										MemberPointLog::insert(['id'=>$new_member_point_id,'host_id'=>'0001','point_type'=>$point_v->point_type,
												'sales_id'=>$data->sales_id,'sales_no'=>$row_data[0]->transactionsNo,'sales_date'=>$row_data[0]->date,
												'member_id'=>$row_data[0]->member,'points'=>'0','actual_total'=>'0','point_total'=>'0',
												'description'=>'銷退產生','events_schedule_id'=>$point_v->events_schedule_id,
												'money_conversion_points'=>$point_v->money_conversion_points,
												'member_fee_conversion_points'=>$point_v->member_fee_conversion_points,
												'effective_date'=>$point_v->effective_date,'create_time'=>$now_date,
												'create_user_id'=>session('userid'),'create_user_name'=>session('username') ]);
										MemberPointLog::where('from_member_points_log_id','=',$point_v->from_member_points_log_id)->update(['from_member_points_log_id'=>$new_member_point_id]);
									}
									MemberPointLog::where('id','=',$point_v->id)->update(['deleted'=>'1']);
								}else if($point_v->point_type == '2'){
									//若退到折抵是"銷退產生"的要把獲得點數加回去才可以繼續使用
									$row_data4 = MemberPointLog::where('sales_id','=',$point_v->from_member_points_log_id)->get();
									foreach($row_data4 as $point_k2 => $point_v2){
										if($point_v2->description =='銷退產生'){
											MemberPointLog::where('id','=',$point_v->from_member_points_log_id)->decrement('points',round($point_v->points));
										}else{
											MemberPointLog::where('id','=',$point_v->from_member_points_log_id)->increment('used_points',round($point_v->points));
										}
									}
									MemberPointLog::where('id','=',$point_v->id)->update(['deleted'=>'1']);
								}
							}
							if($row_data[0]->is_change_y_member == '1'){
								MemberPointLog::where([['member_id','=',$row_data[0]->member],['sales_id','=',$data->sales_id],
														['point_type','=','1'],['is_invalid','=','0'],['deleted','=','0']])
												->whereNull('description')
												->update(['is_invalid'=>'0']);
								Member::where('id','=',$row_data[0]->member)->update(['y_member'=>'0','member_start_date'=>NULL,'y_member_time'=>NULL,'member_end_date'=>NULL]);
								//是VIP才降回一般會員，員工則不降
								Member::where([['id','=',$row_data[0]->member],['s_member','=','5']])->update(['s_member'=>'4']);
							}
							//api商品陣列
							$api_product_arr = array();
							$SalesDay = new SalesDayController();
							$row2 = CashRegister::where('id','=',$data->cash_register_id)->get();
							$row3 = SalePoint::where('id','=',$row2[0]->sale_point_id)->get();
							$now_item = json_decode($data->return_sales_data,true);
							//新單的id
							$sales_id = UUID::generate();
							if($data->s_return_type == '0' || $data->s_return_type == '1'){
								$word = 'PR';
							}else{
								$word = 'PD';
							}
							//取單號
							$sale_data = DB::select("SELECT MAX(no) no FROM sales WHERE no LIKE '".$word.$row3[0]->sale_point_code."%'");
							if ($sale_data[0]->no != ''){
								$temp_no = substr($sale_data[0]->no,-6);
								$temp_no++;
								$now_temp_no = substr("000000".$temp_no,-6);
							}else{
								$now_temp_no = "000001";
							}
							//原銷售單更新退貨
							Sales::where('id','=',$data->sales_id)->update(['is_return' => 1]);
							//暫存變數
							$all_qty = 0;
							$all_total = 0;
							$all_discount_total = 0;
							$all_tax = 0;
							foreach($now_item as $k => $v){
								$tax_type = '';
								if($v['taxcatcode'] == 'TXI') $tax_type = '1';
								if($v['taxcatcode'] == 'TZ') $tax_type = '2';
								if($v['taxcatcode'] == 'TN') $tax_type = '3';
								$api_product_arr[] = array(	"ItemSeq"=> ($k+1),
															"ItemName"=> $v['product_name'],
															"ItemCount"=> (-1)*$v['quantity'],
															"ItemWord"=> "件",
															"ItemPrice"=> ($v['subTotal']/$v['quantity']),
															"ItemTaxType"=> $tax_type,
															"ItemAmount"=> (-1)*$v['subTotal']);
								//原銷售單明細更新退貨
								SalesDetails::where('id','=',$v['id'])->update(['is_return' => 1,'return_date' => $now_date]);
								//新單明細建立
								SalesDetails::insert(['id'=>UUID::generate(),'sales_id'=>$sales_id,'itemNo'=>($k+1),
									'stock_id'=>$v['product_code'],'stock_description'=>$v['product_name'],
									'is_return'=>'1','quantity'=>(-1)*$v['quantity'],'price'=>$v['price'],
									'subTotal'=>(-1)*$v['subTotal'],'salePrice'=>$v['salePrice'],
									'discountRate'=>$v['discountRate'],'taxcatid'=>$v['taxcatid'],
									'taxrate'=>$v['taxrate'],'tax'=>(-1)*$v['Tax'],
									'account_tax'=>(-1)*$v['Tax'],'accountPrice'=>(-1)*$v['subTotal'],
									'return_salesdetails_id'=>$v['id'],'promotion_id'=>$v['promotion_id'],
									'package_id'=>$v['package_id'],'parent_stockid'=>$v['parent_stockid'],
									'employee_birthday_discount_amount'=>$v['employee_birthday_discount_amount'],
									'is_free'=>$v['is_free'],'is_no_accumulated_amount'=>$v['is_no_accumulated_amount']
								]);
								$all_qty += $v['quantity'];
								$all_total += $v['subTotal'];
								$all_discount_total += ($v['price']*$v['quantity']) - $v['subTotal'];
								$all_tax += $v['Tax'];
								//加庫存
								if($row_data[0]->sale_type == '1'){
									$now_qty = LocStock::where('loccode','=',$row_data[0]->loccode)->where('stockid','=',$v['product_code'])->get();
									StockMoves::insert(['stkmoveno'=>UUID::generate(),'type'=>'11','transno'=>$word.$row3[0]->sale_point_code.$now_temp_no,
														'podetailitem'=>'0','trandate'=>substr($now_date,0,10),'stockid'=>$v['product_code'],
														'loccode'=>$row_data[0]->loccode,'prd'=>date("m"),
														'reference'=>'','price'=>$v['price'],
														'qty'=>$v['quantity'],'newqoh'=>$now_qty[0]->quantity + $v['quantity'],
														'confirm_user_id'=>session('userid'),'confirm_user_name'=>session('username')]);
									$SalesDay->doLoc(substr($now_date,0,10),$row_data[0]->loccode,$v['product_code'],$v['quantity'],'return_qty');
								}
							};
							//付款方式建立
							foreach($row_data2 as $k3 => $v3){
								$now_value = $v3['value'];
								if($data->s_return_type == '3') $now_value = $all_total;
								SalesPayments::insert(['id'=>UUID::generate(),'sales_id'=>$sales_id,'sale_point_payments_id'=>$v3['sale_point_payments_id'],
											'value'=>$now_value*(-1),'date'=>$now_date]);
							}
							//新單建立
							Sales::insert(['id'=>$sales_id,'no'=>$word.$row3[0]->sale_point_code.$now_temp_no,'date'=>$now_date,
									'user_id'=>session('userid'),'user_name'=>session('username'),
									'cash_register_id'=>$data->cash_register_id,'is_return'=>1,
									'reference'=>$row_data[0]->transactionsNo,'return_type'=>$data->s_return_type,
									'quantity'=>(-1)*$all_qty,'total'=>(-1)*($all_total+$all_discount_total),
									'discountRate'=>round(($all_total == 0 && $all_discount_total == 0)?100:(($all_total/($all_total+$all_discount_total))*100)),'actualTotal'=>(-1)*$all_total,
									'taxTotal'=>(-1)*$all_tax,'discountTotal'=>(-1)*$all_discount_total,
									'sale_type'=>4,'uniform_number'=>$row_data[0]->uniform_number,
									'member'=>$row_data[0]->member,'loccode'=>$row_data[0]->loccode,
									'point_discount_total'=>0,'point_discount'=>0,'is_internet'=>$row_data[0]->is_internet,
									'love_code'=>$row_data[0]->love_code,'mobile_device'=>$row_data[0]->mobile_device,
									'is_member_birthday_discount'=>$row_data[0]->is_member_birthday_discount,
									'is_employee_birthday_discount'=>$row_data[0]->is_employee_birthday_discount,
									'is_change_y_member'=>$row_data[0]->is_change_y_member,'salesmancode'=>$data->s_user ]);
							//印發票的檔名
							$show_file = '';
							$inv_data = Invoice::where([['invoice.year','=',(substr($row_data[0]->date,0,4) - 1911)],
												['invoice_details.no','=',$row_data[0]->no],
												['invoice.cash_register_id','=',$data->cash_register_id] ])
										->leftJoin('invoice_details','invoice_details.invoice_id','=','invoice.id')
										->select('invoice.invoice_type_id','invoice.is_api')
										->get();
							if(count($inv_data) > 0){
								//更新發票作廢狀態
								//PR銷退 PD折讓
								if($word == 'PR'){
									InvoiceDetails::where('no','=',$row_data[0]->no)->update(['is_disabled' => 1,'disabled_date' => date('Y-m-d')]);	
								}else if($word == 'PD'){
									InvoiceDetails::where('no','=',$row_data[0]->no)->update(['is_disabled' => 1,'disabled_date' => date('Y-m-d')]);
								}
								//綠界參數 START
								$ecinfo['MerchantID'] = $row3[0]->MerchantID;
								$ecinfo['url_base'] = 'https://einvoice.ecpay.com.tw';
								$ecinfo['key'] = $row3[0]->HashKey;
								$ecinfo['iv'] = $row3[0]->HashIV;
								$v2['invoice_no'] = $row_data[0]->no;
								$v2['invoice_date'] = substr($row_data[0]->date,0,10);
								$v2['total'] = $all_total;
								//綠界參數 END
								//原本為一般銷售 且 是電子發票類型 且 invoice要為is_api = 1 才要組，印發票的格式
								if($inv_data[0]->invoice_type_id == '5' && $row_data[0]->sale_type == '1' && $row3[0]->is_api == '1' && $inv_data[0]->is_api == '1'){
									//PR銷退 PD折讓
									if($word == 'PR'){
										$api_data = PosController::g_inv_invalid($v2,$ecinfo);
									}else if($word == 'PD'){
										$api_data = PosController::g_inv_allowance($v2,$ecinfo,$api_product_arr);
									}
									$api_data = urldecode(openssl_decrypt($api_data['Data'], 'AES-128-CBC', $ecinfo['key'], 0, $ecinfo['iv']));
									$api_data = json_decode($api_data);
									if(isset($api_data->RtnCode)){
										if($api_data->RtnCode != '1'){
											$msg[] = '綠界API錯誤訊息:'.$api_data->RtnMsg;
										} 
									}else{
										$msg[] = '綠界API發生錯誤';
									}
									$file = tempnam(storage_path(), $data->cash_register_id.'_');
									$connector = new FilePrintConnector($file);
									$printer = new Printer($connector);
									for($q=0;$q<2;$q++){
										$printer -> printLogo();
										$printer -> setPrintLayout(52,64);
										$printer -> setPrintWidth(512);
										$printer -> setPosition(6,0);
										$printer -> textchinese("營業人銷貨退回、進貨退出或\n");
										$printer -> textchinese("         折讓證明單\n");
										$printer -> setPosition(16,15);
										$printer -> textchinese(date('Y-m-d')."\n");
										$printer -> setPosition(22,0);
										$printer -> textchinese("賣方統編：".$row3[0]->uniform_number."\n");
										$printer -> setPosition(28,0);
										$full_company_name = "賣方名稱：".$row3[0]->full_company_name;
										$limit_count = 15;
										for($i=0;$i < ceil(mb_strlen($full_company_name,"utf-8")/$limit_count);$i++){
											$printer -> textchinese(mb_substr($full_company_name,$i*$limit_count,$limit_count,"utf-8")."\n");
										}
										$auto_h = ($i-1)*4;
										$printer -> setPosition(34+$auto_h,0);
										$printer -> textchinese("發票開立日期：".substr($row_data[0]->date,0,10)."\n");
										$printer -> setPosition(40+$auto_h,0);
										$printer -> textchinese($row_data[0]->no."\n");
										$printer -> setPosition(46+$auto_h,0);
										$printer -> textchinese("買方統編：".$row_data[0]->uniform_number."\n");
										$printer -> setPosition(52+$auto_h,0);
										$printer -> textchinese("買方名稱：\n");
										$printer -> setPosition(63+$auto_h,0);
										$printer -> feedForm();
										$printer -> feed(2);
										foreach($now_item as $k => $v){
											$limit_count = 15;
											if(strlen($v['product_name']) == mb_strlen($v['product_name'],"utf-8")) $limit_count = 30;
											for($i=0;$i < ceil(mb_strlen($v['product_name'],"utf-8")/$limit_count);$i++){
												$printer -> textchinese(mb_substr($v['product_name'],$i*$limit_count,$limit_count,"utf-8")."\n");
											}
											$printer -> textchinese($v['price']."       ".$v['quantity']."        ".$v['subTotal']."\n");
										}
										$printer -> feed(1);
										$printer -> textchinese("總數量：".$all_qty."\n");
										$printer -> textchinese("總金額：".$all_total."\n");
										$printer -> textchinese("未稅合計：".($all_total-$all_tax)."\n");
										$printer -> textchinese("稅額合計：".$all_tax."\n");
										$printer -> textchinese("應稅合計：".$all_total."\n");
										$printer -> feed(2);
										$printer -> textchinese("簽收人：\n");
										$printer -> cut();
									}
									//打開錢櫃
									$printer -> pulse();
									//關閉發票連線
									$printer -> close();
									$show_file = $data->cash_register_id.explode($data->cash_register_id,$file)[1];
								}
							}
							if(count($msg) == 0){
								DB::commit();
								return 'ok'."@".$show_file;
							}else{
								DB::rollback();
								return $msg[0];
							}
						}else{
							return '該筆已經有銷退單。';
						}
					}else{
						return '您輸入的密碼錯誤';
					}
				break;
				case 'reprint':
					$row = Sales::where('id','=',$data->sales_id)->get();
					$row2 = CashRegister::where('id','=',$data->cash_register_id)->get();
					$row3 = SalePoint::where('id','=',$row2[0]->sale_point_id)->get();
					$row4 = Member::where('id','=',$row[0]->member)->get();
					if($row3[0]->is_api == '1'){
						$inv = InvoicePrint::where('sales_id','=',$data->sales_id)->get();
						if(count($inv) > 0){
							$inv_data = json_decode(base64_decode($inv[0]->invoice_print),true);
							$file =  tempnam(storage_path(), $data->cash_register_id.'_');
							$connector = new FilePrintConnector($file);
							$printer = new Printer($connector);
							$buy_code = $inv_data['invoice'][0]['customer_uniform_number'];
							$company_uniform_number = $inv_data['invoice'][0]['company_uniform_number'];
							if($row[0]->mobile_device == '' && $row[0]->love_code == ''){
								$printer -> printLogo(32,$row3[0]->inv_keycode);
								$printer -> setPrintLayout(52,64);
								$printer -> setPrintWidth(512);
								$printer -> setPosition(6,0);
								$printer -> setLineSpacing(25);
								$printer -> setPrintLeftMargin(0);
								$printer -> setTextSize(1,2);
								$printer -> textchinese("  電 子 發 票 證 明 聯 補 印\n");
								$printer -> setTextSize(2,2);
								$printer -> setPosition(13,5);
								$printer -> setLineSpacing(2);
								$printer -> textchinese($inv_data['invoice'][0]['invoice_date']."\n");
								$printer -> setPosition(20,5);
								$printer -> textchinese(substr($inv_data['invoice'][0]['invoice_no'],0,2)."-".substr($inv_data['invoice'][0]['invoice_no'],2)."\n");
								/* Reset */
								$printer -> setFont(Printer::FONT_A); 
								$printer -> setTextSize(1,1);
								$printer -> setPrintWidth(512);
								$printer -> setPosition(24,0);
								$printer -> setLineSpacing(15);
								$printer -> setPrintLeftMargin(0);
								if($buy_code != ''){
									$printer -> textchinese($inv_data['invoice'][0]['invoice_title5']."   格式：25\n");
								}else{
									$printer -> textchinese($inv_data['invoice'][0]['invoice_title5']."\n");
								}
								$printer -> setPosition(28,0);
								$printer -> textchinese("隨機碼：".$inv_data['invoice'][0]['invoice_random_code']."   總計：".$inv_data['invoice'][0]['total']."\n");
								$printer -> setPosition(32,0);
								if($buy_code != ''){
									$printer -> textchinese("賣方 ".$company_uniform_number);
									$printer -> textchinese("  買方 ".$buy_code."\n");
								}else{
									$printer -> textchinese("賣方 ".$company_uniform_number."\n");
								}
								$printer -> setPosition(37,2);
								$printer -> setPrintLeftMargin(0);
								$printer -> setBarcodeWidth(1);
								$printer -> setBarcodeHeight(30);
								$printer -> barcode(($inv_data['invoice'][0]['barcode']), Printer::BARCODE_CODE39);
								$printer -> setPrintLeftMargin(25);
								$x = 38;
								$y = 2;
								$y2 = 25;
								$printer-> printImage($x,$y,$y2,$inv_data['invoice'][0]['qrcode1']."\n",$inv_data['invoice'][0]['qrcode2']."\n",Printer::QR_ECLEVEL_M, 3, Printer::QR_MODEL_2);
								$printer -> setPosition(59,0);
								$printer -> setPrintLeftMargin(0);
								$printer -> textchinese($inv_data['invoice'][0]['invoice_title3']."   序".substr($row[0]->no,-6)."\n");
								$printer -> setPosition(63,0);
								$printer -> textchinese("退貨憑電子發票證明聯正本辦理\n");
								$printer -> feedForm();
								//裁切紙張
								if($buy_code == ''){
									$printer -> cut();
								}else{
									$printer -> feed(2);
								}
							}else{
								$printer -> printLogo(32,$row3[0]->inv_keycode);
							}
							$printer -> textchinese("     交  易  明  細(補印)\n");
							$printer -> textchinese("門市：".$inv_data['invoice'][0]['invoice_title3']."\n");
							$printer -> textchinese($inv_data['invoice'][0]['invoice_title4']."\n");
							$printer -> textchinese("日期：".$inv_data['invoice'][0]['invoice_title5']."\n");
							$printer -> textchinese("單號：".$row[0]->no."\n");
							$printer -> textchinese("收銀員：".$row[0]->salesmancode."\n");
							$printer -> textchinese("會員代碼：".$row4[0]->member_code."\n");
							$printer -> textchinese($inv_data['invoice'][0]['invoice_remark4']."\n");
							if($row[0]->mobile_device != '') $printer -> textchinese("載具：".$row[0]->mobile_device."\n");
							if($row[0]->love_code != '') $printer -> textchinese("愛心碼：".$row[0]->love_code."\n");
							foreach($inv_data['invoice'][0]['invoice_super_stock'] as $k => $v){
								$limit_count = 15;
								if(strlen($v['description']) == mb_strlen($v['description'],"utf-8")) $limit_count = 30;
								for($i=0;$i < ceil(mb_strlen($v['description'],"utf-8")/$limit_count);$i++){
									$printer -> textchinese(mb_substr($v['description'],$i*$limit_count,$limit_count,"utf-8")."\n");
								}
								$printer -> textchinese($v['price']."       ".$v['quantity']."        ".$v['subtotal'].(($v['tax'] >0)?'TX':'')."\n");
							}
							$printer -> textchinese("發票金額：".$inv_data['invoice'][0]['total']."\n");
							if($row[0]->discountTotal > 0) $printer -> textchinese("折扣金額：".$row[0]->discountTotal."\n");
							if($buy_code != ''){
								$printer -> textchinese("未稅合計：".($inv_data['invoice'][0]['total'] - $row[0]->taxTotal)."\n");
								$printer -> textchinese("營業稅：".$row[0]->taxTotal."\n");
							}
							$paycard = '';
							$pay_data = SalesPayments::where('sales_payments.sales_id','=',$data->sales_id)
											->leftJoin('sale_point_payments','sale_point_payments.id','=','sales_payments.sale_point_payments_id')
											->leftJoin('sale_point_payments_type','sale_point_payments_type.id','=','sale_point_payments.sale_point_payments_type_id')
											->leftJoin('creditcard','creditcard.sales_payments_id','=','sales_payments.id')
											->select('sales_payments.id AS pay_id','sale_point_payments_type.name AS payments_type_name',
													'sale_point_payments.payment_name','sales_payments.value','creditcard.cardNo','creditcard.approvalCode')
											->orderBy('sale_point_payments.id')
											->get();
							foreach($pay_data as $k2 => $v2){
								$printer -> textchinese($v2->payment_name."：".$v2->value."\n");
								if($v2['sale_point_payments_name'] == '信用卡'){
									if($paycard == '') $printer -> textchinese("信用卡末四碼：".$v2->cardNo."\n");
									$paycard = $v2->cardNo;
								}
							}
							$printer -> cut();
							//打開錢櫃
							$printer -> pulse();
							//關閉發票連線
							$printer -> close();
							return 'ok@'.$data->cash_register_id.explode($data->cash_register_id,$file)[1];
						}
					}
				break;
			}
		}catch(\Exception $e){
			DB::rollback();
			if($data->types=='sales'){
				return '新增失敗'.$e;
				// return 'error@新增失敗'.$e->getMessage();
				// return json(['status' => false,'message' => '新增失敗']);
			}else{
				return '退貨失敗'.$e;
				// return '退貨失敗'.$e->getMessage();
			}
		}
    }
	function CheckStrLength($str1,$start,$end) {
		//對字串做URL Eecode
		$str1 = mb_substr($str1,$start,mb_strlen($str1));
		$short_str = urlencode($str1);
		$res = "";
		$now_length = 0;
		$k = 0;
		do{
			$lstrChar = substr($short_str, $k, 1);
			if($lstrChar == "%"){
				$ThisChr = hexdec(substr($short_str, $k+1, 2));
				if($ThisChr >= 128){
					if($now_length+3 < $end) $res .= urldecode(substr($short_str, $k, 9));
					$k = $k + 9;
					$now_length+=3;
				}else{
					$res .= urldecode(substr($short_str, $k, 3));
					$k = $k + 3;
					$now_length+=2;
				}
			}else{
				$res .= urldecode(substr($short_str, $k, 1));
				$k = $k + 1;
				$now_length++;
			}
		}while ($k < strlen($short_str) && $now_length < $end); 
		return $res;
	}
	function CheckStrLen($string){
		$s1 = strlen($string);
		$s2 = mb_strlen($string,"utf-8");
		$diff = ($s1 - $s2)/2;
		if($diff==0){
			return $s2;
		}else{
			//讓中文佔4字元
			return ($s2+$diff*3);
		}
	}
}
