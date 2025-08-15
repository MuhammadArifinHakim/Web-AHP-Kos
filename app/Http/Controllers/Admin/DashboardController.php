<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\Campus;
use App\Models\Criteria;
use App\Models\QuestionnaireResponse;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        $stats = [
            'campuses' => Campus::count(),
            'boarding_houses' => BoardingHouse::count(),
            'criteria' => Criteria::count(),
            'questionnaire_responses' => QuestionnaireResponse::count()
        ];

        return view('admin.dashboard', compact('stats'));
    }
}