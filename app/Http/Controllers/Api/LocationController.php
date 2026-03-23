<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Get a list of countries.
     */
    public function getCountries()
    {
        // Simple static list of countries for now. 
        // In a real app, this could come from a database or external API.
        $countries = [
            'Nigeria', 'United States', 'United Kingdom', 'Canada', 'Ghana', 
            'South Africa', 'Kenya', 'Germany', 'France', 'India', 
            'Australia', 'China', 'Japan', 'Brazil', 'Egypt'
        ];

        return response()->json($countries);
    }
}
