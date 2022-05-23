<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
	public $incrementing = false;
	public $timestamps = false;
	protected $table = 'member';
	protected $fillable = [    ];
}
