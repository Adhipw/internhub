<?php

namespace App\Http\Controllers\Api;

use App\Models\Industry;
use App\Models\Location;
use Illuminate\Http\JsonResponse;

class ApiMasterDataController extends ApiBaseController
{
    /**
     * Get list of industries.
     */
    public function industries(): JsonResponse
    {
        $industries = Industry::orderBy('name')->get();

        return $this->sendResponse($industries, 'Industries retrieved successfully');
    }

    /**
     * Get list of locations (provinces/cities).
     */
    public function locations(): JsonResponse
    {
        $locations = Location::where('is_active', true)->orderBy('name')->get();

        return $this->sendResponse($locations, 'Locations retrieved successfully');
    }
}
