<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //
    public function index()
    {
        $gaugeData = [
            [
                "value" => 20,
                "name" => "Good",
                "title" => ["offsetCenter" => ["-40%", "80%"]],
                "detail" => ["offsetCenter" => ["-40%", "95%"]]
            ],
            [
                "value" => 40,
                "name" => "Better",
                "title" => ["offsetCenter" => ["0%", "80%"]],
                "detail" => ["offsetCenter" => ["0%", "95%"]]
            ],
            [
                "value" => 60,
                "name" => "Perfect",
                "title" => ["offsetCenter" => ["40%", "80%"]],
                "detail" => ["offsetCenter" => ["40%", "95%"]]
            ]
        ];

        $data = [
            'gaugeData' => $gaugeData,
        ];

        return view('dashboard.index', compact('data'));
    }
}
