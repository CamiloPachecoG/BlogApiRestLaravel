<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\JwtAuth;
use App\Post;

class PostController extends Controller
{
    public function __construct(){

        $this->middleware('api.auth', ['except' => 
        [
            'index', 
            'show', 
            'getImage', 
            'getPostsByCategory', 
            'getPostsByUser'
        ]]);
    }

    private function getIdentity($request){
        //Conseguir usuario identificado
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);

        return $user;
    }

    public function index(){

        $posts = Post::all()->load('category');

        return response()->json([

            'code'       => 200,
            'status'     => 'success',
            'posts' => $posts

        ], 200);
    }

    public function show($id){

        $post = Post::find($id)->load('category')
                               ->load('user');

        if(is_object($post)){

            $data = [
                
                'code'       => 200,
                'status'     => 'success',
                'post'   => $post
    
            ];

        }else{

            $data = [

                'code'       => 404,
                'status'     => 'error',
                'message'    => 'La categoria no existe'
    
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function store(Request $request){
        //Recoger los datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if(!empty($params_array)){
            //Conseguir usuario identificado
            $user = $this->getIdentity($request);

            //Validar datos
            $validate = \Validator::make($params_array, [
                'category_id'   => 'required',
                'title'         => 'required',
                'content'       => 'required',
                'image'         => 'required'
            ]);

            //Guardar Categoria
            if($validate->fails()){
                $data = [

                    'code'       => 404,
                    'status'     => 'error',
                    'message'    => 'No se ha guardado el post, faltan datos'
        
                ];
            }else{
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;
                $post->save();

                $data = [
                    
                    'code'       => 200,
                    'status'     => 'success',
                    'post'       => $post
        
                ];
            }
        }else{
            $data = [

                'code'       => 404,
                'status'     => 'error',
                'message'    => 'Envia los datos Correctamente.'
    
            ];
        }

        //Devolver resultado
        return response()->json($data, $data['code']);
    }


    public function update($id, Request $request){

        //Recoger los datos mediante Post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if(!empty($params_array)){
            //Valida Datos
            $validate = \Validator::make($params_array, [
                'category_id'   => 'required',
                'title'         => 'required',
                'content'       => 'required'
            ]);

            //Quitar lo que no quiero actualizar
            unset($params_array['id']);
            unset($params_array['user_id']);
            unset($params_array['created_at']);
            unset($params_array['user']);

             //Conseguir usuario identificado
            $user = $this->getIdentity($request);
            
            //Buscar el resgitro
            $post = Post::where('id', $id)
                    ->where('user_id', $user->sub)
                    ->first();
            
            if(!empty($post) && is_object($post)){

                //Actualizar registro
                $post->update($params_array);

                $data = [
                        
                    'code'       => 200,
                    'status'     => 'success',
                    'post'       => $params_array

                ];

            }else{
                $data = [

                    'code'       => 404,
                    'status'     => 'error',
                    'message'    => 'Este post no te pertenece'
        
                ];
            }

        }else{
            $data = [

                'code'       => 404,
                'status'     => 'error',
                'message'    => 'Datos enviado incorrectamente.'
    
            ];
        }
         //Devolver resultado
         return response()->json($data, $data['code']);
    }

    public function destroy($id, Request $request){
        //Conseguir usuario identificado
        $user = $this->getIdentity($request);

        //Conseguir el post
        $post = Post::where('id', $id)
                    ->where('user_id', $user->sub)
                    ->first();

        if(!empty($post)){
            //Borrarlo
            $post->delete();

            //Devolver resultado
            $data = [
                'code'   => 200,
                'status' => 'success',
                'post'   => $post
            ];
        }else{
            //Devolver resultado
            $data = [
                'code'      => 400,
                'status'    => 'error',
                'message'   => 'El post no existe.'
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function upload(Request $request){
        //Recoger la imagen de la peticion
        $image = $request->file('file0');

        //Validar la imagen
        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);

        //Guardar imagen Store\image
        if(!$image || $validate->fails()){
            $data = [
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'Error al subir la imagen.'
            ];
        }else{
            $image_name = time().$image->getClientOriginalName();

            \Storage::disk('images')->put($image_name, \File::get($image));

            $data = [
                'code'      => 200,
                'status'    => 'success',
                'image'     =>  $image_name
            ];

        }

        //Devolver resultado
        return response()->json($data, $data['code']);
    }

    public function getImage($filename){
        $isset = \Storage::disk('images')->exists($filename);

        if($isset){
            $file = \Storage::disk('images')->get($filename);
            return new Response($file, 200);
        }else{
            $data = array(
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'La imagen no existe'
            );

            return response()->json($data, $data['code']);
        }
    }

    public function getPostsByCategory($id){

        $posts = Post::where('category_id', $id)->get();

            return response()->json([
                'status' => 'success',
                'posts'  => $posts
            ], 200);

    }
    
    public function getPostsByUser($id){

        $posts = Post::where('user_id', $id)->get();

            return response()->json([
                'status' => 'success',
                'posts'  => $posts
            ], 200);

    }
}
