@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">Rates Hitory</div>

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
                    @if(count($rates)>0)
                    <p>The table below shows rate history.</p>
                        <table class="table table-striped table-sm">
                            <thead class="thead-dark">
                              <tr>
                                <th scope="col">S/N</th>
                                <th scope="col">Date</th>
                                <th scope="col">GBP</th>
                                <th scope="col">USD</th>
                              </tr>
                            </thead>
                            <tbody>
                            @php
                                $sn = 0;
                            @endphp
                            @foreach($rates as $rate)
                            @php
                                $sn++;
                            @endphp
                              <tr>
                                <th scope="row"><?php echo $sn;?></th>
                                <td>{{date('d-M-Y', strtotime($rate->reporting_date))}}</td>
                                <td>{{number_format($rate->gbp,4)}}</td>
                                <td>{{number_format($rate->usd,4)}}</td>
                              </tr>
                            @endforeach
                            </tbody>
                          </table>
                     
                    @else
                          <p class="alert alert-danger">No rate history yet.</p>
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