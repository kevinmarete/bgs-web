<div class="sb-page-header pb-10 sb-page-header-dark bg-gradient-primary-to-secondary">
    <div class="container-fluid">
        <div class="sb-page-header-content py-5">
            <h1 class="sb-page-header-title">
                <div class="sb-page-header-icon"><i data-feather="activity"></i></div>
                <span>{{ $resource_name }} </span>
            </h1>
        </div>
    </div>
</div>
<div class="container-fluid mt-n10">
    @if (Session::has('bgs_msg'))
        {!! session('bgs_msg') !!}
    @endif
    <div class="card mb-4">
        <div class="card-header row">
            <div class="col-sm-12 col-md-9 col-lg-9">
                {{ $resource_name }} Listing
            </div>
            <div class="col-sm-12 col-md-3 col-lg-3">
                <a href="/manage/{{ str_replace(' ', '-', strtolower($resource_name)) }}"
                   class="btn btn-primary ml-auto"><i data-feather="plus"></i> Add {{ $resource_name }}</a>
            </div>
        </div>
        <div class="card-body">
            <div class="sb-datatable table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        @foreach ($table_headers as $key => $header)
                            <th>{{ ucwords($header) }}</th>
                        @endforeach
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        @foreach ($table_headers as $header)
                            <th>{{ ucwords($header) }}</th>
                        @endforeach
                        <th>Actions</th>
                    </tr>
                    </tfoot>
                    <tbody>
                    @foreach ($table_data as $row)
                        <tr>
                            @foreach ($table_headers as $header)
                                @if (strtolower($resource_name) === 'organizationsuppliercategories' && in_array($header, ["email", "primary_phone"]))
                                    <td>{{ $row["organization"][$header] }}</td>
                                @elseif (is_array($row[$header]))
                                    <td>{{ $row[$header][(array_key_exists('name', $row[$header]) ? 'name' : 'molecular_name')] }}</td>
                                @else
                                    <td>{{ $row[$header] }}</td>
                                @endif
                            @endforeach
                            <td>
                                <a href="/manage/{{ str_replace(' ', '-', strtolower($resource_name)) }}/edit/{{ $row['id'] }}"
                                   class="btn sb-btn-datatable sb-btn-icon sb-btn-transparent-dark mr-2">
                                    <i data-feather="edit"></i>
                                </a>
                                <a href="/manage/{{ str_replace(' ', '-', strtolower($resource_name)) }}/delete/{{ $row['id'] }}"
                                   class="delete btn sb-btn-datatable sb-btn-icon sb-btn-transparent-dark">
                                    <i data-feather="trash-2"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
