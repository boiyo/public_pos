<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
	public $incrementing = false;
	public $timestamps = false;
	protected $table = 'address';
	protected $fillable = [    ];
}
