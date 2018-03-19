<?php

namespace App;

class UserGroup extends \Illuminate\Database\Eloquent\Model {

  protected $table = 'user_group';
  protected $guarded = [];
  public $timestamps = false;

  public function url()
  {
    return '/groups/' . $this->id;
  }

}