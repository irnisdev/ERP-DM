@extends('layouts.app')

@section('title', 'POS')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">POS</h1>
        <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i>Riwayat Penjualan
        </a>
    </div>

    <form method="POST" action="{{ route('pos.store') }}" id="pos-form">
        @csrf

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Pelanggan &amp; Cabang</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="branch_id">Cabang <span class="text-danger">*</span></label>
                                <select name="branch_id" id="branch_id" required
                                        class="form-select @error('branch_id') is-invalid @enderror">
                                    <option value="">—</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected(old('branch_id', $branchId) == $branch->id)>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="customer_id">Customer</label>
                                <select name="customer_id" id="customer_id" class="form-select">
                                    <option value="">Umum (tanpa customer)</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}" data-type="{{ $customer->customer_type }}"
                                                @selected(old('customer_id') == $customer->id)>
                                            {{ $customer->name }} — {{ $customer->customer_code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">Cari Item</div>
                    <div class="card-body">
                        <input type="search" id="catalog-search" class="form-control"
                               placeholder="Ketik nama produk, service, atau package…" autocomplete="off">

                        <ul class="nav nav-tabs mt-3" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-product"
                                        type="button" role="tab">Produk</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-service"
                                        type="button" role="tab">Service</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-package"
                                        type="button" role="tab">Package</button>
                            </li>
                        </ul>

                        <div class="tab-content pt-3">
                            @foreach (['product' => 'products', 'service' => 'services', 'package' => 'packages'] as $key => $group)
                                <div class="tab-pane fade {{ $key === 'product' ? 'show active' : '' }}"
                                     id="tab-{{ $key }}" role="tabpanel">
                                    <div class="list-group list-group-flush" id="list-{{ $group }}"
                                         style="max-height: 320px; overflow-y: auto;">
                                        <div class="text-center text-muted py-4">Memuat…</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Keranjang</div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Item</th><th class="text-end" style="width:70px;">Qty</th>
                                    <th class="text-end">Subtotal</th><th style="width:40px;"></th></tr>
                            </thead>
                            <tbody id="cart-body">
                                <tr><td colspan="4" class="text-center text-muted py-4">
                                    <i class="bi bi-cart me-1"></i>Keranjang kosong
                                </td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Ringkasan</div>
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small mb-1" for="discount">Diskon header</label>
                                <input type="number" step="0.01" min="0" name="discount" id="discount"
                                       value="{{ old('discount', 0) }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1" for="tax">Pajak</label>
                                <input type="number" step="0.01" min="0" name="tax" id="tax"
                                       value="{{ old('tax', 0) }}" class="form-control form-control-sm">
                            </div>
                        </div>

                        <dl class="row mb-0 small">
                            <dt class="col-6">Subtotal</dt>
                            <dd class="col-6 text-end" id="sum-subtotal">0</dd>
                            <dt class="col-6">Diskon</dt>
                            <dd class="col-6 text-end text-danger" id="sum-discount">0</dd>
                            <dt class="col-6">Pajak</dt>
                            <dd class="col-6 text-end" id="sum-tax">0</dd>
                            <dt class="col-6 fw-bold">Total</dt>
                            <dd class="col-6 text-end fw-bold fs-5" id="sum-total">0</dd>
                        </dl>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">Pembayaran</div>
                    <div class="card-body">
                        <div class="mb-2">
                            <label class="form-label small mb-1" for="payment_method_id">Metode</label>
                            <select name="payment[payment_method_id]" id="payment_method_id" class="form-select form-select-sm">
                                <option value="">Bayar nanti (invoice)</option>
                                @foreach ($paymentMethods as $method)
                                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1" for="payment_amount">Nominal</label>
                            <input type="number" step="0.01" min="0" name="payment[amount]" id="payment_amount"
                                   class="form-control form-control-sm @error('payment.amount') is-invalid @enderror"
                                   placeholder="0">
                            @error('payment.amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="pay-full">
                                Isi dengan total
                            </button>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="allow_negative_stock" value="1" id="allow_negative_stock"
                                   class="form-check-input">
                            <label class="form-check-label small" for="allow_negative_stock">
                                Izinkan stok negatif
                            </label>
                            <div class="form-text">
                                Memerlukan permission <code>inventory.negative_stock</code>.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="submit-btn" disabled>
                            <i class="bi bi-check-lg me-1"></i>Simpan Transaksi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        window.POS = (function () {
            const cart = [];
            const money = (n) => new Intl.NumberFormat('id-ID').format(Math.round(n));

            function reindex() {
                document.querySelectorAll('#cart-body tr[data-index]').forEach((tr) => {
                    tr.dataset.index = cart.indexOf(cart[+tr.dataset.slot]);
                });
            }

            function totals() {
                const subtotal = cart.reduce((sum, l) => sum + l.unit_price * l.quantity, 0);
                const lineDiscount = cart.reduce((sum, l) => sum + (l.discount || 0), 0);
                const headerDiscount = parseFloat(document.getElementById('discount').value || 0);
                const tax = parseFloat(document.getElementById('tax').value || 0);

                return {
                    subtotal,
                    discount: lineDiscount + headerDiscount,
                    tax,
                    total: Math.max(0, subtotal - lineDiscount - headerDiscount + tax),
                };
            }

            function renderTotals() {
                const t = totals();
                document.getElementById('sum-subtotal').textContent = money(t.subtotal);
                document.getElementById('sum-discount').textContent = money(t.discount);
                document.getElementById('sum-tax').textContent = money(t.tax);
                document.getElementById('sum-total').textContent = money(t.total);
                document.getElementById('submit-btn').disabled = cart.length === 0;
            }

            function renderCart() {
                const body = document.getElementById('cart-body');

                if (cart.length === 0) {
                    body.innerHTML =
                        '<tr><td colspan="4" class="text-center text-muted py-4">' +
                        '<i class="bi bi-cart me-1"></i>Keranjang kosong</td></tr>';
                    renderTotals();
                    return;
                }

                body.innerHTML = cart.map((l, i) =>
                    '<tr>' +
                    '<td>' + l.name +
                    (l.item_type === 'PRODUCT' && l.stock !== null
                        ? '<div class="small text-muted">stok ' + l.stock + '</div>' : '') +
                    '<input type="hidden" name="cart[' + i + '][item_type]" value="' + l.item_type + '">' +
                    '<input type="hidden" name="cart[' + i + '][' + l.refField + ']" value="' + l.id + '">' +
                    '<input type="hidden" name="cart[' + i + '][unit_price]" value="' + l.unit_price + '">' +
                    '</td>' +
                    '<td><input type="number" step="0.0001" min="0.0001" value="' + l.quantity + '" ' +
                    'class="form-control form-control-sm text-end" data-qty="' + i + '"></td>' +
                    '<td class="text-end">' + money(l.unit_price * l.quantity - (l.discount || 0)) + '</td>' +
                    '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove="' + i + '">' +
                    '<i class="bi bi-x"></i></button></td>' +
                    '</tr>').join('');

                renderTotals();
            }

            document.getElementById('cart-body').addEventListener('input', function (e) {
                const idx = e.target.dataset.qty;
                if (idx === undefined) return;
                cart[idx].quantity = Math.max(0.0001, parseFloat(e.target.value) || 0);
                renderCart();
            });

            document.getElementById('cart-body').addEventListener('click', function (e) {
                const btn = e.target.closest('[data-remove]');
                if (!btn) return;
                cart.splice(+btn.dataset.remove, 1);
                renderCart();
            });

            ['discount', 'tax'].forEach((id) => {
                document.getElementById(id).addEventListener('input', renderTotals);
            });

            document.getElementById('pay-full').addEventListener('click', function () {
                document.getElementById('payment_amount').value = totals().total;
            });

            function addItem(payload) {
                const refField = { PRODUCT: 'product_id', SERVICE: 'service_id', PACKAGE: 'package_id' }[payload.item_type];
                const existing = cart.find((l) => l.item_type === payload.item_type && l.id === payload.id);

                if (existing) {
                    existing.quantity += 1;
                } else {
                    cart.push({
                        item_type: payload.item_type,
                        id: payload.id,
                        refField: refField,
                        name: payload.name,
                        unit_price: Number(payload.price) || 0,
                        quantity: 1,
                        discount: 0,
                        stock: payload.stock,
                    });
                }

                renderCart();
            }

            async function loadCatalog() {
                const warehouse = document.getElementById('branch_id').value;
                const params = new URLSearchParams({
                    search: document.getElementById('catalog-search').value.trim(),
                });
                if (warehouse) params.set('warehouse_id', warehouse);

                const response = await fetch('{{ route('pos.catalog') }}?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await response.json();

                [['list-products', 'products'], ['list-services', 'services'], ['list-packages', 'packages']]
                    .forEach(([elId, key]) => {
                        const el = document.getElementById(elId);

                        if (data[key].length === 0) {
                            el.innerHTML = '<div class="text-center text-muted py-4">Tidak ada data</div>';
                            return;
                        }

                        el.innerHTML = data[key].map((it) =>
                            '<button type="button" class="list-group-item list-group-item-action d-flex ' +
                            'justify-content-between align-items-center" data-item=\'' +
                            JSON.stringify(it).replace(/'/g, '&#39;') + '\'>' +
                            '<div><div>' + it.name + '</div><div class="small text-muted">' + (it.meta || '') + '</div></div>' +
                            '<div class="text-end"><div class="fw-semibold">' + money(it.price) + '</div>' +
                            (it.stock !== null ? '<div class="small text-muted">stok ' + it.stock + '</div>' : '') +
                            '</div></button>').join('');
                    });
            }

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-item]');
                if (btn) addItem(JSON.parse(btn.dataset.item));
            });

            let timer = null;
            document.getElementById('catalog-search').addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(loadCatalog, 350);
            });
            document.getElementById('branch_id').addEventListener('change', loadCatalog);

            document.getElementById('pos-form').addEventListener('submit', function (e) {
                if (cart.length === 0) {
                    e.preventDefault();
                    alert('Keranjang masih kosong.');
                    return;
                }
                document.getElementById('submit-btn').disabled = true;
            });

            loadCatalog();

            return { renderTotals: renderTotals };
        })();
    </script>
@endpush