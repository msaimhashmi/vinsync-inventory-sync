<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory | Julio Tu Car Guy</title>
    <meta name="description" content="Browse our full inventory of new and used vehicles.">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

{{-- ══════════════════════════════════════════════
     TOP NAVIGATION
══════════════════════════════════════════════ --}}
<nav class="top-nav">
    <div class="nav-container">

        {{-- Mobile brand --}}
        <a href="/" class="nav-brand">Julio Tu Car Guy</a>

        {{-- Hamburger --}}
        <button class="nav-toggler" id="navToggler" aria-label="Toggle navigation">
            <i class="bi bi-list"></i>
        </button>

        {{-- Collapsible wrapper on mobile --}}
        <div class="nav-collapse-wrapper" id="navCollapse">
            <div class="nav-links" id="navLinks">
                <a href="#">Home</a>
                <a href="#">About Us</a>
                <a href="{{ route('inventory.index') }}" class="active">Inventory</a>
                <a href="#" class="has-caret">Financial Services</a>
                <a href="#">Contact Us</a>
            </div>
            <div class="nav-ctas" id="navCtas">
                <a href="#" class="btn-gradient" style="font-size:12px;padding:7px 16px;line-height:1.3;border-radius:6px;">
                    Apply<br>Now
                </a>
                <a href="#" class="btn-gradient">
                    <i class="bi bi-geo-alt-fill"></i>Visit<br>Us
                </a>
            </div>
        </div>

    </div>
</nav>

{{-- ══════════════════════════════════════════════
     PAGE BANNER
══════════════════════════════════════════════ --}}
<div class="page-banner">
    <h1>Inventory</h1>
</div>

{{-- ══════════════════════════════════════════════
     SEARCH BAR
══════════════════════════════════════════════ --}}
<div class="search-section">
    <div class="search-inner">
        <form method="GET" action="{{ route('inventory.index') }}" id="searchForm">
            {{-- Preserve non-search filters across search submits --}}
            @foreach(request()->except(['search','page']) as $k => $v)
                @if(is_array($v))
                    @foreach($v as $vv)<input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">@endforeach
                @else
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endif
            @endforeach
            <div class="input-group">
                <input type="text" name="search" class="form-control"
                       placeholder="Search by make, model, year, VIN, stock..."
                       value="{{ request('search') }}">
                <button type="submit" class="btn-gradient btn-search-submit">Search</button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     MAIN LAYOUT
