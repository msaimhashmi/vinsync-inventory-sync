<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\VehicleSyncService;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    // Simple token guard — set SYNC_TOKEN in .env
    private function authorise(Request $request): void
    {
        $token = config('app.sync_token');
        if ($token && $request->query('token') !== $token) {
            abort(403, 'Invalid token.');
        }
    }

    /**
     * Run a full sync and return a JSON summary.
     * Used for the client demo and manual triggers.
     * URL: /sync-now?token=YOUR_SYNC_TOKEN
     */
    public function run(Request $request, VehicleSyncService $service)
    {
        $this->authorise($request);

        $before = Vehicle::count();
        $start  = microtime(true);

        try {
            $result = $service->sync();
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'status'          => 'ok',
            'time_seconds'    => round(microtime(true) - $start, 2),
            'vehicles_before' => $before,
            'vehicles_after'  => Vehicle::count(),
            'inserted'        => $result['inserted'],
            'updated'         => $result['updated'],
            'deleted'         => $result['deleted'],
            'errors'          => $result['errors'],
        ]);
    }

    /**
     * Set up the database in a state that demonstrates all three sync operations.
     * Run this BEFORE /sync-now to make the demo meaningful.
     * URL: /demo-setup?token=YOUR_SYNC_TOKEN
     *
     * What it does:
     *   - Inserts 3 fake VINs  → sync will DELETE them (not in API)
     *   - Deletes 3 real VINs  → sync will INSERT them back
     *   - Corrupts 3 prices    → sync will UPDATE them back to correct values
     */
    public function demoSetup(Request $request)
    {
        $this->authorise($request);

        // 1. Insert fake VINs — sync will delete these because the API doesn't know them
        $fakeVins = ['DEMO-FAKE-VIN-001', 'DEMO-FAKE-VIN-002', 'DEMO-FAKE-VIN-003'];
        foreach ($fakeVins as $vin) {
            Vehicle::updateOrCreate(['vin' => $vin], [
                'make'      => 'DEMO',
                'model'     => 'Fake Vehicle',
                'year'      => 2024,
                'condition' => 'used',
            ]);
        }

        // 2. Delete 3 real vehicles — sync will re-insert them
        $deletedVins = Vehicle::whereNotIn('vin', $fakeVins)
            ->inRandomOrder()
            ->limit(3)
            ->pluck('vin')
            ->toArray();
        Vehicle::whereIn('vin', $deletedVins)->delete();

        // 3. Corrupt the sale_price on 3 real vehicles — sync will correct them
        $corruptedVins = Vehicle::whereNotIn('vin', array_merge($fakeVins, $deletedVins))
            ->whereNotNull('sale_price')
            ->inRandomOrder()
            ->limit(3)
            ->pluck('vin')
            ->toArray();
        Vehicle::whereIn('vin', $corruptedVins)->update(['sale_price' => 999999.00]);

        return response()->json([
            'status'  => 'demo ready',
            'message' => 'Now hit /sync-now?token=... to see all three operations.',
            'setup'   => [
                'fake_vins_inserted' => $fakeVins,
                'real_vins_deleted'  => $deletedVins,
                'prices_corrupted'   => $corruptedVins,
            ],
        ]);
    }
}
