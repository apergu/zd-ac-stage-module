<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StageZdHasAc extends Model
{
  use HasFactory;

  protected $fillable = [
    'zd_stage_id',
    'ac_stage_id',
  ];
}
