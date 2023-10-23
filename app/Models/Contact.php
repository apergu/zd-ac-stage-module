<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
  use HasFactory;

  protected $fillable = [
    'zd_contact_id',
    'zd_contact_name',
    'ac_contact_id',
    'ac_contact_name',
  ];
}
