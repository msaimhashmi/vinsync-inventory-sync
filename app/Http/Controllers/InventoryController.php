<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        // ---- Build the base query with all filters applied ----
        $query = Vehicle::query()
            ->search($request->input('search'))
            ->filterEngine($request->input('engine_filter'))
            ->filterCondition($request->input('condition'))
            ->filterYear($request->input('year'))
            ->filterMake($request->input('make'))
            ->filterModel($request->input('model'))
            ->filterTrim($request->input('trim'))
            ->filterBodyStyle($request->input('body_style'))
            ->filterExteriorColor($request->input('exterior_color'))
            ->filterTransmission($request->input('transmission'))
            ->filterFuelType($request->input('fuel_type'))
            ->filterDrivetrain($request->input('drivetrain'))
            ->filterPriceMin($request->input('price_min'))
            ->filterPriceMax($request->input('price_max'))
            ->filterMileageMax($request->input('mileage_max'));

        // ---- Sorting ----
        $sort  = $request->input('sort', 'year_desc');
        match ($sort) {
            'price_asc'  => $query->orderByRaw('COALESCE(sale_price, msrp) ASC NULLS LAST'),
            'price_desc' => $query->orderByRaw('COALESCE(sale_price, msrp) DESC NULLS LAST'),
            'year_asc'   => $query->orderBy('year', 'asc'),
            'mileage'    => $query->orderBy('mileage', 'asc'),
            default      => $query->orderBy('year', 'desc'),   // year_desc (default)
        };

        // ---- Paginate ----
        $vehicles = $query->paginate(24)->withQueryString();

        // ---- Distinct values for filter dropdowns (un-filtered base) ----
        $options = $this->dropdownOptions($request);

        // ---- View mode ----
        $view = in_array($request->input('view'), ['list', 'grid']) 
            ? $request->input('view') 
            : 'grid';

        return view('inventory.index', compact('vehicles', 'options', 'view'));
    }

    // -----------------------------------------------------------------------
    // Build dropdown option lists from CURRENT inventory (always up-to-date)
    // -----------------------------------------------------------------------
    private function dropdownOptions(Request $request): array
    {
        // We intentionally use un-scoped queries so all options are visible
        // even when the user has filtered to a small subset.
        return [
            'years'        => Vehicle::select('year')->distinct()
                                ->whereNotNull('year')
                                ->orderBy('year', 'desc')
                                ->pluck('year'),

            'makes'        => Vehicle::select('make')->distinct()
                                ->whereNotNull('make')
                                ->orderBy('make')
                                ->pluck('make'),

            'models'       => Vehicle::select('model')->distinct()
                                ->whereNotNull('model')
                                ->orderBy('model')
                                ->pluck('model'),

            'trims'        => Vehicle::select('trim')->distinct()
                                ->whereNotNull('trim')
                                ->orderBy('trim')
                                ->pluck('trim'),

            'body_styles'  => Vehicle::select('body_style')->distinct()
                                ->whereNotNull('body_style')
                                ->orderBy('body_style')
                                ->pluck('body_style'),

            'colors'       => Vehicle::select('exterior_color')->distinct()
                                ->whereNotNull('exterior_color')
                                ->orderBy('exterior_color')
                                ->pluck('exterior_color'),

            'transmissions' => Vehicle::select('transmission')->distinct()
                                ->whereNotNull('transmission')
                                ->orderBy('transmission')
                                ->pluck('transmission'),

            'fuel_types'   => Vehicle::select('fuel_type')->distinct()
                                ->whereNotNull('fuel_type')
                                ->orderBy('fuel_type')
                                ->pluck('fuel_type'),

            'drivetrains'  => Vehicle::select('drivetrain')->distinct()
                                ->whereNotNull('drivetrain')
                                ->orderBy('drivetrain')
                                ->pluck('drivetrain'),

            'engines'      => Vehicle::select('engine')->distinct()
                                ->whereNotNull('engine')
                                ->orderBy('engine')
                                ->pluck('engine'),

            'total'        => Vehicle::count(),
        ];
    }
}
