@extends('layouts.app')

@section('content')
   
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card bg-light mt-3">
        <div class="card-header">
            Money Market Placements with Subsidiary
        </div>
      <div class="card-body">
        @if ($errors->any())
        <div class="alert alert-danger">
            There were some errors with your request.
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        @if (session('success'))
        <div class="alert alert-success">
          {{ session('success')}}
        </div>
      @endif
              
        <div class = "row">
          <div class = "col-md-8">
          <form action="{{ url('/import_psub') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class = "form-group">
              <label>GBP</label><input type="file" name="file_1" class="form-control" required><br>
              @if (session('error_gbp'))
              <div class="alert alert-danger">
                {{ session('error_gbp') }}
              </div>
              @endif
              <label>USD</label><input type="file" name="file_2" class="form-control" required><br>
              @if (session('error_usd'))
              <div class="alert alert-danger">
                {{ session('error_usd') }}
              </div>
              @endif
              <button class="btn btn-success">Upload Cashflows</button>
            </div>
          </form>  
          <form action="{{ url('/export_psub') }}" method="GET">
            @csrf
            <div class = "form-group">
              <button class="btn btn-warning">Download Proof</button>
            </div>
          </form>
          </div>
      </div>
    </div>
  </div>
</div>
@endsection

