@php
    use Illuminate\Support\Facades\Gate;

    $sections = [
        ['label' => 'Employee', 'icon' => 'bi-people', 'items' => [
            ['label' => 'Employee', 'route' => 'employees.index', 'permission' => 'employee.view'],
            ['label' => 'Shift', 'route' => null, 'permission' => 'employee.view'],
            ['label' => 'Salary Structure', 'route' => null, 'permission' => 'employee.salary.view'],
        ]],
        ['label' => 'Master Data', 'icon' => 'bi-database', 'items' => [
            ['label' => 'Customer', 'route' => 'customers.index', 'permission' => 'customer.view'],
            ['label' => 'Product', 'route' => 'products.index', 'permission' => 'product.view'],
            ['label' => 'Product Category', 'route' => 'product-categories.index', 'permission' => 'product.view'],
            ['label' => 'Brand', 'route' => 'brands.index', 'permission' => 'product.view'],
            ['label' => 'Unit', 'route' => 'units.index', 'permission' => 'product.view'],
            ['label' => 'Service', 'route' => 'services.index', 'permission' => 'service.view'],
            ['label' => 'Service Category', 'route' => 'service-categories.index', 'permission' => 'service.view'],
            ['label' => 'Package', 'route' => 'packages.index', 'permission' => 'package.view'],
            ['label' => 'Supplier', 'route' => 'suppliers.index', 'permission' => 'supplier.view'],
        ]],
    ];
@endphp

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
        <i class="bi {{ $sections[0]['icon'] }} me-1"></i>Modul
    </a>
    <ul class="dropdown-menu">
        @foreach ($sections as $section)
            @php $visible = collect($section['items'])->filter(fn ($i) => $i['route'] && Gate::allows($i['permission'])); @endphp
            @if ($visible->isNotEmpty())
                <li><h6 class="dropdown-header">{{ $section['label'] }}</h6></li>
                @foreach ($visible as $item)
                    <li>
                        <a class="dropdown-item {{ request()->routeIs($item['route'].'*') ? 'active' : '' }}"
                           href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                    </li>
                @endforeach
            @endif
        @endforeach
    </ul>
</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-semibold' : '' }}"
       href="{{ route('dashboard') }}">Dashboard</a>
</li>