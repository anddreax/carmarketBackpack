<?php

namespace App\Http\Controllers;

use App\Models\assigned_cards;
use App\Models\User;
use App\Models\cards;
use App\Models\collections;
use App\Models\sales;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ActionsController extends Controller
{
    public function newUser(Request $request){
        //Array Asociativo que genera la respuesta
        $response = ['status'=>1, 'msg'=>''];
        $data = $request->getContent(); //recogemos datos
        $data = json_decode($data); //descodificamos los datos
        $user = new User();
        $validatedData = Validator::make($request->all(),[
            'name' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|regex:/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/'
        ]);

        if ($validatedData->fails()) {
            $response['status'] = 0;
            $response['msg'] = "Invalid format" . $validatedData->errors();
            return response()->json($response, 400);
        }else{

            try{
                $user->name  = $data->name;
                $user->email  = $data->email;
                $user->password  = Hash::make($data->password);
                if($data->role == 'personal' || $data->role == 'professional' || $data->role == 'admin'){
                    $user->role  = $data->role;
                    $user->save();
                    $response['msg'] = "User Save";
                    $response['satus'] = 1;
                }else{
                    $response['msg'] = 'Role not found';
                    $response['satus'] = 0;
                }
            }catch(\Exception $e){
                $response['msg'] = $e->getMessage();
                $response['satus'] = 0;
            }
    }
        return response()->json($response);
    }

    public function login(Request $request){
        $response = ['status'=>1, 'msg'=>''];
        $data = $request->getContent();
        $data = json_decode($data);


        try{
            $user = User::where('name', $data->name)->first();

            if(isset($data->name) && isset($data->password)){
                if($user){
                    if(Hash::check($data->password, $user->password)){
                        $apitoken =  Hash::make(now().$user->id);
                        $user->api_token = $apitoken;
                        $user->save();
                        $response['msg'] = "Your token is: " . $apitoken;
                        $response['satus'] = 1;

                    }else{
                        $response['msg']='Password is not correct';
                        $response['satus'] = 0;
                    }
                }else{
                    $response['msg']='User is not correct';
                    $response['satus'] = 0;
                }
            }else{
                $response['msg']='Data missing';
                $response['satus'] = 0;
            }

        }catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['satus'] = 0;
        }
        return response()->json($response);
    }

    public function recoverypass(Request $request){
        $response = ['status'=>1, 'msg'=>''];
        $data = $request->getContent();
        $data = json_decode($data);
        try{
            if($data->email){
                $email = $data->email;
                $user = User::where('email', $email)->first();
                if($user){
                    $password = Str::random(8);
                    $user->password = Hash::make($password);
                    $user->save();
                    $response ['msg'] = $password;
                    $response['satus'] = 1;
                }else{
                    $response ['msg'] = 'User not found';
                    $response['satus'] = 0;
                }
            }else{
                $response ['msg'] = 'Enter an email';
                $response['satus'] = 0;
            }
        }catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['satus'] = 0;
        }
        return response()->json($response);
    }

}
