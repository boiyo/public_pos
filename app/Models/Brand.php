<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;

class Brand extends Model
{
	protected $primaryKey = 'id';
	public $incrementing = false;
	public $timestamps = false;
	protected $table = 'brand';
	protected $fillable = ['code','name'];
	public static function boot()
	{
		parent::boot();
		//即將建立
		static::creating(function($model)
		{
			if(!$model->id){
				$model->id = (string)UUID::generate()->string;
			}
			if(!$model->create_user_id){
				$model->create_user_id = session('userid');
			}
			if(!$model->create_user_name){
				$model->create_user_name = session('username');
			}
			if(!$model->create_time){
				$model->create_time = date('Y-m-d H:i:s');
			}
		});
		//即將儲存
		static::saving(function($model)
		{
			if(!$model->modify_user_id){
				$model->create_user_id = session('userid');
			}
			if(!$model->modify_user_name){
				$model->create_user_name = session('username');
			}
			if(!$model->modify_time){
				$model->create_time = date('Y-m-d H:i:s');
			}
		});
		//即將更新
		static::updating(function($model)
		{
			dump('即將更新333');
		});
		//已經更新
		static::updated(function($model)
		{
			dump('已經更新444');
		});
		//已經建立
		static::created(function($model)
		{
			dump('已經建立555');
		});
		//已經儲存
		static::saved(function($model)
		{
			dump('已經儲存666');
		});
	}
}
