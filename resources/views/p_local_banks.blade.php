@extends('layouts.app')

@section('content')
   
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card bg-light mt-3">
        <div class="card-header">
            Money Market Placements with Local Banks
        </div>
      <div class="card-body">
      @if (session('success'))
          <div class="alert alert-success">
          {{ session('success')}}
          </div>
      @endif
       
          <form action="{{ url('/import_plb') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class = "form-group">
            <label>Local Banks Cashflow</label><input type="file" name="file" class="form-control" required><br>
              @if (session('error'))
                  <div class="alert alert-danger">
                  {{ session('error')}}
                  </div>
              @endif
              <button class="btn btn-success">Upload Cashflow</button>
            </div>
          </form>  
          <form action="{{ url('/export_plb') }}" method="GET">
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

