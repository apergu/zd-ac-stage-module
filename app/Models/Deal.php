<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
  use HasFactory;

  protected $fillable = [
    'zd_deal_id',
    'zd_deal_name',
    'ac_deal_id',
    'ac_deal_name',
  ];
}
