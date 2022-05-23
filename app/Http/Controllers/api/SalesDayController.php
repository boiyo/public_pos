<?php

namespace App\Http\Controllers\api;

use App\Models\Product;
use App\Models\SalesDay;
use App\Models\LocStock;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class SalesDayController extends Controller
{
    public function checkProduct($date=null,$loccode=null,$stockid=null,$qty=null){
		if($date=='' || $loccode=='' || $stockid=='' || $qty=='') return 'error';
		$product = Product::where('stockid','=',$stockid)->get();
		if($product[0]->is_service =='1') return 'error';
		return 'ok';
    }
    public function updateData($date=null,$loccode=null,$stockid=null,$qty=null,$tables=null,$types=null,$item=null){
	if($tables == 'SalesDay'){
		if($item == 'now'){
			SalesDay::where([['stockid','=',$stockid],['loccode','=',$loccode],['trandate','=',$date]])->increment($types,$qty,['end_qty'=>DB::raw('end_qty + '.$qty)]);
		}else if($item == 'after'){
			SalesDay::where([['stockid','=',$stockid],['loccode','=',$loccode],['trandate','>',$date]])->increment('init_qty',$qty,['end_qty'=>DB::raw('end_qty + '.$qty)]);
		}else if($item == 'check'){
			if(count(SalesDay::where([['stockid','=',$stockid],['loccode','=',$loccode],['trandate','=',$date]])->get()) == 0){
				//抓上一期期末量當期初
				$last_data  = SalesDay::where([['stockid','=',$stockid],['loccode','=',$loccode],['trandate','<',$date]])->orderBy('trandate','DESC')->limit(1)->get();
				$qty = (count($last_data)==0)?'0':$last_data[0]->end_qty;
				SalesDay::insert(['stockid'=>$stockid,'loccode'=>$loccode,'trandate'=>$date,"sale_qty"=>'0',"gift_qty"=>'0',
					"free_qty"=>'0',"return_qty"=>'0',"sale_cost"=>'0',"free_cost"=>'0',"transout_used_cost"=>'0',
					"adjustin_cost"=>'0',"return_cost"=>'0',"issue_cost"=>'0',"transfer_issue_cost"=>'0',
					"wo_receive_cost"=>'0',"purchin_qty"=>'0',"transin_qty"=>'0',"adjustin_qty"=>'0',
					"purchout_qty"=>'0',"transout_qty"=>'0',"transout_used_qty"=>'0',"adjustout_qty"=>'0',
					"issue_qty"=>'0',"transfer_issue_qty"=>'0',"service_out_qty"=>'0',"wo_receive_qty"=>'0',
					"unit_cost"=>'0',"init_qty"=>$qty,"end_qty"=>$qty,"counts_in_qty"=>'0',"counts_out_qty"=>'0']);
			}
		}
		return 'ok';
	}else if($tables == 'LocStock'){
		LocStock::where('stockid','=',$stockid)->where('loccode','=',$loccode)->increment('quantity',$qty);
		return 'ok';
	}
    }
    public function doLoc($date=null,$loccode=null,$stockid=null,$qty=null,$types=null)
    {
		//判斷是否服務商品
		if(SalesDayController::checkProduct($date,$loccode,$stockid,$qty) == 'error') return;
		//當天有無,若無就建立
        if(SalesDayController::updateData($date,$loccode,$stockid,$qty,'SalesDay',$types,'check') != 'ok') return '當天庫存建立發生錯誤';
		//當天異動
		if(SalesDayController::updateData($date,$loccode,$stockid,$qty,'SalesDay',$types,'now') != 'ok') return '當天庫存更新發生錯誤';
		//當天之後異動(不含當天
		if(SalesDayController::updateData($date,$loccode,$stockid,$qty,'SalesDay',$types,'after') != 'ok') return '當天之後庫存更新發生錯誤';
		//最終庫存
		if(SalesDayController::updateData($date,$loccode,$stockid,$qty,'LocStock') != 'ok') return '最終庫存更新發生錯誤';
		return 'ok';
    }
}
