<div class="sb-page-header pb-10 sb-page-header-dark bg-gradient-primary-to-secondary">
    <div class="container-fluid">
        <div class="sb-page-header-content py-5">
            <h1 class="sb-page-header-title">
                <div class="sb-page-header-icon"><i data-feather="user"></i></div>
                <span>Account</span>
            </h1>
            <div class="sb-page-header-subtitle">Manage User Account</div>
        </div>
    </div>
</div>
<div class="container-fluid mt-n10">
    @if (Session::has('bgs_msg'))
        {!! session('bgs_msg') !!}
    @endif
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">Manage Account</div>
                <div class="card-body">
                    <div class="col-lg-12">
                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <a href="" data-target="#profile" data-toggle="tab" class="nav-link active">Profile</a>
                            </li>
                            <li class="nav-item">
                                <a href="" data-target="#password" data-toggle="tab" class="nav-link">Password</a>
                            </li>
                            @if(strtolower(session()->get('organization.organization_type.role.name')) == 'buyer')
                                <li class="nav-item">
                                    <a href="" data-target="#subscription" data-toggle="tab" class="nav-link">Subscription</a>
                                </li>
                                <li class="nav-item">
                                    <a href="" data-target="#loyalty" data-toggle="tab" class="nav-link">Loyalty</a>
                                </li>
                            @endif
                        </ul>
                        <div class="tab-content py-4">
                            <div class="tab-pane active" id="profile">
                                <form role="form" action="/update-account" method="POST">
                                    @csrf
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label form-control-label">Firstname</label>
                                        <div class="col-lg-9">
                                            <input class="form-control" type="hidden" value="{{ $profile['id'] }}" name="id" required>
                                            <input class="form-control" type="text" value="{{ $profile['firstname'] }}" name="firstname" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label form-control-label">Lastname</label>
                                        <div class="col-lg-9">
                                            <input class="form-control" type="text" value="{{ $profile['lastname'] }}" name="lastname" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label form-control-label">Organization</label>
                                        <div class="col-lg-9">
                                            <select class="form-control" size="0" disabled name="organization_id" required>
                                                <option value="">Select Organization</option>
                                                @foreach ($organizations as $organization)
                                                    @if ($organization['id'] === $profile['organization_id'])
                                                        <option value="{{ $organization['id'] }}" selected>{{ $organization['name'] }}</option>
                                                    @else
                                                        <option value="{{ $organization['id'] }}">{{ $organization['name'] }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label form-control-label">Email</label>
                                        <div class="col-lg-9">
                                            <input class="form-control" type="email" value="{{ $profile['email'] }}" name="email" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label form-control-label">Phone</label>
                                        <div class="col-lg-9">
                                            <input class="form-control" type="text" value="{{ $profile['phone'] }}" name="phone" onkeypress="return event.charCode >= 48 && event.charCode <= 57" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label form-control-label"></label>
                                        <div class="col-lg-9">
                                            <button type="reset" class="btn btn-secondary">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Update Profile</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="tab-pane" id="password">
                                <form role="form" action="/change-password" method="POST">
                                    @csrf
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label form-control-label">Current Password</label>
                                        <div class="col-lg-9">
                                            <input class="form-control" type="hidden" value="{{ $profile['email'] }}" name="email" required>
                                            <input class="form-control" type="password" value="" placeholder="*********" name="password" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label form-control-label">New Password</label>
                                        <div class="col-lg-9">
                                            <input class="form-control" type="password" value="" placeholder="*********" name="new_password" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label form-control-label">Confirm password</label>
                                        <div class="col-lg-9">
                                            <input class="form-control" type="password" value="" placeholder="*********" name="confirm_password" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label form-control-label"></label>
                                        <div class="col-lg-9">
                                            <button type="reset" class="btn btn-secondary">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Change Password</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="tab-pane" id="subscription">
                                <!--subscription options-->
                                <div class="container">
                                    <div class="card-deck mb-3 text-center">
                                        @foreach ($packages as $package)
                                            <div class="card mb-4 box-shadow">
                                                <div class="card-header">
                                                    <h4 class="my-0 font-weight-normal">{{ $package['name'] }}</h4>
                                                </div>
                                                <div class="card-body">
                                                    <h1 class="card-title pricing-card-title">Ksh.{{ $package['price'] }} <small class="text-muted">/ mo</small></h1>
                                                    <ul class="list-unstyled mt-3 mb-4">
                                                        @foreach (json_decode($package['details']) as $detail)
                                                            <li>{{ $detail }}</li>
                                                        @endforeach
                                                    </ul>
                                                    @if ($package['id'] === $subscription['package']['id'] && $subscription['status'] === 'active')
                                                        <button type="button" class="subscription-btn btn btn-lg btn-block btn-success" data-toggle="modal" data-target=".bd-example-modal-lg" data-price="{{ $package['price'] }}" data-package="{{ $package['id'] }}">Current Package</button>
                                                        <strong><small>Expires on: {{ $subscription['end_date'] }}  </small></strong>
                                                    @elseif ($package['id'] === $subscription['package']['id'] && $subscription['status'] !== 'active')
                                                        <button type="button" class="subscription-btn btn btn-lg btn-block btn-danger" data-toggle="modal" data-target=".bd-example-modal-lg" data-price="{{ $package['price'] }}" data-package="{{ $package['id'] }}">Expired Package</button>
                                                        <strong><small>Expired on: {{ $subscription['end_date'] }} </small></strong>
                                                    @else
                                                        <button type="button" class="subscription-btn btn btn-lg btn-block btn-primary" data-toggle="modal" data-target=".bd-example-modal-lg" data-price="{{ $package['price'] }}" data-package="{{ $package['id'] }}">Select Package</button>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>  
                                <!--payment options-->
                                <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLongTitle">Payment Options</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <article class="card">
                                                    <div class="card-body p-5">
                                                        <ul class="nav bg-light nav-pills rounded nav-fill mb-3" role="tablist">
                                                            <li class="nav-item">
                                                                <a class="nav-link active show" data-toggle="pill" href="#nav-tab-card">
                                                                <i class="fa fa-credit-card"></i> Debit/Credit Card</a></li>
                                                            <li class="nav-item">
                                                                <a class="nav-link" data-toggle="pill" href="#nav-tab-mobile">
                                                                <i class="fa fa-university"></i>  Mobile Money</a></li>
                                                        </ul>
                                                        <div class="tab-content">
                                                            <div class="tab-pane fade active show" id="nav-tab-card">
                                                                <form role="form" action="/save-subscription/card" method="POST">
                                                                    @csrf
                                                                    <div class="form-group">
                                                                        <label for="username">Full name (on the card)</label>
                                                                        <input type="hidden" class="form-control" name="start_date" value="{{ date('Y-m-d') }}">
                                                                        <input type="hidden" class="form-control" name="end_date" value="{{ date('Y-m-d', strtotime('+1 month')) }}">
                                                                        <input type="hidden" class="form-control" name="user_id" value="{{ $profile['id'] }}">
                                                                        <input type="hidden" class="form-control subscription-package" name="package_id">
                                                                        <input type="hidden" class="form-control subscription-price" name="price">
                                                                        <input type="text" class="form-control" name="card_name" required>
                                                                    </div> <!-- form-group.// -->

                                                                    <div class="form-group">
                                                                        <label for="cardNumber">Card number</label>
                                                                        <div class="input-group">
                                                                            <input type="text" class="form-control" name="card_number" onkeypress="return event.charCode >= 48 && event.charCode <= 57" required>
                                                                            <div class="input-group-append">
                                                                                <span class="input-group-text text-muted">
                                                                                    <i class="fab fa-cc-visa"></i> &nbsp; <i class="fab fa-cc-amex"></i> &nbsp; 
                                                                                    <i class="fab fa-cc-mastercard"></i> 
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    </div> <!-- form-group.// -->

                                                                    <div class="row">
                                                                        <div class="col-sm-8">
                                                                            <div class="form-group">
                                                                                <label><span class="hidden-xs">Expiration</span> </label>
                                                                                <div class="input-group">
                                                                                    <input type="text" class="form-control" placeholder="MM" name="expiry_month" onkeypress="return event.charCode >= 48 && event.charCode <= 57" required>
                                                                                    <input type="text" class="form-control" placeholder="YY" name="expiry_year" onkeypress="return event.charCode >= 48 && event.charCode <= 57" required>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-sm-4">
                                                                            <div class="form-group">
                                                                                <label data-toggle="tooltip" title="" data-original-title="3 digits code on back side of the card">CVV <i class="fa fa-question-circle"></i></label>
                                                                                <input type="text" class="form-control" name="cvv_code" onkeypress="return event.charCode >= 48 && event.charCode <= 57" required>
                                                                            </div> <!-- form-group.// -->
                                                                        </div>
                                                                    </div> <!-- row.// -->
                                                                    <button class="subscribe btn btn-primary btn-block" type="submit"> Confirm  </button>
                                                                </form>
                                                            </div> <!-- tab-pane.// -->
                                                            <div class="tab-pane fade" id="nav-tab-mobile">
                                                                <form role="form" action="/save-subscription/mobile" method="POST">
                                                                    @csrf
                                                                    <p>Mobile Money Details</p>
                                                                    <dl class="param">
                                                                        <dt>Paybill Number: </dt>
                                                                        <dd> {{ $payment['paybill_number'] }}</dd>
                                                                    </dl>
                                                                    <dl class="param">
                                                                        <dt>Account number: </dt>
                                                                        <dd> {{ $payment['account_number'] }}</dd>
                                                                    </dl>
                                                                    <dl class="param">
                                                                        <dt>Phone number: </dt>
                                                                        <dd> 
                                                                            <input type="hidden" class="form-control" name="start_date" value="{{ date('Y-m-d') }}">
                                                                            <input type="hidden" class="form-control" name="end_date" value="{{ date('Y-m-d', strtotime('+1 month')) }}">
                                                                            <input type="hidden" class="form-control" name="user_id" value="{{ $profile['id'] }}">
                                                                            <input type="hidden" class="form-control subscription-package" name="package_id">
                                                                            <input type="hidden" class="form-control" name="paybill_number" value="{{ $payment['paybill_number'] }}">
                                                                            <input type="hidden" class="form-control" name="account_number" value="{{ $payment['account_number'] }}">
                                                                            <input type="hidden" class="form-control subscription-price" name="price">
                                                                            <input type="text" name="phone" value="{{ $profile['phone'] }}" onkeypress="return event.charCode >= 48 && event.charCode <= 57" required/> 
                                                                        </dd>
                                                                    </dl>
                                                                    <p><strong>Note:</strong> Additional transaction costs will be charged</p>
                                                                    <button class="subscribe btn btn-primary btn-block" type="submit">Pay Now</button>
                                                                </form>
                                                            </div> <!-- tab-pane.// -->
                                                        </div> <!-- tab-content .// -->
                                                    </div> <!-- card-body.// -->
                                                </article>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> 
                            <div class="tab-pane" id="loyalty">
                                <div class="container">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="card-deck mb-3 text-center">
                                                <div class="card mb-4 box-shadow">
                                                    <div class="card-header">
                                                        <h4 class="my-0 font-weight-normal">My Points</h4>
                                                    </div>
                                                    <div class="card-body">
                                                        <form>
                                                            <div class="form-group">
                                                                <label for="redeem_points">                                                        
                                                                    <h1 class="card-title pricing-card-title"> {{ $loyalty['points'] }} </h1>
                                                                </label>
                                                                <input type="number" class="form-control" id="redeem_points" aria-describedby="pointsHelp"  min="{{ $min_redeem }}" max="{{ $loyalty['points'] }}" onkeypress="return event.charCode >= 48 && event.charCode <= 57" required>
                                                                <small id="pointsHelp" class="form-text text-muted">Redeem a minimum of {{ number_format($min_redeem) }} points.</small>
                                                            </div>
                                                            <button class="btn btn-lg btn-block btn-primary">Redeem</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <table class="table table-condensed table-hover table-bordered table-sm">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>How Earned</th>
                                                        <th>Points</th>
                                                        <th>When</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($loyalty['loyalty_logs'] as $point)
                                                        <tr>
                                                            <td>{{ $point['status'] }}</td>
                                                            <td>{{ $point['points'] }}</td>
                                                            <td>{{ $point['created_at'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="thead-light">
                                                    <tr>
                                                        <th>How Earned</th>
                                                        <th>Points</th>
                                                        <th>When</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div> 
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>