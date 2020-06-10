<div class="sb-page-header pb-10 sb-page-header-dark bg-gradient-primary-to-secondary">
    <div class="container-fluid">
        <div class="sb-page-header-content py-5">
            <h1 class="sb-page-header-title">
                <div class="sb-page-header-icon"><i data-feather="activity"></i></div>
                <span>New {{ ucwords($type) }} Promotion</span>
            </h1>
        </div>
    </div>
</div>
<div class="container-fluid mt-n10">
    <div class="card mb-4">
        <div class="card-header"> </div>
        <div class="card-body">
            <form role="form" action="/promotions/save" method="POST">
                @csrf
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="sb-datatable table-responsive">
                                <table class="table table-bordered table-hover transactions-tbl" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Offer</th>
                                            <th>CouponCode</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Offer</th>
                                            <th>CouponCode</th>
                                            <th>Actions</th>
                                        </tr>
                                    </tfoot>
                                    <tbody>
                                        <tr class="tr_clone">
                                            <td>
                                                <select class="product col-md-12" size="0" name="product_now_id[]" required>
                                                    <option value="">Select Product</option>
                                                    @foreach ($productnows as $productnow)
                                                    <option value="{{ $productnow['id'] }}">{{ $productnow['product']['molecular_name']  }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select class="form-control offer_id" size="0" name="offer_id[]" required>
                                                    <option value="">Select Offer</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="coupon_code" value="" name="coupon_code[]" required />
                                            </td>
                                            <td>
                                                <a href="#" class="add"><i class="fa fa-plus"></i></a> |
                                                <a href="#" class="remove"><i class="fa fa-minus"></i></a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>