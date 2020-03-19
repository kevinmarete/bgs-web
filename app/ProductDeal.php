<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductDeal extends Model
{	
	use SoftDeletes;

    protected $table = 'tbl_product_deal';

    protected $fillable = ['minimum_order_quantity', 'product_now_id', 'offer_id'];

    public static $rules = [
        "minimum_order_quantity" => "required|numeric",
        "product_now_id" => "required|numeric",
        "offer_id" => "required|numeric"
	];

    public function product_now()
    {
        return $this->belongsTo('App\ProductNow');
    }
    
    public function offer()
    {
        return $this->belongsTo('App\Offer');
    }
}