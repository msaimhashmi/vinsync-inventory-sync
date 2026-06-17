<?php

namespace App\Services;

use App\Models\Vehicle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VehicleSyncService
{
    // The API endpoint is a private JSON API used by the dealership's own website.
    // 5089 = dealer group ID, 2908332 = store/location ID.
    private const BASE_API  = 'https://www.classicarlington.com/api/vhcliaa/vehicle-pages/cosmos/srp/vehicles/5089/2908332';
    private const BASE_HOST = 'https://www.classicarlington.com';

    // The API requires a base64-encoded filter string to separate new vs used inventory.
    // FILTER_NEW  decodes to: type='n'
    // FILTER_USED decodes to: type='u'
    private const FILTER_NEW  = 'dHlwZT0nbic=';
    private const FILTER_USED = 'dHlwZSA9ICd1Jw=';
//    private const FILTER_USED = 'dHlwZT0ndSc=';

    private const TIMEOUT = 45;

    // The API always returns 12 vehicles per page regardless of what you request.
    // Sending pn=96 makes no difference — 12 is the server-side hard cap.
    private const PAGE_SIZE = 12;

    /**
     * Fetch all new and used vehicles, upsert into DB, prune removed ones.
     *
     * @param  int|null $limit  Cap total vehicles (for testing). Null = full sync.
     * @return array{fetched: int, inserted: int, updated: int, deleted: int, errors: string[]}
     */
    public function sync(?int $limit = null): array
    {
        $errors  = [];
        $fetched = 0;
        $allVins = [];
        $allData = [];

        // The API splits inventory into two separate filters: new and used.
        // Both must be fetched and merged to get the complete inventory (~320 total).
        foreach (['new' => self::FILTER_NEW, 'used' => self::FILTER_USED] as $type => $filter) {

            $remaining = $limit !== null ? ($limit - $fetched) : null;

            if ($remaining !== null && $remaining <= 0) {
                break;
            }

            try {
                [$vehicles, $pageErrors] = $this->fetchAllPages($filter, $type, $remaining);
                $errors = array_merge($errors, $pageErrors);

                foreach ($vehicles as $v) {
                    // The API mixes real vehicle cards with ad/promo cards.
                    // Ad cards have IsAdCard=true and no VehicleCard key — skip them.
                    if (!isset($v['VehicleCard']) || !is_array($v['VehicleCard'])) {
                        continue;
                    }

                    $parsed = $this->parseVehicle($v['VehicleCard']);
                    if ($parsed && $parsed['vin']) {
                        $allVins[] = $parsed['vin'];
                        $allData[] = $parsed;
                    }
                }

                $fetched += count($vehicles);

            } catch (\Throwable $e) {
                $msg = "VehicleSync [{$type}] fatal: " . $e->getMessage();
                Log::error($msg, ['exception' => $e]);
                $errors[] = $msg;
            }
        }

        // If every single API call failed, keep the existing DB untouched.
        // Better to show stale inventory than an empty page.
        if ($fetched === 0 && count($errors) > 0) {
            Log::warning('VehicleSync: all API calls failed – preserving existing inventory.');
            return ['fetched' => 0, 'inserted' => 0, 'updated' => 0, 'deleted' => 0, 'errors' => $errors];
        }

        $inserted = 0;
        $updated  = 0;
        foreach ($allData as $data) {
            // VIN is the unique identifier for every vehicle. updateOrCreate matches
            // on VIN so existing records get updated rather than duplicated.
            $vehicle = Vehicle::updateOrCreate(['vin' => $data['vin']], $data);
            $vehicle->wasRecentlyCreated ? $inserted++ : $updated++;
        }

        $deleted = 0;
        if ($limit === null && count($allVins) > 0) {
            // Any VIN in our DB that the API no longer returns means the car was sold.
            // Delete those records to keep our inventory in sync.
            $deleted = Vehicle::whereNotIn('vin', $allVins)->delete();
        }
        // Note: prune is intentionally skipped when --limit is active because a
        // limited fetch doesn't collect all VINs, so we'd wrongly delete real vehicles.

        Log::info("VehicleSync complete: fetched={$fetched}, inserted={$inserted}, updated={$updated}, deleted={$deleted}");

        return compact('fetched', 'inserted', 'updated', 'deleted', 'errors');
    }

    private function fetchAllPages(string $baseFilter, string $type, ?int $remaining): array
    {
        $collected  = [];
        $pageErrors = [];
        $page       = 1;
        $totalPages = null;

        while (true) {

            if ($remaining !== null && count($collected) >= $remaining) {
                Log::info("VehicleSync [{$type}]: limit reached after " . count($collected) . " vehicles.");
                break;
            }

            Log::info("VehicleSync [{$type}]: fetching page {$page}" . ($totalPages ? " of {$totalPages}" : '') . '…');

            try {
                [$pageVehicles, $pagingMeta] = $this->fetchPage($baseFilter, $page);
            } catch (\Throwable $e) {
                $msg = "VehicleSync [{$type}] page {$page} failed: " . $e->getMessage();
                Log::error($msg, ['exception' => $e]);
                $pageErrors[] = $msg;
                break;
            }

            if ($totalPages === null) {
                $totalPages = $pagingMeta['total_pages'] ?? null;
                $totalCount = $pagingMeta['total_count'] ?? '?';
                Log::info("VehicleSync [{$type}]: {$totalCount} vehicles across " . ($totalPages ?? '?') . " pages.");
            }

            $pageCount = count($pageVehicles);

            if ($pageCount === 0) {
                break;
            }

            foreach ($pageVehicles as $v) {
                if ($remaining !== null && count($collected) >= $remaining) {
                    break;
                }
                $collected[] = $v;
            }

            Log::info("VehicleSync [{$type}]: page {$page} — got {$pageCount} vehicles.");

            if ($totalPages !== null && $page >= $totalPages) {
                Log::info("VehicleSync [{$type}]: done.");
                break;
            }

            // Fallback when the API doesn't return TotalPages: a partial page means we're on the last one.
            if ($totalPages === null && $pageCount < self::PAGE_SIZE) {
                break;
            }

            $page++;
        }

        return [$collected, $pageErrors];
    }

    private function fetchPage(string $baseFilter, int $page): array
    {
        $response = Http::timeout(self::TIMEOUT)
            ->withHeaders([
                // The API is a private endpoint used by the dealership website itself.
                // It checks the Referer and User-Agent headers — without them the request gets blocked.
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept'     => 'application/json, text/plain, */*',
                'Referer'    => 'https://www.classicarlington.com/',
            ])
            ->get(self::BASE_API, [
                'host'       => 'www.classicarlington.com',
                'baseFilter' => $baseFilter,
                // WARNING: the param names here are counterintuitive.
                // 'pn' looks like "page number" but it is actually PAGE SIZE.
                // 'pt' looks like nothing obvious but it is actually PAGE NUMBER (1-based).
                // This was confirmed by reading the dealership's minified JS bundle.
                // Do not swap these — the API silently ignores unknown params and returns page 1 only.
                'pn' => self::PAGE_SIZE,
                'pt' => $page,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException("HTTP {$response->status()} on page {$page}");
        }

        $body = $response->json();

        if (is_null($body)) {
            throw new \RuntimeException("Non-JSON response on page {$page}");
        }

        $vehicles = $this->extractVehicleList($body, $baseFilter, $page);

        $pagingRaw  = $body['Paging']['PaginationDataModel'] ?? [];
        $pagingMeta = [
            'total_count' => isset($pagingRaw['TotalCount']) ? (int) $pagingRaw['TotalCount'] : null,
            'total_pages' => isset($pagingRaw['TotalPages']) ? (int) $pagingRaw['TotalPages'] : null,
            'page_size'   => isset($pagingRaw['PageSize'])   ? (int) $pagingRaw['PageSize']   : null,
            'page_start'  => isset($pagingRaw['PageStart'])  ? (int) $pagingRaw['PageStart']  : null,
            'page_end'    => isset($pagingRaw['PageEnd'])    ? (int) $pagingRaw['PageEnd']    : null,
        ];

        return [$vehicles, $pagingMeta];
    }

    private function extractVehicleList(array $body, string $baseFilter, int $page): array
    {
        if (isset($body['DisplayCards']) && is_array($body['DisplayCards'])) {
            return $body['DisplayCards'];
        }

        if (isset($body['vehicles']) && is_array($body['vehicles'])) {
            return $body['vehicles'];
        }

        if (array_is_list($body)) {
            return $body;
        }

        foreach (['Vehicles', 'VehicleList', 'data', 'items', 'results', 'Records'] as $key) {
            if (isset($body[$key]) && is_array($body[$key])) {
                return $body[$key];
            }
        }

        Log::warning("VehicleSync: unexpected API structure on page {$page}", [
            'top_level_keys' => array_keys($body),
        ]);

        return [];
    }

    private function parseVehicle(array $v): ?array
    {
        $vin = trim($v['VehicleVin'] ?? '');
        if (!$vin) {
            return null;
        }

        return [
            'vin'                 => $vin,
            'stock_number'        => $v['VehicleStockNumber'] ?? null,
            'condition'           => strtolower($v['VehicleType'] ?? 'used'),
            'make'                => $v['VehicleMake'] ?? null,
            'model'               => $v['VehicleModel'] ?? null,
            'year'                => isset($v['VehicleYear']) ? (int) $v['VehicleYear'] : null,
            'trim'                => $v['VehicleTrim'] ?? null,
            'body_style'          => $v['VehicleBodyStyle'] ?? null,
            'engine'              => $v['VehicleEngine'] ?? null,
            'transmission'        => $v['VehicleTransmission'] ?? null,
            'drivetrain'          => $v['VehicleDriveTrain'] ?? null,
            'fuel_type'           => $v['VehicleFuelType'] ?? null,
            'mpg_city'            => isset($v['VehicleMpgCity']) ? (int) $v['VehicleMpgCity'] : null,
            'mpg_hwy'             => isset($v['VehicleMpgHwy']) ? (int) $v['VehicleMpgHwy'] : null,
            'msrp'                => isset($v['VehicleMsrp']) ? (float) $v['VehicleMsrp'] : null,
            'sale_price'          => $this->extractSalePrice($v),
            'mileage'             => $this->parseMileage($v['Mileage'] ?? null),
            'exterior_color'      => $v['ExteriorColorLabel'] ?? null,
            'exterior_color_code' => $v['ExteriorColorCode'] ?? null,
            'interior_color'      => $v['InteriorColorLabel'] ?? null,
            'interior_color_code' => $v['InteriorColorCode'] ?? null,
            'status'              => $v['VehicleStatusModel']['StatusText'] ?? null,
            'dealer_name'         => $v['DealerName'] ?? null,
            'dealer_city'         => $v['DealerLocatedAtCity'] ?? null,
            'dealer_state'        => $v['DealerLocatedAtState'] ?? null,
            'detail_url'          => $v['VehicleDetailUrl'] ?? null,
            'features'            => $v['VehicleHighlightsModel']['Highlights'] ?? [],
            'images'              => $this->extractImages($v),
            'window_sticker_url'  => $this->extractWindowSticker($v),
        ];
    }

    private function parseMileage(?string $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        // API returns mileage as a formatted string e.g. "85,234 mi" — strip everything except digits.
        preg_match('/[\d,]+/', $raw, $m);
        if (!isset($m[0])) {
            return null;
        }
        return (int) str_replace(',', '', $m[0]);
    }

    private function extractSalePrice(array $v): ?float
    {
        // The API embeds an HTML pricing panel inside the JSON response.
        // The actual sale price is inside that HTML, labeled "CLASSIC PRICE".
        $html = $v['WasabiVehiclePricingPanelViewModel']['PriceStakViewModel']['PriceStakTabsModel']['BuyContent'] ?? null;

        if (!$html) {
            return null;
        }

        if (preg_match(
            '/CLASSIC PRICE.*?<span class="vehiclePricingHighlightAmount ">\$([\d,]+)<\/span>/s',
            $html,
            $m
        )) {
            return (float) str_replace(',', '', $m[1]);
        }

        return null;
    }

    private function extractImages(array $v): array
    {
        // PhotoList is an array of relative URL strings e.g. "/inventoryphotos/19099/.../1.jpg".
        // Prepend BASE_HOST to build the full URL.
        $photoList = $v['VehicleImageModel']['VehicleImageCarouselModel']['PhotoList'] ?? [];
        $urls = [];
        foreach ($photoList as $src) {
            if ($src) {
                $urls[] = self::BASE_HOST . '/' . ltrim($src, '/');
            }
        }
        return $urls;
    }

    private function extractWindowSticker(array $v): ?string
    {
        // Window sticker URL is buried inside a JS click event string e.g.:
        // "OpenWindowSticker('/stickers/xyz.pdf', ...)"
        $clickEvent = $v['VehicleFeaturesModel']['WindowStickersModel']['ClickEvent'] ?? null;
        if (!$clickEvent) {
            return null;
        }
        if (preg_match("/OpenWindowSticker\('([^']+)'/", $clickEvent, $m)) {
            return self::BASE_HOST . $m[1];
        }
        return null;
    }
}
