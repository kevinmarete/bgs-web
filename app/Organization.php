<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{	
	use SoftDeletes;

    protected $table = 'tbl_organization';

    protected $fillable = ['name', 'town', 'road', 'building', 'organization_type_id'];

    public static $rules = [
        "name" => "required",
        "town" => "required",
        "road" => "required",
        "building" => "required",
        "organization_type_id" => "required|numeric"
	];

    public function organizationtype()
    {
        return $this->belongsTo('App\OrganizationType');
    }

    public function users()
    {
        return $this->hasMany('App\User');
    }

}