══════════════════════════════════════════════ --}}
<div class="inventory-wrapper">

    {{-- Mobile: toggle filter sidebar --}}
    <button class="btn-gradient filter-toggle-btn" id="filterToggle">
        <i class="bi bi-funnel-fill me-2"></i> Filter Vehicles
        <i class="bi bi-chevron-down ms-auto" id="filterChevron"></i>
    </button>

    <div class="inventory-body">

        {{-- ══ LEFT SIDEBAR ══ --}}
        <aside class="filter-sidebar" id="filterSidebar">
            <form method="GET" action="{{ route('inventory.index') }}" id="filterForm">
                {{-- Preserve sort & view & search across filter changes --}}
                @foreach(['sort','view','search'] as $pk)
                    @if(request($pk))
                        <input type="hidden" name="{{ $pk }}" value="{{ request($pk) }}">
                    @endif
                @endforeach

                {{-- Year --}}
                <div class="filter-group">
                    <label>Year</label>
                    <select name="year" onchange="document.getElementById('filterForm').submit()">
                        <option value="">Any Year</option>
                        @foreach($options['years'] as $y)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Make --}}
                <div class="filter-group">
                    <label>Make</label>
                    <select name="make" onchange="document.getElementById('filterForm').submit()">
                        <option value="">Any Make</option>
                        @foreach($options['makes'] as $m)
                            <option value="{{ $m }}" {{ request('make') === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Model --}}
                <div class="filter-group">
                    <label>Model</label>
                    <select name="model" onchange="document.getElementById('filterForm').submit()">
                        <option value="">Any Model</option>
                        @foreach($options['models'] as $m)
                            <option value="{{ $m }}" {{ request('model') === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Body Style --}}
                <div class="filter-group">
                    <label>Body Style</label>
                    <select name="body_style" onchange="document.getElementById('filterForm').submit()">
                        <option value="">Any Body Style</option>
                        @foreach($options['body_styles'] as $b)
                            <option value="{{ $b }}" {{ request('body_style') === $b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Engine --}}
                <div class="filter-group">
                    <label>Engine</label>
                    <select name="engine_filter" onchange="document.getElementById('filterForm').submit()">
                        <option value="">Any Engine</option>
                        @foreach($options['engines'] ?? [] as $e)
                            <option value="{{ $e }}" {{ request('engine_filter') === $e ? 'selected' : '' }}>{{ $e }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Transmission --}}
                <div class="filter-group">
                    <label>Transmission</label>
                    <select name="transmission" onchange="document.getElementById('filterForm').submit()">
                        <option value="">Any Transmission</option>
                        @foreach($options['transmissions'] as $t)
                            <option value="{{ $t }}" {{ request('transmission') === $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Fuel Type --}}
                <div class="filter-group">
                    <label>Fuel Type</label>
                    <select name="fuel_type" onchange="document.getElementById('filterForm').submit()">
                        <option value="">Any Fuel Type</option>
                        @foreach($options['fuel_types'] as $f)
                            <option value="{{ $f }}" {{ request('fuel_type') === $f ? 'selected' : '' }}>{{ $f }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Condition --}}
                <div class="filter-group">
                    <label>Condition</label>
                    <select name="condition" onchange="document.getElementById('filterForm').submit()">
                        <option value="">Any Condition</option>
                        <option value="new"  {{ request('condition') === 'new'  ? 'selected' : '' }}>New</option>
                        <option value="used" {{ request('condition') === 'used' ? 'selected' : '' }}>Used</option>
                    </select>
                </div>

                {{-- Max Price --}}
                <div class="filter-group">
                    <label>Max Price</label>
                    <select name="price_max" onchange="document.getElementById('filterForm').submit()">
                        <option value="">Any Price</option>
                        @foreach([15000,20000,25000,30000,35000,40000,50000,60000,75000,100000] as $p)
                            <option value="{{ $p }}" {{ request('price_max') == $p ? 'selected' : '' }}>
                                ${{ number_format($p) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Max Mileage --}}
                <div class="filter-group">
                    <label>Max Mileage</label>
                    <select name="mileage_max" onchange="document.getElementById('filterForm').submit()">
                        <option value="">Any Mileage</option>
                        @foreach([5000,10000,15000,25000,50000,75000,100000,150000] as $mi)
                            <option value="{{ $mi }}" {{ request('mileage_max') == $mi ? 'selected' : '' }}>
                                {{ number_format($mi) }} mi
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Reset --}}
                <a href="{{ route('inventory.index') }}" class="btn-gradient btn-reset-filters">
                    Reset All Filters
                </a>
            </form>
        </aside>

        {{-- ══ RIGHT CONTENT ══ --}}
        <div class="inventory-content">

            {{-- Active filter chips --}}
            @php
                $chips = array_filter([
                    'condition'      => request('condition'),
                    'year'           => request('year'),
                    'make'           => request('make'),
                    'model'          => request('model'),
                    'body_style'     => request('body_style') ? request('body_style') : null,
                    'fuel_type'      => request('fuel_type'),
                    'transmission'   => request('transmission'),
                    'engine_filter'  => request('engine_filter'),
                    'price_max'      => request('price_max') ? '$'.number_format(request('price_max')).' max' : null,
                    'mileage_max'    => request('mileage_max') ? number_format(request('mileage_max')).' mi max' : null,
                    'search'         => request('search') ? '"'.request('search').'"' : null,
                ]);
            @endphp
            @if(count($chips))
                <div class="active-chips">
                    <span class="chip-label">Active:</span>
                    @foreach($chips as $key => $label)
                        <a href="{{ route('inventory.index', request()->except([$key,'page'])) }}"
                           class="filter-chip">
                            {{ $label }}
                            <i class="bi bi-x"></i>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- ── TOOLBAR ── --}}
            <div class="results-toolbar">
                <div class="count-text">
                    @if($vehicles->total() > 0)
                        Showing <strong>{{ $vehicles->firstItem() }}–{{ $vehicles->lastItem() }}</strong>
                        of <strong>{{ $vehicles->total() }}</strong> vehicles
                    @else
                        No vehicles found
                    @endif
                </div>

                <div class="toolbar-right">

                    {{-- Grid / List toggle --}}
                    <div class="view-toggle" role="group" aria-label="View mode">
                        <a href="{{ route('inventory.index', array_merge(request()->except(['view','page']), ['view'=>'grid'])) }}"
                           class="view-btn {{ $view === 'grid' ? 'active' : '' }}"
                           title="Grid view">
                            <i class="bi bi-grid-3x3-gap-fill"></i>
                        </a>
                        <a href="{{ route('inventory.index', array_merge(request()->except(['view','page']), ['view'=>'list'])) }}"
                           class="view-btn {{ $view === 'list' ? 'active' : '' }}"
                           title="List view">
                            <i class="bi bi-list-ul"></i>
                        </a>
                    </div>

                    {{-- Sort --}}
                    <form method="GET" action="{{ route('inventory.index') }}" class="sort-form" id="sortForm">
                        @foreach(request()->except(['sort','page']) as $k => $v)
                            @if(is_array($v))
                                @foreach($v as $vv)<input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">@endforeach
                            @else
                                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                            @endif
                        @endforeach
                        <label>Sort by:</label>
                        <select name="sort" onchange="document.getElementById('sortForm').submit()">
                            <option value="year_desc"  {{ request('sort','year_desc') === 'year_desc'  ? 'selected':'' }}>Newly Listed</option>
                            <option value="year_asc"   {{ request('sort') === 'year_asc'   ? 'selected':'' }}>Oldest First</option>
                            <option value="price_asc"  {{ request('sort') === 'price_asc'  ? 'selected':'' }}>Price: Low to High</option>
                            <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected':'' }}>Price: High to Low</option>
                            <option value="mileage"    {{ request('sort') === 'mileage'    ? 'selected':'' }}>Lowest Mileage</option>
                        </select>
                    </form>

                </div>
            </div>
            {{-- /toolbar --}}

            {{-- ════════════════════════════════════
                 VEHICLE LISTING
            ════════════════════════════════════ --}}
            @if($vehicles->isEmpty())

                <div class="empty-state">
                    <i class="bi bi-car-front"></i>
                    <h5>No vehicles found</h5>
                    <p>Try widening your search or resetting the filters.</p>
                    <a href="{{ route('inventory.index') }}" class="btn-gradient" style="padding:10px 24px;font-size:14px;border-radius:5px;margin-top:14px;display:inline-flex;">
                        Clear All Filters
                    </a>
                </div>

            @elseif($view === 'list')

                {{-- ───── LIST VIEW ───── --}}
                <div class="vehicles-list">
                    @foreach($vehicles as $vehicle)
                        <div class="v-row">

                            {{-- Thumbnail --}}
                            <div class="row-img-wrap">
                                <img src="{{ $vehicle->primary_image }}"
                                     alt="{{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}"
                                     loading="lazy">
                                <span class="cond-pill {{ $vehicle->condition === 'used' ? 'used' : '' }}">
                                    {{ strtoupper($vehicle->condition) }}
                                </span>
                            </div>

                            {{-- Details --}}
                            <div class="row-body">
                                <div class="v-title">
                                    {{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}
                                    @if($vehicle->trim) &mdash; {{ $vehicle->trim }} @endif
                                </div>
                                <div class="v-meta">
                                    {{ $vehicle->year }}
                                    @if($vehicle->mileage !== null)
                                        <span class="bull">{{ number_format($vehicle->mileage) }} miles</span>
                                    @endif
                                    @if($vehicle->stock_number)
                                        <span class="bull">Stock #{{ $vehicle->stock_number }}</span>
                                    @endif
                                    @if($vehicle->vin)
                                        <span class="bull">VIN {{ substr($vehicle->vin, 0, 11) }}...</span>
                                    @endif
                                </div>
                                <div class="row-specs">
                                    @if($vehicle->engine)
                                        <span><i class="bi bi-cpu"></i>{{ $vehicle->engine }}</span>
                                    @endif
                                    @if($vehicle->transmission)
                                        <span><i class="bi bi-gear"></i>{{ $vehicle->transmission }}</span>
                                    @endif
                                    @if($vehicle->exterior_color)
                                        <span><i class="bi bi-palette"></i>{{ $vehicle->exterior_color }}</span>
                                    @endif
                                    @if($vehicle->mpg_city)
                                        <span><i class="bi bi-fuel-pump"></i>{{ $vehicle->mpg_city }}/{{ $vehicle->mpg_hwy }} MPG</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Price + buttons --}}
                            <div class="row-price-col">
                                <div class="v-price">
                                    @if($vehicle->display_price)
                                        ${{ number_format($vehicle->display_price) }}
                                    @else
                                        <span style="font-size:14px;color:#888;">Call for Price</span>
                                    @endif
                                </div>
                                <div class="v-price-note">Plus taxes, tags &amp; fees</div>
                                <div class="row-btns">
                                    <a href="{{ $vehicle->detail_url ?? '#' }}"
                                       @if($vehicle->detail_url) target="_blank" rel="noopener" @endif
                                       class="btn-outline-blue">View Details</a>
                                    <a href="#" class="btn-gradient">Apply Now</a>
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>

            @else

                {{-- ───── GRID VIEW (default) ───── --}}
                <div class="vehicles-grid">
                    @foreach($vehicles as $vehicle)
                        <div class="v-card">

                            {{-- Image --}}
                            <div class="img-wrap">
                                <img src="{{ $vehicle->primary_image }}"
                                     alt="{{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}"
                                     loading="lazy">
                                <span class="cond-pill {{ $vehicle->condition === 'used' ? 'used' : '' }}">
                                    {{ strtoupper($vehicle->condition) }}
                                </span>
                            </div>

                            {{-- Body --}}
                            <div class="card-body-wrap">
                                <div class="v-title">
                                    {{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}
                                </div>
                                <div class="v-meta">
                                    {{ $vehicle->year }}
                                    @if($vehicle->mileage !== null)
                                        <span class="bull">{{ number_format($vehicle->mileage) }} miles</span>
                                    @endif
                                    @if($vehicle->stock_number)
                                        <span class="bull">Stock #{{ $vehicle->stock_number }}</span>
                                    @endif
                                    @if($vehicle->vin)
                                        <br>VIN {{ substr($vehicle->vin, 0, 10) }}...
                                    @endif
                                </div>
                                <div class="v-price">
                                    @if($vehicle->display_price)
                                        ${{ number_format($vehicle->display_price) }}
                                    @else
                                        <span style="font-size:13px;color:#888;font-weight:600;">Call for Price</span>
                                    @endif
                                </div>
                                <div class="v-price-note">Plus taxes, tags &amp; fees</div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="card-actions">
                                <a href="{{ $vehicle->detail_url ?? '#' }}"
                                   @if($vehicle->detail_url) target="_blank" rel="noopener" @endif
                                   class="btn-outline-blue">View Details</a>
                                <a href="#" class="btn-gradient">Apply Now</a>
                            </div>

                        </div>
                    @endforeach
                </div>

            @endif
            {{-- /listing --}}

            {{-- Pagination --}}
            @if($vehicles->hasPages())
                <div class="pagination-wrap">
                    {{ $vehicles->links('pagination::bootstrap-5') }}
                </div>
            @endif

        </div>{{-- /inventory-content --}}
    </div>{{-- /inventory-body --}}
</div>{{-- /inventory-wrapper --}}

{{-- ══════════════════════════════════════════════
     FOOTER CTA STRIP
══════════════════════════════════════════════ --}}
<div class="footer-cta">
    <div class="cta-text">Have a question? Feel free to ask</div>
    <div class="cta-icons">
        <a href="tel:" title="Call us"><i class="bi bi-telephone-fill"></i></a>
        <a href="#"   title="Location"><i class="bi bi-geo-alt-fill"></i></a>
        <a href="#"   title="Email"><i class="bi bi-envelope-fill"></i></a>
        <a href="#"   title="Facebook"><i class="bi bi-facebook"></i></a>
        <a href="#"   title="Instagram"><i class="bi bi-instagram"></i></a>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     SITE FOOTER
══════════════════════════════════════════════ --}}
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">

            <div class="col-12 col-md-4 col-lg-3">
                <p class="footer-brand-text">
                    At Julio Tu Car Guy, we believe buying a car shouldn't be
                    complicated, overpriced, or stressful.
                </p>
            </div>

            <div class="col-6 col-md-3 col-lg-2 offset-lg-1">
                <h6>Vehicles</h6>
                <ul>
                    <li><a href="{{ route('inventory.index', ['body_style' => 'SUV']) }}">SUVs</a></li>
                    <li><a href="{{ route('inventory.index', ['body_style' => 'Truck']) }}">Trucks</a></li>
                    <li><a href="{{ route('inventory.index', ['body_style' => 'Crossover']) }}">Crossovers</a></li>
                    <li><a href="{{ route('inventory.index', ['fuel_type' => 'Hybrid']) }}">Hybrids</a></li>
                    <li><a href="{{ route('inventory.index', ['fuel_type' => 'Electric']) }}">Electrified Vehicles</a></li>
                </ul>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
                <h6>Helpful Links</h6>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#">About Us</a></li>
                    <li><a href="{{ route('inventory.index') }}">Inventory</a></li>
                    <li><a href="#">Financial Services</a></li>
                    <li><a href="#">Contact Us</a></li>
                </ul>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
                <h6>Help</h6>
                <ul>
                    <li><a href="#">How To Buy A Car?</a></li>
                    <li><a href="#">FAQ's</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms &amp; Conditions</a></li>
                    <li><a href="#">Legal Disclaimer</a></li>
                </ul>
            </div>

        </div>
    </div>
