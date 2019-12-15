<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Post;
use App\Category;

class PruebaController extends Controller
{
    public function testOrm(){

        $posts = Post::all();
        var_dump($posts);
        die();
    }
}
