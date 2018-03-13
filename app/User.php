<?php

namespace App;

class User extends \Illuminate\Database\Eloquent\Model {

  protected $table = 'user';
  protected $hidden = [];
  public $timestamps = false;
}