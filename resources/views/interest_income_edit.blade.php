@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">Adjust Interest Income</div>

                <div class="card-body">
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
                <form action="{{ url('/interest_income/edit') }}" method="POST">
                    @csrf
                    <div class = "form-group">
                        <label>Class</label>
                        <input type="text" name="class" class="form-control" value="{{$class}}" readonly><br>
                    </div>
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
                        <input class="form-control" name="reporting_date" placeholder="Reporting Date" value="{{$date}}" type="date" readonly />
                    </div>
                    <button class="btn btn-secondary">Update</button>
                </form>
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