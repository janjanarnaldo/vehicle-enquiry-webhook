<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Enquiry extends Model
{
  protected $guarded = [];

  public static function boot()
  {
    parent::boot();

    static::creating(function ($enquiry) {
      $enquiry->identifier = Str::random(25);
    });
  }
}
