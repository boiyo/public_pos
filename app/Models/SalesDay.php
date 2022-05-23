<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesDay extends Model
{
	public $incrementing = false;
	public $timestamps = false;
	protected $table = 'sales_day';
	protected $fillable = [    ];
}
