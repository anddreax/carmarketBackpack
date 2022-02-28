<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Card;
use App\Models\Collection;
use App\Models\Sale;
use App\Models\AssignedCards;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CardsController extends Controller
{
    public function newCard(Request $request){
        //Array Asociativo que genera la respuesta
        $response = ['status'=>1, 'msg'=>''];
        $data = $request->getContent(); //recogemos datos
        $data = json_decode($data); //descodificamos los datos
        $card = new Card();

        try{
            if (isset($data->name) && isset($data->description)){
                $card->name  = $data->name;
                $card->description  = $data->description;
                if(isset($data->id_collection)){
                    if (Collection::where('id', $data->id_collection)->first()){
                    $card->save();
                    $id = DB::table('cards')->select('id')->where('name', $data->name)->orderBy('id', 'desc')->first();
                    $assigned = new AssignedCards();
                    $assigned->card_id = $id->id;
                    $assigned->collection_id = $data->id_collection;
                    $assigned->save();
                    $response['msg'] = "Card Save, Assigned Save";
                    $response['satus'] = 1;
                    }
                }else{
                    $response['msg'] = "You need to assign a collection for this card";
                    $response['satus'] = 0;
                }
            }else {
                $response['msg'] = "Please enter some data";
                $response['satus'] = 0;
            }
        }catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['satus'] = 0;
        }

        return response()->json($response);
    }

    public function newCollection(Request $request){
        //Array Asociativo que genera la respuesta
        $response = ['status'=>1, 'msg'=>''];
        $data = $request->getContent(); //recogemos datos
        $data = json_decode($data); //descodificamos los datos
        $collection = new Collection();
        try{
            if (isset($data->name) && isset($data->edition_date)){

                $collection->name  = $data->name;
                $collection->edition_date  = $data->edition_date;
                if(isset($collection->symbol)){
                    $collection->symbol  = $data->symbol;
                }else{
                    $collection->symbol  = 'default.png';
                }

                if(isset($data->id_card)){
                    $search = Card::where('id', $data->id_card)->first();
                    if($search){
                        // añadir a tabla intermedia
                        $collection->save();
                        $response['msg'] = "Card Save";
                        $response['satus'] = 1;
                    }else{
                        $card = new Card();
                        $card->name  = "card".$data->id_card;
                        $card->description  = "Introduce la descripcion de la carta";
                        $collection->save();
                        $card->save();
                        $response['msg'] = "Card Save, Collection Save";
                        $response['satus'] = 1;
                    }
                    $id = DB::table('collections')->select('id')->where('name', $data->name)->orderBy('id', 'desc')->first();
                    $assigned = new AssignedCards();
                    $assigned->collection_id = $id->id;
                    $assigned->card_id = $data->id_card;
                    $assigned->save();
                }else{
                    $response['collection'] = "You need to assign a card to this collection";
                    $response['satus'] = 0;
                }
            }else{
                $response['msg'] = "Data missing";
                $response['satus'] = 0;
            }

        }catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['satus'] = 0;
        }

        return response()->json($response);
    }

    public function newSale(Request $request){
        //Array Asociativo que genera la respuesta
        $response = ['status'=>1, 'msg'=>''];
        $data = $request->getContent(); //recogemos datos
        $data = json_decode($data); //descodificamos los datos
        $sale = new Sale();
        $validatedData = Validator::make($request->all(),[
            'id_card' => 'required|exists:cards,id',
            'amount' => 'required',
            'total_price' => 'required|Numeric'
        ]);

        if ($validatedData->fails()) {
            $response['status'] = 0;
            $response['msg'] = "Invalid format" . $validatedData->errors();
            return response()->json($response, 400);
        }else{
            $sale->id_card  = $data->id_card;
            $sale->id_user  = $request->user->id;
            $sale->amount  = $data->amount;
            $sale->total_price  = $data->total_price;
        }

        try{
            $sale->save();
            $response['msg'] = "Sale Save";
        }catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['satus'] = 0;
        }

        return response()->json($response);
    }

    public function listSales(Request $request){
        $response = ['status'=>1, 'msg'=>''];
        $logedUser = $request->user;
        $cardname = $request->input('name', '0');
        try{
            if($cardname!= 0){
                $sales = DB::table('cards')->join('sales','sales.id_card', '=','cards.id')
                                        ->join('users','sales.id_user', '=','users.id')
                                        ->select('cards.id', 'cards.name', 'sales.amount', 'sales.total_price', 'users.name as user')
                                        ->where('cards.name', 'like', '%'.$cardname.'%')
                                        ->get();
                $response['msg'] = $sales;
            }else{
                $sales = DB::table('cards')->join('sales','sales.id_card', '=','cards.id')
                                        ->join('users','sales.id_user', '=','users.id')
                                        ->select('cards.id', 'cards.name', 'sales.amount', 'sales.total_price', 'users.name as user')
                                        ->get();
                $response['msg'] = $sales;
            }
        }catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['satus'] = 0;
        }
        return response()->json($response);
    }

    public function listCards(Request $request){
        $response = ['status'=>1, 'msg'=>''];
        $cardname = $request->input('name', '0');
        try{

            $cards = Card::select('id', 'name');
            if($cardname!= 0){
                $cards = $cards->where('cards.name', 'like','%'.$cardname.'%');
                $response['msg'] = $cards->get();
                $response['status'] = 1;
            }else{
                $response['msg'] = $cards->get();
                $response['status'] = 1;
            }


        }catch(\Exception $e){
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: ".$e->getMessage();
        }

        return response()->json($response);
    }

    public function asignCards(Request $request){
        $response = ['status'=>1, 'msg'=>''];
        $data = $request->getContent();
        $data = json_decode($data);
        $assigned = new AssignedCards();
        $validatedData = Validator::make($request->all(),[
            'id_card' => 'required|int|exists:cards,id',
            'id_collection' => 'required|int|exists:collections,id'
        ]);

        if ($validatedData->fails()) {
            $response['status'] = 0;
            $response['msg'] = "Invalid format" . $validatedData->errors();
            return response()->json($response, 400);
        }else{
            try{
                $assigned->collection_id = $data->id_collection;
                $assigned->card_id = $data->id_card;
                $assigned->save();
                $response['msg'] = "Asignation save";
            }catch(\Exception $e){
                $respone['status'] = 0;
                $response['msg'] = "Se ha producido un error: ".$e->getMessage();
            }
        }

        return response()->json($respone);
    }
}
