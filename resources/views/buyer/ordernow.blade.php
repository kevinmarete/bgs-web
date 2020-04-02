<div class="sb-page-header pb-10 sb-page-header-dark bg-gradient-primary-to-secondary">
    <div class="container-fluid">
        <div class="sb-page-header-content py-5">
            <h1 class="sb-page-header-title">
                <div class="sb-page-header-icon"><i data-feather="activity"></i></div>
                <span>OrderNow</span>
            </h1>
        </div>
    </div>
</div>
<div class="container-fluid mt-n10">
    @if (Session::has('bgs_msg'))
        {!! session('bgs_msg') !!}
    @endif
    <div class="card mb-4">
        <div class="card-header"> 
            <div class="container">
                <form class="form-inline my-2 my-lg-0 row">
                    <div class="input-group input-group-md col-md-11">
                        <input type="text" class="form-control search" aria-label="Small" aria-describedby="inputGroup-sizing-sm" placeholder="Search...">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-success btn-number">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <a class="btn btn-success btn-md col-md-1" href="/cart">
                        <i class="fa fa-shopping-cart"></i> Cart
                        <span class="badge badge-light ml-1">{{ sizeof(session()->get('cart')) }}</span>
                    </a>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="col">
                <div class="row product_list">
                    @foreach ($products as $product)
                        @if ($product['is_published'])
                            <div class="col-12 col-md-4 col-lg-3 box">
                                <div class="card">
                                    <div class="row">
                                        <div class="col">
                                            <img class="card-img-top img-thumbnail mx-auto d-block" src="/assets/img/medicine.png" alt="Card image cap">
                                        </div>
                                        <div class="col">
                                            <p class="btn btn-sm btn-warning btn-block">KES {{ number_format($product['unit_price']) }}</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <form role="form" action="/add-cart" method="POST">
                                                @csrf
                                                <input type="hidden" class="form-control" name="product_id" value="{{ $product['id'] }}">
                                                <input type="hidden" class="form-control" name="quantity" value="1">
                                                <input type="hidden" class="form-control" name="price" value="{{ $product['unit_price'] }}">
                                                <input type="hidden" class="form-control" name="delivery" value="{{ $product['delivery_cost'] }}">
                                                <input type="hidden" class="form-control" name="discount" value="0">
                                                <input type="hidden" class="form-control" name="sub_total" value="{{ $product['unit_price']*1 }}">
                                                <input type="hidden" class="form-control" name="product_name" value="{{ $product['product']['molecular_name'] }}">
                                                <input type="hidden" class="form-control" name="product_description" value="{{ $product['product']['brand_name'].' Packsize:'.$product['product']['pack_size'].' Strength:'.$product['product']['strength'] }}">
                                                <input type="hidden" class="form-control" name="organization_id" value="{{ $product['organization_id'] }}">
                                                <input type="hidden" class="form-control" name="organization_name" value="{{ $product['organization']['name'] }}">
                                                <input type="hidden" class="form-control" name="organization_email" value="{{ $product['user']['email'] }}">
                                                <button type="submit" class="btn btn-sm btn-success btn-block">Add to cart</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h4 class="card-title product-title">
                                            <a href="#" title="View Product" class="molecular_name">{{ $product['product']['molecular_name'] }}</a>
                                        </h4>
                                        <hr/>
                                        <p class="card-text product-description">
                                            <strong class="brand_name">{{ $product['product']['brand_name'] }}</strong> <br/>
                                            <strong>Packsize:</strong> {{ $product['product']['pack_size'] }} <br/>
                                            <strong>Strength:</strong> {{ $product['product']['strength'] }} <br/>
                                            <strong>Vendor:</strong> {{ $product['organization']['name'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>