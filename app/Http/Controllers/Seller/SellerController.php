<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\MyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class SellerController extends MyController
{   
    
    public function displayCatalogueView()
    {   
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $data = [
            'page_title' => 'Catalogue', 
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.catalogue')
        ];

        return view('template.main', $data);
    }

    public function displayTableView(Request $request)
    {   
        $resource = $request->path();
        $resource_name = ucwords(str_replace('-', ' ', $resource));
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $view_data = [
            'resource_name' => $resource_name,
            'table_headers' => $this->getResourceKeys($resource),
            'table_data' => $this->getResourceData($token, $resource)
        ];
        $data = [
            'page_title' => $resource_name, 
            'content_view' => View::make('seller.table', $view_data),
            'menus' => $this->getRoleMenus($token, $role_id),
        ];

        return view('template.main', $data);
    }

    public function getResourceData($token=null, $resource=null)
    {   
        $resource_data = [];
        if($token !== null && $resource != null){
            $request = $this->client->get($resource, [
                'headers' => [
                    'Authorization' => 'Bearer '.$token
                ]
            ]);
            $response = $request->getBody();
            $resource_data = json_decode($response, true);
        }

        return $resource_data;
    }

    public function getResourceKeys($resource=null)
    {
        $header_data = [];
        if($resource != null){
            $headers = [
                'products' => ['id', 'molecular_name', 'brand_name', 'pack_size', 'product_category', 'minimum_order_quantity', 'unit_price', 'delivery_cost'],
                'offers' => ['id', 'description', 'valid_from', 'valid_until', 'discount', 'max_discount_amount', 'organization'],
                'stocks' => ['id', 'product', 'stock_type', 'transaction_date', 'batch_number', 'expiry_date', 'quantity', 'balance', 'organization']
            ];
            $header_data = $headers[$resource];
        }

        return $header_data;
    }

    public function displayManageView(Request $request)
    {   
        $resource_name = $request->resource;
        $singular_resource_name = Str::singular($resource_name);
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $view_data = $this->getDropDownData($token, $resource_name);
        $view_data['manage_label'] = 'new';

        if($request->action){
            if($request->action == 'edit'){
                $view_data['manage_label'] = 'update';
                $view_data['edit'] = $this->getResourceData($token, $singular_resource_name.'/'.$request->id);
            }else{
                if($request->action == 'new'){
                    $response = $this->manageResourceData($token, 'POST', $singular_resource_name, $request->except('_token'));
                }else if($request->action == 'update'){
                    $response = $this->manageResourceData($token, 'PUT', $singular_resource_name.'/'.$request->id, $request->except('_token'));
                }else if($request->action == 'delete'){
                    $response = $this->manageResourceData($token, 'DELETE', $singular_resource_name.'/'.$request->id, $request->except('_token'));
                }

                //Handle response
                if(isset($response['error'])){
                    $flash_msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong> '.$response["error"].'
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>';
                }else{
                    $flash_msg = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong>Success!</strong> '.ucwords($singular_resource_name).' was managed successfully
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>';
                }
                $request->session()->flash('bgs_msg', $flash_msg);
                return redirect('/'.$resource_name);
            }
        }

        $data = [
            'page_title' => ucwords($resource_name), 
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.manage.'.$resource_name, $view_data)
        ];

        return view('template.main', $data);
    }

    public function getDropDownData($token=null, $resource=null)
    {   
        $dropdown_data = [];
        $data_sources = [
            'products' => ['product-categories'],
            'offers' => ['organizations'],
            'stocks' => ['products', 'stocktypes', 'organizations']
        ];

        if ($token !== null && $resource !== null){   
            foreach($data_sources[$resource] as $data_source){
                $dropdown_data[str_replace('-', '_', $data_source)] = $this->getResourceData($token, $data_source);
            }
        }
        
        return $dropdown_data;
    }

    public function manageResourceData($token=null, $rest_method=null, $resource=null, $request_data=null)
    {   
        $response = $this->client->request($rest_method, $resource, [
            'headers' => [
                'Authorization' => 'Bearer '.$token
            ],
            'json' => $request_data
        ]);

        $response = json_decode($response->getBody(), true);

        return $response;
    }
}