</footer>
<div class="footer-bar">
    &copy; {{ date('Y') }} Julio Tu Car Guy &mdash;
    Inventory synced automatically every 12 hours. All prices subject to change without notice.
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmrAYP3aFrZHaZl5HFnJbZdNg6uFzu4r1pKP"
        crossorigin="anonymous"></script>
<script>
    /* ── Mobile nav toggle ── */
    document.getElementById('navToggler').addEventListener('click', function () {
        document.getElementById('navCollapse').classList.toggle('open');
    });

    /* ── Mobile filter sidebar toggle ── */
    document.getElementById('filterToggle').addEventListener('click', function () {
        const sidebar  = document.getElementById('filterSidebar');
        const chevron  = document.getElementById('filterChevron');
        const isOpen   = sidebar.classList.toggle('open');
        chevron.className = isOpen ? 'bi bi-chevron-up ms-auto' : 'bi bi-chevron-down ms-auto';
    });

    /* ── On desktop, always show sidebar (remove mobile-open class conflicts) ── */
    function handleResize() {
        const sidebar = document.getElementById('filterSidebar');
        if (window.innerWidth >= 992) {
            sidebar.classList.remove('open'); // class irrelevant on desktop (CSS shows it always)
        }
    }
    window.addEventListener('resize', handleResize);
</script>
</body>
</html>
