@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">

                <div class="row justify-content-center">
                    <div class = "col-md-6 form-group">
                        <a class="btn btn-info" href = "{{ route('pcbn') }}">Placement with C.B.N</a><br><br>
                        <a class="btn btn-info" href = "{{ route('plb') }}">Placement with Local Banks</a><br><br>
                        <a class="btn btn-info" href = "{{ route('pfb') }}">Placement with Foreign Banks</a><br><br>
                        <a class="btn btn-info" href = "{{ route('psub') }}">Placement with Subsidiary</a>
                    </div>
                    <div class = "col-md-6 form-group">
                        <a class="btn btn-success" href = "{{ route('tcbn') }}">Takings from C.B.N</a><br><br>
                        <a class="btn btn-success" href = "{{ route('tlb') }}">Takings from Local Banks</a><br><br>
                        <a class="btn btn-success" href = "{{ route('tsub') }}">Takings from Subsidiary</a>
                    </div>
                </div>

                        
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