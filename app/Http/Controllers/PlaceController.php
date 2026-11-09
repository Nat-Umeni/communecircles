<?php

namespace App\Http\Controllers;

use App\Exceptions\GeoNamesException;
use App\Services\GeoNames\GeoNamesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    /**
     * Towns and villages whose name starts with the search, for choosing a circle's location.
     */
    public function __invoke(Request $request, GeoNamesService $geoNames): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        try {
            $places = $geoNames->searchPlaces($validated['search']);
        } catch (GeoNamesException $exception) {
            report($exception);

            return response()->json(['message' => "Place search isn't available right now. Try again in a little while."], 503);
        }

        return response()->json(['places' => $places]);
    }
}
