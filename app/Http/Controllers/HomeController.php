<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\Criteria;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $campuses = Campus::all();
        $criteria = Criteria::orderBy('order')->get();
        
        return view('home', compact('campuses', 'criteria'));
    }

    public function selectCampus(Request $request)
    {
        $campusId = $request->input('campus_id');
        $weightMethod = $request->input('weight_method');
        
        $campus = Campus::findOrFail($campusId);
        $boardingHouses = $campus->boardingHouses;
        
        return view('boarding-houses', compact('campus', 'boardingHouses', 'weightMethod'));
    }
}