<?php

namespace App;

class UserModel extends \Illuminate\Database\Eloquent\Model {

  protected $table = 'user';
  protected $hidden = [];
  public $timestamps = false;
}