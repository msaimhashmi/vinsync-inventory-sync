<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
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

        $sort = $request->input('sort', 'year_desc');

        // COALESCE picks sale_price, falls back to msrp if sale_price is null.
        // ISNULL() returns 1 for null rows, 0 for non-null — sorting by it first
        // pushes vehicles with no price to the bottom. MySQL doesn't support
        // the standard "NULLS LAST" syntax that PostgreSQL uses.
        if ($sort === 'price_asc') {
            $query->orderByRaw('ISNULL(COALESCE(sale_price, msrp)), COALESCE(sale_price, msrp) ASC');
        } elseif ($sort === 'price_desc') {
            $query->orderByRaw('ISNULL(COALESCE(sale_price, msrp)), COALESCE(sale_price, msrp) DESC');
        } elseif ($sort === 'year_asc') {
            $query->orderBy('year', 'asc');
        } elseif ($sort === 'mileage') {
            $query->orderBy('mileage', 'asc');
        } else {
            $query->orderBy('year', 'desc');
        }

        $vehicles = $query->paginate(24)->withQueryString();
        $options  = $this->dropdownOptions();
        $view     = in_array($request->input('view'), ['list', 'grid'])
            ? $request->input('view')
            : 'grid';

        return view('inventory.index', compact('vehicles', 'options', 'view'));
    }

    private function dropdownOptions(): array
    {
        // These queries are intentionally unscoped (no active filters applied).
        // If we scoped them, selecting "Toyota" would make every other make disappear
        // from the dropdown, confusing the user. Unscoped = all options always visible.
        return [
            'years'         => Vehicle::select('year')->distinct()->whereNotNull('year')->orderBy('year', 'desc')->pluck('year'),
            'makes'         => Vehicle::select('make')->distinct()->whereNotNull('make')->orderBy('make')->pluck('make'),
            'models'        => Vehicle::select('model')->distinct()->whereNotNull('model')->orderBy('model')->pluck('model'),
            'trims'         => Vehicle::select('trim')->distinct()->whereNotNull('trim')->orderBy('trim')->pluck('trim'),
            'body_styles'   => Vehicle::select('body_style')->distinct()->whereNotNull('body_style')->orderBy('body_style')->pluck('body_style'),
            'colors'        => Vehicle::select('exterior_color')->distinct()->whereNotNull('exterior_color')->orderBy('exterior_color')->pluck('exterior_color'),
            'transmissions' => Vehicle::select('transmission')->distinct()->whereNotNull('transmission')->orderBy('transmission')->pluck('transmission'),
            'fuel_types'    => Vehicle::select('fuel_type')->distinct()->whereNotNull('fuel_type')->orderBy('fuel_type')->pluck('fuel_type'),
            'drivetrains'   => Vehicle::select('drivetrain')->distinct()->whereNotNull('drivetrain')->orderBy('drivetrain')->pluck('drivetrain'),
            'engines'       => Vehicle::select('engine')->distinct()->whereNotNull('engine')->orderBy('engine')->pluck('engine'),
            'total'         => Vehicle::count(),
        ];
    }
}
