@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">Interest Income Hitory</div>

                <div class="card-body" style="font-weight:lighter;">
                @if (session('success'))
                    <div class="alert alert-success">
                    {{ session('success')}}
                    </div>
                @endif
                    @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div><br />
                    @endif
                    @if(count($interest_incomes)>0)
                    <p>The table below shows YTD interest income history in Nigerian Naira.</p>
                        <table class="table table-striped table-sm">
                            <thead class="thead-dark">
                              <tr>
                                <th scope="col">S/N</th>
                                <th scope="col">Date</th>
                                <th scope="col">Class</th>
                                <th scope="col">GBP</th>
                                <th scope="col">USD</th>
                                <th scope="col">Action</th>
                              </tr>
                            </thead>
                            <tbody>
                            @php
                                $sn = 0;
                            @endphp
                            @foreach($interest_incomes as $interest_income)
                            @php
                                $sn++;
                            @endphp
                              <tr>
                                <th scope="row"><?php echo $sn;?></th>
                                <td>{{date('d-M-Y', strtotime($interest_income->reporting_date))}}</td>
                                <td>{{$interest_income->class}}</td>
                                <td>{{number_format($interest_income->gbp,2)}}</td>
                                <td>{{number_format($interest_income->usd,2)}}</td>
                                <td><a href="interest_income/edit/{{$interest_income->class}}/{{$interest_income->reporting_date}}">Edit</a>
                                <!-- <a href="interest_income/delete/{{$interest_income->class}}/{{$interest_income->reporting_date}}">Delete</a> -->
                                </td>
                              </tr>
                            @endforeach
                            </tbody>
                          </table>
                          <form id="interest_income-form" action="{{ url('logout') }}" method="POST" hidden>
                          <div class = "form-group">
                        <label>USD</label>
                        <input type="number" name="usd" class="form-control" step="0.0001" required><br>
                    </div>
                    <div class = "form-group">
                        <label>GBP</label>
                        <input type="number" name="gbp" class="form-control" step="0.0001" required><br>
                    </div>
                    <div class = "form-group">
                        <label>Reporting Date</label>
                        <input class="form-control" name="reporting_date" placeholder="Reporting Date" type="date" required />
                    </div>
                    <button class="btn btn-secondary">Update</button>
                                        @csrf
                                    </form>
                     
                    @else
                          <p class="alert alert-danger">No Interest Income history yet.</p>
                          @endif  
                </div>
            </div>
        
        </div>
    </div>
</div>
@endsection
<!-- @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif -->