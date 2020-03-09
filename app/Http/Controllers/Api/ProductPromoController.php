<?php

namespace App\Http\Controllers\Api;

use App\ProductPromo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductPromoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $productpromos = ProductPromo::with('product_now', 'offer')->get();
        return response()->json($productpromos);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, ProductPromo::$rules);
        $productpromo = ProductPromo::firstOrCreate($request->all(), $request->all());
        return response()->json($productpromo);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $productpromo = ProductPromo::with('product_now', 'offer')->find($id);
        if(is_null($productpromo)){
            return response()->json(['error' => 'not_found']);
        }
        return response()->json($productpromo);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, ProductPromo::$rules);
        $productpromo  = ProductPromo::find($id);
        if(is_null($productpromo)){
            return response()->json(['error' => 'not_found']);
        }
        $productpromo->update($request->all());
        return response()->json($productpromo);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $productpromo = ProductPromo::find($id);
        if(is_null($productpromo)){
            return response()->json(['error' => 'not_found']);
        }
        $productpromo->delete();
        return response()->json(['msg' => 'Removed successfully']);
    }
}