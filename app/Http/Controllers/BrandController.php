<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\Product;
use App\Models\Brand;
use App\Models\IndexItem;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use DB;

class BrandController extends Controller
{
	function get_title(){
		$title = IndexItem::where('file_name','=',explode('/',Route::getCurrentRoute()->uri())[0])->get();
		return $title[0]->item_name;
    }
    public function index(Request $data){
        $row_data = Brand::where('code', 'like', "%".$data->s_brand_code."%")
							->where('name','like',"%".$data->s_brand_name."%")
							->select('id','code','name')
							->orderBy('code','ASC')
							->paginate(session('max_page'))->appends($data->input());
        return view('product.Brand', ['title' => BrandController::get_title(),
					'item_id' => 'Brand',
					'userid' => session('userid'),
					'username' => session('username'),
					'modeldata' => session('modeldata'),
					'workdata' => session('workdata'),
					'row_data' => $row_data ]);
    }
    public function create(Request $data){
        return view('product.Brand_create', ['title' => BrandController::get_title(),
					'item_id' => 'Brand',
					'userid' => session('userid'),
					'username' => session('username'),
					'modeldata' => session('modeldata'),
					'workdata' => session('workdata'),
					'is_show' => (count(request()->all()) == 0)?'1':$data->is_show ]);
    }
    public function store(Request $data){
        $msg = [];
        $validator = Validator::make(
						request()->all(),
						['brand_code' => 'required','brand_name' => 'required']
        );
        $validator->setAttributeNames(['brand_code' => '品牌代碼','brand_name' => '品牌名稱']);
        if($validator->fails()){
			$msg = $validator->errors()->all();
        }else{
			$data_check1 = Brand::where('code','=',$data->brand_code)->get();
			$data_check2 = Brand::where('name','=',$data->brand_name)->get();
			if(count($data_check1)>0){
				$msg = ['該品牌代碼已有存在，不可儲存'];
			}else if(count($data_check2)>0){
				$msg = ['該品牌名稱已有存在，不可儲存'];
			}else{
				DB::beginTransaction();
				try{
					$res = Brand::create(['code'=>$data->brand_code,'name'=>$data->brand_name,
								'create_user_id'=>session('userid'),'create_user_name'=>session('username'),'create_time'=>date('Y-m-d H:i:s')]);
					$id = $res['id'];
					DB::commit();
				}catch(\Exception $e){
					DB::rollback();
					$msg = ['儲存發生錯誤，請重新操作！'];
				}
			}
        }
        if(count($msg) > 0){
			return view('product.Brand_create', ['title' => BrandController::get_title(),
						'item_id' => 'Brand',
						'userid' => session('userid'),
						'username' => session('username'),
						'modeldata' => session('modeldata'),
						'workdata' => session('workdata'),
						'is_show' => $data->is_show,
						'msg' => $msg ]);
        }
        $row_data = Brand::where('id','=',$id)->get();
        return view('product.Brand_details', ['title' => BrandController::get_title(),
					'item_id' => 'Brand',
					'userid' => session('userid'),
					'username' => session('username'),
					'modeldata' => session('modeldata'),
					'workdata' => session('workdata'),
					'is_show' => $data->is_show,
					'msg' => $msg,
					'row_id' => $id,
					'row_data' => $row_data ]);
    }
    public function show($id,Request $data){
        $row_data = Brand::where('id','=',$id)->get();
        return view('product.Brand_details', ['title' => BrandController::get_title(),
					'item_id' => 'Brand',
					'userid' => session('userid'),
					'username' => session('username'),
					'modeldata' => session('modeldata'),
					'workdata' => session('workdata'),
					'is_show' => $data->is_show,
					'row_id' => $id,
					'row_data' => $row_data ]);
    }
    public function edit($id,Request $data){
        $row_data = Brand::where('id','=',$id)->get();
        return view('product.Brand_edit', ['title' => BrandController::get_title(),
					'item_id' => 'Brand',
					'userid' => session('userid'),
					'username' => session('username'),
					'modeldata' => session('modeldata'),
					'workdata' => session('workdata'),
					'is_show' => $data->is_show,
					'row_id' => $id,
					'row_data' => $row_data ]);
    }
    public function update($id,Request $data){
        $msg = [];
        if(count(request()->all())>0){
			$validator = Validator::make(
                        request()->all(),
                        ['brand_name' => 'required']
			);
			$validator->setAttributeNames(['brand_name' => '品牌名稱']);
			if($validator->fails()){
				$msg = $validator->errors()->all();
			}else{
				$data_check = Brand::where('id','<>',$id)->where('name','=',$data->brand_name)->get();
				if(count($data_check)>0){
					$msg = ['該品牌名稱已有存在，不可修改'];
				}else{
					DB::beginTransaction();
					try{
						Brand::where('id','=',$id)
							->update(['name' => $data->brand_name,'modify_user_id' => session('userid'),
								'modify_user_name' => session('username'),'modify_time' => date('Y-m-d H:i:s') ]);
						$msg = ['修改成功'];
						DB::commit();
					}catch(\Exception $e){
						DB::rollback();
						$msg = ['儲存發生錯誤，請重新操作！'];
					}
				}
			}
        }
		$row_data = Brand::where('id','=',$id)->get();
        return view('product.Brand_details', ['title' => BrandController::get_title(),
					'item_id' => 'Brand',
					'userid' => session('userid'),
					'username' => session('username'),
					'modeldata' => session('modeldata'),
					'workdata' => session('workdata'),
					'is_show' => $data->is_show,
					'msg' => $msg,
					'row_id' => $id,
					'row_data' => $row_data ]);
    }
    public function destroy($id){
        $data = Product::where('stock_brand_id','=',$id)->count();
        if($data > 0){
			return '該品牌已有商品主檔使用，不可刪除';
        }else{
			Brand::where('id','=',$id)->delete();
			return 'ok';
        }
    }
	
}
