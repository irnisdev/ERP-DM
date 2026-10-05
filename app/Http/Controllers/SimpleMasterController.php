<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Base controller untuk master sederhana yang tidak punya aturan bisnis khusus:
 * kategori produk, kategori jasa, brand, unit.
 *
 * Aturan "tidak boleh dihapus bila sudah dipakai"tetap ditangani oleh
 * SimpleMasterPolicy.
 */
abstract class SimpleMasterController extends Controller implements ResolvesRouteKey
{
    /** @return class-string<Model> */
    abstract protected function model(): string;

    abstract protected function viewBase(): string;

    abstract protected function routeBase(): string;

    abstract protected function label(): string;

    /** Kolom tambahan selain code, name, status. @return array<int, string> */
    protected function extraFields(): array
    {
        return ['description'];
    }

    protected function allowsParent(): bool
    {
        return false;
    }

    public function index(): View
    {
        $this->authorize('viewAny', $this->model());

        $rows = $this->query()->orderBy('name')->get()
            ->map(fn (Model $m) => $this->rowColumns($m));

        return view('master.simple-master.index', [
            'title' => $this->label(),
            'singular' => rtrim($this->label(), 's'),
            'routeBase' => $this->routeBase(),
            'headers' => $this->headers(),
            'rows' => $rows,
            'parents' => $this->allowsParent() ? $this->query()->orderBy('name')->get() : collect(),
            'allowsParent' => $this->allowsParent(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', $this->model());

        $model = $this->model();
        $instance = new $model(['status' => 'ACTIVE']);

        return view('master.simple-master.form', [
            'title' => 'Tambah ' . $this->label(),
            'routeBase' => $this->routeBase(),
            'model' => $instance,
            'parents' => $this->allowsParent() ? $this->query()->orderBy('name')->get() : collect(),
            'allowsParent' => $this->allowsParent(),
            'fields' => $this->extraFields(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', $this->model());

        $model = $this->model();
        $model::create($this->validateMaster($request));

        return redirect()
            ->route($this->routeBase().'.index')
            ->with('success', rtrim($this->label(), 's').' berhasil ditambahkan.');
    }

    /**
     * Record diterima sebagai primary key mentah, bukan hasil route model
     * binding, karena subclass memakai beberapa model berbeda dalam satu
     * controller dasar.
     */
    public function edit(int|string $key): View
    {
        $record = $this->findOrFail($key);

        $this->authorize('update', $record);

        return view('master.simple-master.form', [
            'title' => 'Edit ' . $this->label(),
            'routeBase' => $this->routeBase(),
            'model' => $record,
            'parents' => $this->allowsParent()
                ? $this->query()->whereKeyNot($record->getKey())->orderBy('name')->get()
                : collect(),
            'allowsParent' => $this->allowsParent(),
            'fields' => $this->extraFields(),
        ]);
    }

    public function update(Request $request, int|string $key): RedirectResponse
    {
        $record = $this->findOrFail($key);

        $this->authorize('update', $record);

        $record->update($this->validateMaster($request, $record));

        return redirect()
            ->route($this->routeBase().'.index')
            ->with('success', rtrim($this->label(), 's').' berhasil diperbarui.');
    }

    public function destroy(int|string $key): RedirectResponse
    {
        $record = $this->findOrFail($key);

        $this->authorize('delete', $record);

        $record->delete();

        return redirect()
            ->route($this->routeBase().'.index')
            ->with('success', rtrim($this->label(), 's').' berhasil dihapus.');
    }

    public function findOrFail(int|string $key): Model
    {
        return $this->query()->findOrFail($key);
    }

    protected function query()
    {
        $model = $this->model();

        return $model::query();
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateMaster(Request $request, ?Model $ignore = null): array
    {
        return $request->validate($this->masterRules($ignore));
    }

    /**
     * Subclass dapat menambah atau mengganti aturan lewat masterRules() sehingga
     * validasi tetap berjalan sekali di validateMaster().
     *
     * @return array<string, mixed>
     */
    protected function masterRules(?Model $ignore = null): array
    {
        $rules = [
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique((new ($this->model()))->getTable(), 'code')->ignore($ignore?->getKey()),
            ],
            'name' => ['required', 'string', 'max:191'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];

        if ($this->allowsParent()) {
            $rules['parent_id'] = ['nullable', 'exists:'.$this->query()->getModel()->getTable().',id'];
        }

        foreach ($this->extraFields() as $field) {
            $rules[$field] = ['nullable', 'string'];
        }

        return $rules;
    }

    /**
     * @return array<int, string>
     */
    protected function headers(): array
    {
        return array_merge(['Kode', 'Nama'], array_map('ucfirst', $this->extraFields()), ['Status', 'Aksi']);
    }

    /**
     * @return array<int, string|null>
     */
    protected function rowColumns(Model $record): array
    {
        $columns = [$record->getAttribute('code'), $record->getAttribute('name')];

        foreach ($this->extraFields() as $field) {
            $columns[] = $record->getAttribute($field);
        }

        $columns[] = $record->getAttribute('status');

        return $columns;
    }
}