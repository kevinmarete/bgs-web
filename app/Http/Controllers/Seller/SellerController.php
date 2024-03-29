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
            'products' => $this->getResourceData($token, 'organization/' . $organization_id . '/stockbalances-pricelist')
        ];
        $data = [
            'page_title' => 'pricelist',
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.pricelist.new', $view_data)
        ];

        return view('template.main', $data);
    }

    public function getPricelistSubscriptionLimit($token, $organization_id)
    {
        $response = $this->getResourceData($token, 'organization/' . $organization_id . '/subscription');
        if (empty($response)) {
            return 0;
        }
        return intval(json_decode($response['package']['details'], true)['published_items']['value']);
    }

    public function getCurrentPublishedPricelist($token, $organization_id)
    {
        return sizeof($this->getResourceData($token, 'organization/' . $organization_id . '/published'));
    }

    public function savePricelist(Request $request)
    {
        $post_data = $request->all();
        $flash_id = 'bgs_msg';
        $redirect_url = '/pricelist';
        $errors = 0;

        $token = session()->get('token');
        $organization_id = session()->get('organization_id');
        $user_id = session()->get('id');

        foreach ($post_data['product_id'] as $key => $product_id) {
            $pricelist_data = [
                'unit_price' => $post_data['unit_price'][$key],
                'delivery_cost' => $post_data['delivery_cost'][$key],
                'is_published' => $post_data['is_published'][$key],
                'product_id' => $product_id,
                'organization_id' => $organization_id,
                'user_id' => $user_id,
            ];

            $pricelist_response = $this->manageResourceData($token, 'POST', 'productnow', $pricelist_data);
            if (!array_key_exists('id', $pricelist_response)) {
                $errors++;
                continue;
            }
        }

        if ($errors > 0) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> ' . $errors . ' pricelist item(s) were not saved successfully');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        $flash_msg = $this->getAlertMessage('success', '<strong>Success!</strong> Your pricelist item(s) were saved successfully');
        $request->session()->flash($flash_id, $flash_msg);
        return redirect($redirect_url);
    }

    public function displayImportPricelistView(Request $request)
    {
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $view_data = [
            'product_categories' => $this->getResourceData($token, 'product-categories')
        ];
        $data = [
            'page_title' => 'pricelist',
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.pricelist.import', $view_data)
        ];

        return view('template.main', $data);
    }

    public function importPricelist(Request $request)
    {
        $upload_file = $request->file('upload');
        $product_category_id = $request->product_category_id;
        $flash_id = 'bgs_msg';
        $redirect_url = '/pricelist';
        $max_file_size = env('UPLOAD_FILE_LIMIT');
        $errors = 0;

        $token = session()->get('token');
        $organization_id = session()->get('organization_id');
        $user_id = session()->get('id');

        if (!$request->has('upload')) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> No file selected for upload');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        $file_details = $this->getFileDetails($upload_file);
        if (!$this->isValidExtension($file_details['extension'], explode(',', env('UPLOAD_FILE_ACCEPT_EXTENSION')))) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> Invalid File Extension');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        if (!$this->isAllowedSize($file_details['size'], $max_file_size)) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> File too large. File must be less than 2MB');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        $import_data_arr = $this->getCsvData($file_details['path']);
        foreach ($import_data_arr as $import_data) {
            try {
                if (!isset($import_data[0]) && !isset($import_data[1]) && !isset($import_data[2]) && !isset($import_data[3])) {
                    $errors++;
                    continue;
                }
                $brand_name = $import_data[0];
                $molecular_name = $import_data[1];
                $pack_size = $import_data[2];
                $unit_price = $import_data[3];
                $available_stock = $import_data[4];
                //Add Product
                $product_data = [
                    'molecular_name' => $molecular_name,
                    'brand_name' => $brand_name,
                    'pack_size' => $pack_size,
                    'product_category_id' => $product_category_id,
                    'organization_id' => $organization_id,
                ];
                $product_response = $this->manageResourceData($token, 'POST', 'product', $product_data);
                if (!array_key_exists('id', $product_response)) {
                    $errors++;
                    continue;
                }
                //Add stock
                $product_id = $product_response['id'];
                $stock_data = [
                    'transaction_date' => date('Y-m-d'),
                    'batch_number' => strtoupper(Str::random(6)),
                    'expiry_date' => date('Y-m-t', strtotime('+1 year')),
                    'quantity' => intval($available_stock),
                    'balance' => intval($available_stock),
                    'product_id' => $product_id,
                    'stock_type_id' => 3,
                    'organization_id' => $organization_id,
                    'user_id' => $user_id,
                ];
                $stock_response = $this->manageResourceData($token, 'POST', 'stock', $stock_data);
                if (!array_key_exists('id', $stock_response)) {
                    $errors++;
                    continue;
                }
                //Add pricelist
                $pricelist_data = [
                    'unit_price' => floatval($unit_price),
                    'delivery_cost' => 0,
                    'is_published' => false,
                    'product_id' => $product_id,
                    'organization_id' => $organization_id,
                    'user_id' => $user_id,
                ];
                $pricelist_response = $this->manageResourceData($token, 'POST', 'productnow', $pricelist_data);
                if (!array_key_exists('id', $pricelist_response)) {
                    $errors++;
                    continue;
                }
            } catch (\Exception $e) {
                $e->getMessage();
                $errors++;
                continue;
            }
        }

        if ($errors > 0) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> ' . $errors . ' pricelist item(s) were not imported successfully');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        $flash_msg = $this->getAlertMessage('success', '<strong>Success!</strong> Your pricelist item(s) on ' . $file_details['name'] . ' were imported successfully');
        $request->session()->flash($flash_id, $flash_msg);
        return redirect($redirect_url);
    }

    public function displayPublishPricelistView(Request $request)
    {
        $resources = ['unpublished', 'published'];
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');
        $headers = $this->getResourceKeys('productnows');

        $view_data = [];
        foreach ($resources as $resource) {
            $view_data[$resource] = [
                'table_headers' => $headers,
                'table_data' => $this->getResourceData($token, 'organization/' . $organization_id . '/' . $resource)
            ];
        }
        $data = [
            'page_title' => 'pricelist',
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.pricelist.publish', $view_data)
        ];

        return view('template.main', $data);
    }

    public function publishPricelist(Request $request)
    {
        $token = session()->get('token');
        $organization_id = session()->get('organization_id');

        $flash_id = 'bgs_msg';
        $redirect_url = '/pricelist/publish';
        $is_published = $request->is_published;
        $pricelist_ids = json_decode($request->pricelist_ids, true);
        $action_label = ['unpublished', 'published'];

        if (!$pricelist_ids) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> Please select an item to be ' . $action_label[$is_published]);
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        $currently_published = $this->getCurrentPublishedPricelist($token, $organization_id);
        $num_of_pricelist = sizeof($pricelist_ids);
        $published_arr = [
            $currently_published - $num_of_pricelist, $num_of_pricelist + $currently_published
        ];
        $total_published = $published_arr[$is_published];
        $package_limit = $this->getPricelistSubscriptionLimit($token, $organization_id);
        $errors = 0;

        if ($total_published > $package_limit) {
            //Redirect to subscription page and recommend upgrade
            $flash_msg = $this->getAlertMessage('info', '<strong>Info!</strong> You cannot publish pricelist item(s) as you have exceeded your limit. Please <b>upgrade</b> you subscription under the "Subscription" tab below!');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect('/account');
        }

        foreach ($pricelist_ids as $pricelist_id) {
            $pricelist_data = [
                'is_published' => $is_published
            ];
            $pricelist_response = $this->manageResourceData($token, 'POST', 'productnow/' . $pricelist_id, $pricelist_data);
            if (!array_key_exists('id', $pricelist_response)) {
                $errors++;
                continue;
            }
        }

        if ($errors > 0) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> ' . $errors . ' pricelist item(s) were not ' . $action_label[$is_published] . ' successfully');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        $flash_msg = $this->getAlertMessage('success', '<strong>Success!</strong> Your pricelist item(s) were ' . $action_label[$is_published] . ' successfully');
        $request->session()->flash($flash_id, $flash_msg);
        return redirect($redirect_url);
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
        $parent_resource = 'promotions';
        $resources = ['slider', 'static'];
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');

        $view_data = [];
        foreach ($resources as $resource) {
            $view_data[$resource] = [
                'table_headers' => $this->getResourceKeys($parent_resource),
                'table_data' => $this->getResourceData($token, 'organization/' . $organization_id . '/' . $parent_resource . '-' . $resource)
            ];
        }

        $data = [
            'page_title' => 'Promotions',
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make('seller.promotions.listing', $view_data)
        ];

        return view('template.main', $data);
    }

    public function getPromotionBookingTotals($token, $organization_id, $type)
    {
        $bookings_totals = [];
        $bookings = $this->getResourceData($token, 'organization/' . $organization_id . '/promotions-' . $type);
        foreach ($bookings as $booking) {
            if ($booking['display_date'] >= date('Y-m-d')) {
                if (!array_key_exists($booking['display_date'], $bookings_totals)) {
                    $bookings_totals[$booking['display_date']] = 0;
                }
                $bookings_totals[$booking['display_date']] += 1;
            }
        }
        return $bookings_totals;
    }

    public function getOrganizationSubscriptionDetails($token, $organization_id)
    {
        $subscription_details = [];
        $response = $this->getResourceData($token, 'organization/' . $organization_id . '/subscription');
        if (empty($response)) {
            $response['package']['name'] = 'No';
            $response['package']['details'] = '{"promotions":{"value":"1","description":"0% Discount on Promotions"}}';
        }
        $subscription_details['name'] = $response['package']['name'];
        $subscription_details['details'] = json_decode($response['package']['details'], true);
        return $subscription_details;
    }

    public function displayNewPromotionView(Request $request)
    {
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');
        $type = $request->type;

        $subscription_detail = $this->getOrganizationSubscriptionDetails($token, $organization_id);
        $promotion_cost = $subscription_detail['details']['promotions']['value'] * env('PROMOTIONS_' . strtoupper($type) . '_COST');

        $view_data = [
            'type' => $type,
            'booking_limit' => env('PROMOTIONS_' . strtoupper($type) . '_LIMIT'),
            'promotion_cost' => $promotion_cost,
            'promotion_discount_description' => $subscription_detail['name'] . ' Package : ' . $subscription_detail['details']['promotions']['description'] . ' at KES.' . number_format($promotion_cost) . ' per Day',
            'bookings' => json_encode($this->getPromotionBookingTotals($token, $organization_id, $type)),
            'productnows' => $this->getResourceData($token, 'organization/' . $organization_id . '/published')
        ];
        $data = [
            'page_title' => 'promotions',
            'content_view' => View::make('seller.promotions.new', $view_data),
            'menus' => $this->getRoleMenus($token, $role_id),
        ];

        return view('template.main', $data);
    }

    public function saveFile($location, $file, $new_filename)
    {
        $file->move($location, $new_filename);
        return $location . $new_filename;
    }

    public function savePromotions(Request $request)
    {
        $type = $request->type;
        $display_date_str = $request->display_date;
        $display_dates = explode(',', $display_date_str);
        $upload_image = $request->file('upload');

        $flash_id = 'bgs_msg';
        $redirect_url = '/promotions';
        $promotion_cost = $request->promotion_cost;
        $max_file_size = env('UPLOAD_FILE_LIMIT');
        $location = env('PROMOTIONS_UPLOAD_DIR');
        $errors = 0;

        $token = session()->get('token');
        $organization_id = session()->get('organization_id');
        $organization_name = session()->get('organization.name');
        $user_id = session()->get('id');

        //Redirect if no display_dates
        if (strlen($display_date_str) == 0) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> Please select a display_date');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect('promotions/new/' . $type);
        }

        //Get organization payment_type
        $source_url = 'organization/' . $organization_id . '/payment-type';
        $source_response = $this->manageResourceData($token, 'GET', $source_url, []);

        //Redirect if no payment-type configured
        if (empty($source_response)) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> Please configure payment-type under account tab');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        if (!$request->has('upload')) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> No image selected for upload');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        $image_details = $this->getFileDetails($upload_image);
        if (!$this->isValidExtension($image_details['extension'], explode(',', env('UPLOAD_IMAGE_ACCEPT_EXTENSION')))) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> Invalid Image Extension');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        if (!$this->isAllowedSize($image_details['size'], $max_file_size)) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> File too large. File must be less than 2MB');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        $new_filename = strtolower($organization_name . '-' . $type . '-' . Str::random(6)) . '.' . $image_details['extension'];
        $display_url = $this->saveFile($location, $upload_image, $new_filename);

        //Make payment
        $payment_data = [
            'method' => $source_response['payment_type']['name'],
            'amount' => $promotion_cost * sizeof($display_dates),
            'source' => $source_response['payment_type']['details'],
            'destination' => [
                'paybill_number' => env('PAYBILL_NUMBER'),
                'account_number' => env('ACCOUNT_NUMBER')
            ]
        ];
        $payment_response = $this->process_payment($token, $organization_id, $user_id, $payment_data);
        if (!array_key_exists('id', $payment_response)) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> Payment was not successful, promotion could not be booked');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }
        $payment_id = $payment_response['id'];

        //Loop through display_dates
        foreach ($display_dates as $display_date) {
            //Add promotion
            $promotion_data = [
                'type' => $type,
                'status' => 'paid',
                'display_date' => $display_date,
                'display_url' => $display_url,
                'product_now_id' => $request->product_now_id,
                'organization_id' => $organization_id
            ];
            $promotion_response = $this->manageResourceData($token, 'POST', 'promotion', $promotion_data);
            if (!array_key_exists('id', $promotion_response)) {
                $errors++;
                continue;
            }
            $promotion_id = $promotion_response['id'];

            //Add payment_promotion
            $payment_promotion_data = ['payment_id' => $payment_id, 'promotion_id' => $promotion_id];
            $payment_promotion_response = $this->manageResourceData($token, 'POST', 'paymentpromotion', $payment_promotion_data);
            if (!array_key_exists('id', $payment_promotion_response)) {
                $errors++;
                continue;
            }
        }

        if ($errors > 0) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> ' . $errors . ' ' . $type . ' promotions were not added successfully');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        $flash_msg = $this->getAlertMessage('success', '<strong>Success!</strong> Your ' . $type . ' promotions were added successfully');
        $request->session()->flash($flash_id, $flash_msg);
        return redirect($redirect_url);
    }

    public function managePromotions(Request $request)
    {
        $resource_name = 'promotions';
        $singular_resource_name = Str::singular($resource_name);
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');
        $view_data = [
            'type' => $request->type,
            'productnows' => $this->getResourceData($token, 'organization/' . $organization_id . '/published'),
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
                    $update_data = $request->except('_token');
                    $upload_image = $request->file('upload');
                    if ($request->has('upload')) {
                        $flash_id = 'bgs_msg';
                        $redirect_url = '/promotions';
                        $max_file_size = env('UPLOAD_FILE_LIMIT');
                        $location = env('PROMOTIONS_UPLOAD_DIR');
                        $organization_name = session()->get('organization.name');
                        $image_details = $this->getFileDetails($upload_image);

                        if (!$this->isValidExtension($image_details['extension'], explode(',', env('UPLOAD_IMAGE_ACCEPT_EXTENSION')))) {
                            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> Invalid Image Extension');
                            $request->session()->flash($flash_id, $flash_msg);
                            return redirect($redirect_url);
                        }

                        if (!$this->isAllowedSize($image_details['size'], $max_file_size)) {
                            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> File too large. File must be less than 2MB');
                            $request->session()->flash($flash_id, $flash_msg);
                            return redirect($redirect_url);
                        }
                        $new_filename = strtolower($organization_name . '-' . $request->type . '-' . Str::random(6)) . '.' . $image_details['extension'];
                        $update_data['display_url'] = $this->saveFile($location, $upload_image, $new_filename);
                    }
                    $response = $this->manageResourceData($token, 'PUT', $singular_resource_name . '/' . $request->id, $update_data);
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
        $resource = 'offers';
        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');

        $view_data = [
            'table_headers' => $this->getResourceKeys($resource),
            'table_data' => $this->getResourceData($token, 'organization/' . $organization_id . '/' . $resource)
        ];

        $data = [
            'page_title' => 'Offers',
            'content_view' => View::make('seller.offers.listing', $view_data),
            'menus' => $this->getRoleMenus($token, $role_id),
        ];

        return view('template.main', $data);
    }

    public function saveOffers(Request $request)
    {
        $upload_image = $request->file('upload');

        $flash_id = 'bgs_msg';
        $redirect_url = '/offers';
        $product_ids = $request->product_now_ids;
        $total_offer_cost = $request->total_offer_cost;
        $max_file_size = env('UPLOAD_FILE_LIMIT');
        $location = env('OFFER_UPLOAD_DIR');
        $errors = 0;

        $token = session()->get('token');
        $organization_id = session()->get('organization_id');
        $organization_name = session()->get('organization.name');
        $user_id = session()->get('id');

        //Get organization payment_type
        $source_url = 'organization/' . $organization_id . '/payment-type';
        $source_response = $this->manageResourceData($token, 'GET', $source_url, []);

        //Redirect if no payment-type configured
        if (empty($source_response)) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> Please configure payment-type under account tab');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        if (!$request->has('upload')) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> No image selected for upload');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        $image_details = $this->getFileDetails($upload_image);
        if (!$this->isValidExtension($image_details['extension'], explode(',', env('UPLOAD_IMAGE_ACCEPT_EXTENSION')))) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> Invalid Image Extension');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        if (!$this->isAllowedSize($image_details['size'], $max_file_size)) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> File too large. File must be less than 2MB');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        $new_filename = strtolower($organization_name . '-' . Str::random(6)) . '.' . $image_details['extension'];
        $display_url = $this->saveFile($location, $upload_image, $new_filename);

        //Make payment
        $payment_data = [
            'method' => $source_response['payment_type']['name'],
            'amount' => $total_offer_cost,
            'source' => $source_response['payment_type']['details'],
            'destination' => [
                'paybill_number' => env('PAYBILL_NUMBER'),
                'account_number' => env('ACCOUNT_NUMBER')
            ]
        ];
        $payment_response = $this->process_payment($token, $organization_id, $user_id, $payment_data);
        if (!array_key_exists('id', $payment_response)) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> Payment was not successful, promotion could not be booked');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }
        $payment_id = $payment_response['id'];

        //Loop through display_dates
        foreach ($product_ids as $product_id) {
            //Add offer
            $offer_data = [
                'status' => 'paid',
                'valid_from' => $request->valid_from,
                'valid_until' => $request->valid_until,
                'display_url' => $display_url,
                'discount' => $request->discount,
                'min_order_quantity' => $request->min_order_quantity,
                'max_discount_amount' => $request->max_discount_amount,
                'product_now_id' => $product_id,
                'organization_id' => $organization_id
            ];
            $offer_response = $this->manageResourceData($token, 'POST', 'offer', $offer_data);
            if (!array_key_exists('id', $offer_response)) {
                $errors++;
                continue;
            }
            $offer_id = $offer_response['id'];

            //Add payment_offer
            $payment_offer_data = ['payment_id' => $payment_id, 'offer_id' => $offer_id];
            $payment_offer_response = $this->manageResourceData($token, 'POST', 'paymentoffer', $payment_offer_data);
            if (!array_key_exists('id', $payment_offer_response)) {
                $errors++;
                continue;
            }
        }

        if ($errors > 0) {
            $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> ' . $errors . ' offer(s) were not added successfully');
            $request->session()->flash($flash_id, $flash_msg);
            return redirect($redirect_url);
        }

        $flash_msg = $this->getAlertMessage('success', '<strong>Success!</strong> Your offer(s) were added successfully');
        $request->session()->flash($flash_id, $flash_msg);
        return redirect($redirect_url);
    }

    public function manageOffers(Request $request)
    {
        $resource_name = 'offers';
        $singular_resource_name = Str::singular($resource_name);
        $action = $request->action;

        $token = session()->get('token');
        $role_id = session()->get('organization.organization_type.role_id');
        $organization_id = session()->get('organization_id');

        if ($action == 'new') {
            $view_data = [
                'productnows' => $this->getResourceData($token, 'organization/' . $organization_id . '/published'),
                'offer_cost' => env('OFFER_COST'),
                'manage_label' => 'save'
            ];
            $content_page = 'seller.offers.new';
        } else if ($action == 'edit') {
            $view_data = [
                'edit' => $this->getResourceData($token, $singular_resource_name . '/' . $request->id),
                'productnows' => $this->getResourceData($token, 'organization/' . $organization_id . '/published'),
                'manage_label' => 'update'
            ];
            $content_page = 'seller.offers.edit';
        } else {
            if ($action == 'update') {
                $update_data = $request->except('_token');
                $upload_image = $request->file('upload');
                if ($request->has('upload')) {
                    $flash_id = 'bgs_msg';
                    $redirect_url = '/offers';
                    $max_file_size = env('UPLOAD_FILE_LIMIT');
                    $location = env('OFFER_UPLOAD_DIR');
                    $organization_name = session()->get('organization.name');
                    $image_details = $this->getFileDetails($upload_image);

                    if (!$this->isValidExtension($image_details['extension'], explode(',', env('UPLOAD_IMAGE_ACCEPT_EXTENSION')))) {
                        $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> Invalid Image Extension');
                        $request->session()->flash($flash_id, $flash_msg);
                        return redirect($redirect_url);
                    }

                    if (!$this->isAllowedSize($image_details['size'], $max_file_size)) {
                        $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> File too large. File must be less than 2MB');
                        $request->session()->flash($flash_id, $flash_msg);
                        return redirect($redirect_url);
                    }
                    $new_filename = strtolower($organization_name . '-' . Str::random(6)) . '.' . $image_details['extension'];
                    $update_data['display_url'] = $this->saveFile($location, $upload_image, $new_filename);
                }
                $response = $this->manageResourceData($token, 'PUT', $singular_resource_name . '/' . $request->id, $update_data);
            } else if ($action == 'delete') {
                $response = $this->manageResourceData($token, 'DELETE', $singular_resource_name . '/' . $request->id, $request->except('_token'));
            }

            //Handle response
            if (isset($response['error'])) {
                $flash_msg = $this->getAlertMessage('danger', '<strong>Error!</strong> ' . $response["error"]);
            } else {
                $flash_msg = $this->getAlertMessage('success', '<strong>Success!</strong> Offer was managed successfully');
            }
            $request->session()->flash('bgs_msg', $flash_msg);
            return redirect('/offers');
        }

        $data = [
            'page_title' => ucwords($resource_name),
            'menus' => $this->getRoleMenus($token, $role_id),
            'content_view' => View::make($content_page, $view_data)
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
                'offers' => ['id', 'valid from', 'valid until', 'discount', 'min order qty', 'product'],
                'stockbalances' => ['brand_name', 'molecular_name', 'pack_size', 'balance'],
                'productnows' => ['id', 'brand_name', 'molecular_name', 'pack_size', 'published'],
                'promotions' => ['id', 'status', 'display_date', 'product']
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
