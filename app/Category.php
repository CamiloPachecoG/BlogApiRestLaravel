<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    //Relacion de 1 a muchos
    public function posts(){
        return $this->HasMany('App\Post');
    }
}
