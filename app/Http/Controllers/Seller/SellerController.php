<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\MyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class SellerController extends MyController
{

    public function displayPricelistTableView()
    {
        $resource = 'productnows';
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');

        $view_data = [
            'resource_name' => $resource,
            'table_headers' => $this->getResourceKeys($resource),
            'table_data' => $this->getResourceData($token, 'organization/' . $organization_id . '/' . $resource)
        ];

        $data = [
            'page_title' => 'PriceList',
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.pricelist.listing', $view_data)
        ];

        return view('template.main', $data);
    }

    public function displayNewPricelistView(Request $request)
    {
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');
        $view_data = [
            'products' => $this->getResourceData($token, 'organization/' . $organization_id . '/stockbalances')
        ];
        $data = [
            'page_title' => 'pricelist',
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.pricelist.new', $view_data)
        ];

        return view('template.main', $data);
    }

    public function savePricelist(Request $request)
    {
        $token = session()->get('token');
        $post_data = $request->all();
        $organization_id = session()->get('organization_id');
        $user_id = session()->get('id');
        $errors = 0;
        $cost_per_product = env('ORDER_NOW_COST');

        //Get organization payment_type
        $source_url = 'organization/' . $organization_id . '/payment-type';
        $source_response = $this->manageResourceData($token, 'GET', $source_url, []);

        //Redirect if no payment-type configured
        if (empty($source_response)) {
            $flash_msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> Please configure payment-type under account tab!
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>';
            $request->session()->flash('bgs_msg', $flash_msg);
            return redirect('/pricelist');
        }

        //Make payment
        $payment_data = [
            'method' => $source_response['payment_type']['name'],
            'amount' => ($cost_per_product * sizeof($post_data['product_id'])),
            'source' => $source_response['payment_type']['details'],
            'destination' => [
                'paybill_number' => env('PAYBILL_NUMBER'),
                'account_number' => env('ACCOUNT_NUMBER')
            ]
        ];
        $payment_response = $this->process_payment($token, $organization_id, $user_id, $payment_data);
        $payment_id = $payment_response['id'];

        foreach ($post_data['product_id'] as $key => $product_id) {
            //Build request object
            $request_data = [
                'unit_price' => $post_data['unit_price'][$key],
                'delivery_cost' => $post_data['delivery_cost'][$key],
                'is_published' => $post_data['is_published'][$key],
                'product_id' => $product_id,
                'organization_id' => $organization_id,
                'user_id' => $user_id,
            ];

            //Send request data to Api
            $response = $this->client->post("productnow", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ],
                'json' => $request_data
            ]);

            $response = json_decode($response->getBody(), true);

            //Check success
            if (isset($response['error'])) {
                $errors += 1;
            } else {
                $product_now_id = $response['id'];
                //Send request data to Api for payment
                $response = $this->client->post("paymentnow", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . session()->get('token')
                    ],
                    'json' => ['payment_id' => $payment_id, 'product_now_id' => $product_now_id]
                ]);
            }
        }

        if ($errors > 0) {
            $flash_msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> ' . $errors . ' pricelist item(s) were not added successfully
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                                </button>
                            </div>';
        } else {
            $flash_msg = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Success!</strong> Your pricelist item(s) were added successfully
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>';
        }

        $request->session()->flash('bgs_msg', $flash_msg);

        return redirect('/pricelist');
    }

    public function displayImportPricelistView(Request $request)
    {
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');
        $view_data = [
            'products' => $this->getResourceData($token, 'organization/' . $organization_id . '/stockbalances')
        ];
        $data = [
            'page_title' => 'pricelist',
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.pricelist.import', $view_data)
        ];

        return view('template.main', $data);
    }

    public function displayPublishPricelistView(Request $request)
    {
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');
        $view_data = [
            'products' => $this->getResourceData($token, 'organization/' . $organization_id . '/stockbalances')
        ];
        $data = [
            'page_title' => 'pricelist',
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.pricelist.publish', $view_data)
        ];

        return view('template.main', $data);
    }

    public function managePricelist(Request $request)
    {
        $resource_name = 'productnows';
        $singular_resource_name = Str::singular($resource_name);
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');
        $view_data = [
            'products' => $this->getResourceData($token, 'organization/' . $organization_id . '/stockbalances')
        ];
        $view_data['manage_label'] = 'new';

        if ($request->action) {
            if ($request->action == 'edit') {
                $view_data['manage_label'] = 'update';
                $view_data['edit'] = $this->getResourceData($token, $singular_resource_name . '/' . $request->id);
            } else {
                if ($request->action == 'new') {
                    $response = $this->manageResourceData($token, 'POST', $singular_resource_name, $request->except('_token'));
                } else if ($request->action == 'update') {
                    $response = $this->manageResourceData($token, 'PUT', $singular_resource_name . '/' . $request->id, $request->except('_token'));
                } else if ($request->action == 'delete') {
                    $response = $this->manageResourceData($token, 'DELETE', $singular_resource_name . '/' . $request->id, $request->except('_token'));
                }

                //Handle response
                if (isset($response['error'])) {
                    $flash_msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong> ' . $response["error"] . '
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>';
                } else {
                    $flash_msg = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong>Success!</strong> Pricelist was managed successfully
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>';
                }
                $request->session()->flash('bgs_msg', $flash_msg);
                return redirect('/pricelist');
            }
        }

        $data = [
            'page_title' => 'pricelist',
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.pricelist.edit', $view_data)
        ];

        return view('template.main', $data);
    }

    public function displayStocksTableView(Request $request)
    {
        $resource = 'stockbalances';
        $resource_name = ucwords(str_replace('-', ' ', $resource));
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');
        $view_data = [
            'resource_name' => $resource_name,
            'table_headers' => $this->getResourceKeys($resource),
            'table_data' => $this->getResourceData($token, 'organization/' . $organization_id . '/' . $resource)
        ];
        $data = [
            'page_title' => $resource_name,
            'content_view' => View::make('seller.stocks.listing', $view_data),
            'menus' => $this->getRoleMenus($token, $role_id),
        ];

        return view('template.main', $data);
    }

    public function displayNewStockTransactionView(Request $request)
    {
        $resource_name = 'stockbalances';
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $view_data = $this->getDropDownData($token, $resource_name);
        $data = [
            'page_title' => 'Stocks',
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.stocks.new', $view_data)
        ];

        return view('template.main', $data);
    }

    public function saveStocks(Request $request)
    {
        $post_data = $request->all();
        $organization_id = session()->get('organization_id');
        $user_id = session()->get('id');
        $errors = 0;

        foreach ($post_data['product_id'] as $key => $product_id) {
            $batch_number = $post_data['batch_number'][$key];
            $expiry_date = $post_data['expiry_date'][$key];
            $quantity = $post_data['quantity'][$key];
            $stock_type_id = $post_data['stock_type_id'];

            //Get balance
            $balance_data = $this->getExpectedBalance([
                'batch_number' => $batch_number,
                'expiry_date' => $expiry_date,
                'quantity' => $quantity,
                'product_id' => $product_id,
                'stock_type_id' => $stock_type_id,
                'organization_id' => $organization_id
            ]);

            //Build request object
            $request_data = [
                'transaction_date' => $post_data['transaction_date'],
                'batch_number' => $batch_number,
                'expiry_date' => $expiry_date,
                'quantity' => $balance_data['quantity'],
                'balance' => $balance_data['closing_balance'],
                'product_id' => $product_id,
                'stock_type_id' => $stock_type_id,
                'organization_id' => $organization_id,
                'user_id' => $user_id,
            ];

            //Send request data to Api
            $response = $this->client->post("stock", [
                'headers' => [
                    'Authorization' => 'Bearer ' . session()->get('token')
                ],
                'json' => $request_data
            ]);

            $response = json_decode($response->getBody(), true);

            //Check success
            if (isset($response['error'])) {
                $errors += 1;
            }
        }

        if ($errors > 0) {
            $flash_msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> ' . $errors . ' stock item(s) were not added successfully
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                                </button>
                            </div>';
        } else {
            $flash_msg = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Success!</strong> Your stock item(s) were added successfully
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>';
        }

        $request->session()->flash('bgs_msg', $flash_msg);

        return redirect('/stocks');
    }

    public function getExpectedBalance($request_data = [])
    {
        $response_data = [];
        if (!empty($request_data)) {
            //Send request data to Api
            $response = $this->client->post("calculatebalance", [
                'headers' => [
                    'Authorization' => 'Bearer ' . session()->get('token')
                ],
                'json' => $request_data
            ]);

            $response_data = json_decode($response->getBody(), true);
        }
        return $response_data;
    }

    public function displayStockBinCardView(Request $request)
    {
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');
        $product_id = $request->productId;
        $view_data = [
            'product' => $this->getResourceData($token, 'product/' . $product_id),
            'stocks' => $this->getResourceData($token, 'organization/' . $organization_id . '/stocks/' . $product_id),
            'balances' => $this->getResourceData($token, 'organization/' . $organization_id . '/stockbalances/' . $product_id)
        ];
        $data = [
            'page_title' => 'Stocks',
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.stocks.view', $view_data)
        ];

        return view('template.main', $data);
    }

    public function displayPromotionsTableView()
    {
        $resource = 'productpromos';
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');

        $view_data = [
            'resource_name' => $resource,
            'table_headers' => $this->getResourceKeys($resource),
            'table_data' => $this->getResourceData($token, 'organization/' . $organization_id . '/' . $resource)
        ];

        $data = [
            'page_title' => 'Promotions',
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.promotions.listing', $view_data)
        ];

        return view('template.main', $data);
    }

    public function displayNewPromotionView(Request $request)
    {
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');
        $view_data = [
            'offers' => $this->getResourceData($token, 'organization/' . $organization_id . '/activeoffers'),
            'productnows' => $this->getResourceData($token, 'organization/' . $organization_id . '/productnows')
        ];
        $data = [
            'page_title' => 'promotions',
            'content_view' => View::make('seller.promotions.new', $view_data),
            'menus' => $this->getRoleMenus($token, $role_id),
        ];

        return view('template.main', $data);
    }

    public function savePromotions(Request $request)
    {
        $token = session()->get('token');
        $post_data = $request->all();
        $organization_id = session()->get('organization_id');
        $user_id = session()->get('id');
        $errors = 0;
        $cost_per_product = env('PROMO_COST');

        //Get organization payment_type
        $source_url = 'organization/' . $organization_id . '/payment-type';
        $source_response = $this->manageResourceData($token, 'GET', $source_url, []);


        //Redirect if no payment-type configured
        if (empty($source_response)) {
            $flash_msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> Please configure payment-type under account tab!
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>';
            $request->session()->flash('bgs_msg', $flash_msg);
            return redirect('/promotions');
        }

        //Make payment
        $payment_data = [
            'method' => $source_response['payment_type']['name'],
            'amount' => ($cost_per_product * sizeof($post_data['product_now_id'])),
            'source' => $source_response['payment_type']['details'],
            'destination' => [
                'paybill_number' => env('PAYBILL_NUMBER'),
                'account_number' => env('ACCOUNT_NUMBER')
            ]
        ];
        $payment_response = $this->process_payment($token, $organization_id, $user_id, $payment_data);
        $payment_id = $payment_response['id'];

        foreach ($post_data['product_now_id'] as $key => $product_now_id) {
            //Build request object
            $request_data = [
                'coupon_code' => $post_data['coupon_code'][$key],
                'offer_id' => $post_data['offer_id'][$key],
                'product_now_id' => $product_now_id
            ];

            //Send request data to Api
            $response = $this->client->post("productpromo", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ],
                'json' => $request_data
            ]);

            $response = json_decode($response->getBody(), true);

            //Check success
            if (isset($response['error'])) {
                $errors += 1;
            } else {
                $product_now_id = $response['id'];
                //Send request data to Api for payment
                $response = $this->client->post("paymentpromo", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . session()->get('token')
                    ],
                    'json' => ['payment_id' => $payment_id, 'product_promo_id' => $product_now_id]
                ]);
            }
        }

        if ($errors > 0) {
            $flash_msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> ' . $errors . ' Promotion Item(s) were not added successfully
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                                </button>
                            </div>';
        } else {
            $flash_msg = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Success!</strong> Your Promotion Item(s) were added successfully
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>';
        }

        $request->session()->flash('bgs_msg', $flash_msg);

        return redirect('/promotions');
    }

    public function managePromotions(Request $request)
    {
        $resource_name = 'productpromos';
        $singular_resource_name = Str::singular($resource_name);
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');
        $view_data = [
            'productnows' => $this->getResourceData($token, 'organization/' . $organization_id . '/productnows'),
            'offers' => $this->getResourceData($token, 'organization/' . $organization_id . '/offers')
        ];
        $view_data['manage_label'] = 'new';

        if ($request->action) {
            if ($request->action == 'edit') {
                $view_data['manage_label'] = 'update';
                $view_data['edit'] = $this->getResourceData($token, $singular_resource_name . '/' . $request->id);
            } else {
                if ($request->action == 'new') {
                    $response = $this->manageResourceData($token, 'POST', $singular_resource_name, $request->except('_token'));
                } else if ($request->action == 'update') {
                    $response = $this->manageResourceData($token, 'PUT', $singular_resource_name . '/' . $request->id, $request->except('_token'));
                } else if ($request->action == 'delete') {
                    $response = $this->manageResourceData($token, 'DELETE', $singular_resource_name . '/' . $request->id, $request->except('_token'));
                }

                //Handle response
                if (isset($response['error'])) {
                    $flash_msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong> ' . $response["error"] . '
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>';
                } else {
                    $flash_msg = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong>Success!</strong> Promotion was managed successfully
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>';
                }
                $request->session()->flash('bgs_msg', $flash_msg);
                return redirect('/promotions');
            }
        }

        $data = [
            'page_title' => 'promotions',
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.promotions.edit', $view_data)
        ];

        return view('template.main', $data);
    }

    public function displayOffersTableView(Request $request)
    {
        $resources = ['offers', 'productdeals'];
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');

        $view_data = [];
        foreach ($resources as $resource) {
            $view_data[$resource] = [
                'table_headers' => $this->getResourceKeys($resource),
                'table_data' => $this->getResourceData($token, 'organization/' . $organization_id . '/' . $resource)
            ];
        }

        $data = [
            'page_title' => 'Offers',
            'content_view' => View::make('seller.offers.listing', $view_data),
            'menus' => $this->getRoleMenus($token, $role_id),
        ];

        return view('template.main', $data);
    }

    public function manageOffers(Request $request)
    {
        $resource_name = 'offers';
        $singular_resource_name = Str::singular($resource_name);
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $view_data = $this->getDropDownData($token, $resource_name);
        $view_data['manage_label'] = 'new';

        if ($request->action) {
            if ($request->action == 'edit') {
                $view_data['manage_label'] = 'update';
                $view_data['edit'] = $this->getResourceData($token, $singular_resource_name . '/' . $request->id);
            } else {
                if ($request->action == 'new') {
                    $response = $this->manageResourceData($token, 'POST', $singular_resource_name, $request->except('_token'));
                } else if ($request->action == 'update') {
                    $response = $this->manageResourceData($token, 'PUT', $singular_resource_name . '/' . $request->id, $request->except('_token'));
                } else if ($request->action == 'delete') {
                    $response = $this->manageResourceData($token, 'DELETE', $singular_resource_name . '/' . $request->id, $request->except('_token'));
                }

                //Handle response
                if (isset($response['error'])) {
                    $flash_msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong> ' . $response["error"] . '
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>';
                } else {
                    $flash_msg = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong>Success!</strong> Offer was managed successfully
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>';
                }
                $request->session()->flash('bgs_msg', $flash_msg);
                return redirect('/offers');
            }
        }

        $data = [
            'page_title' => ucwords($resource_name),
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.offers.add_edit', $view_data)
        ];

        return view('template.main', $data);
    }

    public function displayNewDealView(Request $request)
    {
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');
        $view_data = [
            'offers' => $this->getResourceData($token, 'organization/' . $organization_id . '/activeoffers'),
            'productnows' => $this->getResourceData($token, 'organization/' . $organization_id . '/productnows')
        ];
        $data = [
            'page_title' => 'offers',
            'content_view' => View::make('seller.deals.new', $view_data),
            'menus' => $this->getRoleMenus($token, $role_id),
        ];

        return view('template.main', $data);
    }

    public function saveDeals(Request $request)
    {
        $token = session()->get('token');
        $post_data = $request->all();
        $organization_id = session()->get('organization_id');
        $user_id = session()->get('id');
        $errors = 0;
        $cost_per_product = env('DEAL_COST');

        //Get organization payment_type
        $source_url = 'organization/' . $organization_id . '/payment-type';
        $source_response = $this->manageResourceData($token, 'GET', $source_url, []);

        //Redirect if no payment-type configured
        if (empty($source_response)) {
            $flash_msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong> Please configure payment-type under account tab!
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>';
            $request->session()->flash('bgs_msg', $flash_msg);
            return redirect('/offers');
        }

        //Make payment
        $payment_data = [
            'method' => $source_response['payment_type']['name'],
            'amount' => ($cost_per_product * sizeof($post_data['product_now_id'])),
            'source' => $source_response['payment_type']['details'],
            'destination' => [
                'paybill_number' => env('PAYBILL_NUMBER'),
                'account_number' => env('ACCOUNT_NUMBER')
            ]
        ];
        $payment_response = $this->process_payment($token, $organization_id, $user_id, $payment_data);
        $payment_id = $payment_response['id'];

        foreach ($post_data['product_now_id'] as $key => $product_now_id) {
            //Build request object
            $request_data = [
                'minimum_order_quantity' => $post_data['minimum_order_quantity'][$key],
                'offer_id' => $post_data['offer_id'][$key],
                'product_now_id' => $product_now_id
            ];

            //Send request data to Api
            $response = $this->client->post("productdeal", [
                'headers' => [
                    'Authorization' => 'Bearer ' . session()->get('token')
                ],
                'json' => $request_data
            ]);

            $response = json_decode($response->getBody(), true);

            //Check success
            if (isset($response['error'])) {
                $errors += 1;
            } else {
                $product_now_id = $response['id'];
                //Send request data to Api for payment
                $response = $this->client->post("paymentdeal", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . session()->get('token')
                    ],
                    'json' => ['payment_id' => $payment_id, 'product_deal_id' => $product_now_id]
                ]);
            }
        }

        if ($errors > 0) {
            $flash_msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> ' . $errors . ' transactions were not added successfully
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                                </button>
                            </div>';
        } else {
            $flash_msg = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Success!</strong> Your deal item(s) were added successfully
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>';
        }

        $request->session()->flash('bgs_msg', $flash_msg);

        return redirect('/offers');
    }

    public function manageDeals(Request $request)
    {
        $resource_name = 'productdeals';
        $singular_resource_name = Str::singular($resource_name);
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');
        $view_data = [
            'productnows' => $this->getResourceData($token, 'organization/' . $organization_id . '/productnows'),
            'offers' => $this->getResourceData($token, 'organization/' . $organization_id . '/offers')
        ];
        $view_data['manage_label'] = 'new';

        if ($request->action) {
            if ($request->action == 'edit') {
                $view_data['manage_label'] = 'update';
                $view_data['edit'] = $this->getResourceData($token, $singular_resource_name . '/' . $request->id);
            } else {
                if ($request->action == 'new') {
                    $response = $this->manageResourceData($token, 'POST', $singular_resource_name, $request->except('_token'));
                } else if ($request->action == 'update') {
                    $response = $this->manageResourceData($token, 'PUT', $singular_resource_name . '/' . $request->id, $request->except('_token'));
                } else if ($request->action == 'delete') {
                    $response = $this->manageResourceData($token, 'DELETE', $singular_resource_name . '/' . $request->id, $request->except('_token'));
                }

                //Handle response
                if (isset($response['error'])) {
                    $flash_msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong> ' . $response["error"] . '
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>';
                } else {
                    $flash_msg = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong>Success!</strong> Deal was managed successfully
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>';
                }
                $request->session()->flash('bgs_msg', $flash_msg);
                return redirect('/offers');
            }
        }

        $data = [
            'page_title' => 'Offers',
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.deals.edit', $view_data)
        ];

        return view('template.main', $data);
    }

    public function getResourceData($token = null, $resource = null)
    {
        $resource_data = [];
        if ($token !== null && $resource != null) {
            $request = $this->client->get($resource, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            $response = $request->getBody();
            $resource_data = json_decode($response, true);
        }

        return $resource_data;
    }

    public function getResourceKeys($resource = null)
    {
        $header_data = [];
        if ($resource != null) {
            $headers = [
                'offers' => ['id', 'description', 'valid_from', 'valid_until', 'discount', 'max_discount_amount', 'organization'],
                'stockbalances' => ['brand_name', 'molecular_name', 'pack_size', 'balance'],
                'productnows' => ['id', 'brand_name', 'molecular_name', 'pack_size', 'published'],
                'productpromos' => ['id', 'brand_name', 'molecular_name', 'coupon_code', 'unit_price', 'discount', 'max_amount'],
                'productdeals' => ['id', 'brand_name', 'molecular_name', 'min_quantity', 'unit_price', 'discount', 'max_amount']
            ];
            $header_data = $headers[$resource];
        }

        return $header_data;
    }

    public function getDropDownData($token = null, $resource = null)
    {
        $dropdown_data = [];
        $organization_id = session()->get('organization_id');
        $data_sources = [
            'offers' => ['organizations' => 'organizations'],
            'stockbalances' => ['products' => 'organization/' . $organization_id . '/products', 'stocktypes' => 'stocktypes']
        ];

        if ($token !== null && $resource !== null) {
            foreach ($data_sources[$resource] as $data_ref => $data_source) {
                $dropdown_data[str_replace('-', '_', $data_ref)] = $this->getResourceData($token, $data_source);
            }
        }

        return $dropdown_data;
    }
}
