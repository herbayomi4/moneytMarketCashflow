<?php

namespace App\Http\Controllers;

use App\variables;
use App\InterestIncomeSub;
use App\InterestIncomeFgb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    { 
        return view('home');
    }

    public function variables()
    {
        $table = new variables();
        $variables = $table->orderBy('created_at', 'desc')->get()->first();
        if($variables){
            session(['usd'=>$variables->usd]);
            session(['gbp'=>$variables->gbp]);
            session(['reporting_date'=>$variables->reporting_date]);
        }
        
       
        return view('variables');
    }

    public function change(Request $request)
    {
        $table = new variables();

        $validatedData = $request->validate([
            'usd' => 'required|regex:/^[0-9]+(\.[0-9][0-9]?)?$/',
            'gbp' => 'required|regex:/^[0-9]+(\.[0-9][0-9]?)?$/',
            'reporting_date' => 'required|date_format:Y-m-d|before:today',
        ]);

        $table->create($validatedData);

        session(['usd'=>$request->usd]);
        session(['gbp'=>$request->gbp]);
        session(['reporting_date'=>$request->reporting_date]);
        
        return view('home');
    }

    public function rate()
    {
        $table = new variables();
        $rates = $table->orderBy('reporting_date', 'desc')->get();
        
        return view('rates', compact('rates'));
    }

    public function InterestIncomeSub()
    {
        $table = new InterestIncomeSub();
        $interest_incomes = $table->orderBy('reporting_date', 'asc')->get();     
        return view('interest_income', compact('interest_incomes'));
    }

    public function EditInterestIncome (Request $request)
    {
        if ($request->class == 'Subsidiary Placement') {
            $table = new InterestIncomeSub();
            $table->where('reporting_date',$request->reporting_date)->update(['gbp'=> $request->gbp, 'usd'=>$request->usd]);
            return redirect()->route('interestincome_sub');
        } 
        elseif ($request->class == 'Foreign Placement') {
            $table = new InterestIncomeFgb();
            $table->where('reporting_date',$request->reporting_date)->update(['gbp'=> $request->gbp, 'usd'=>$request->usd]);
            return redirect()->route('interestincome_fgb');
        }
         
    }
}
