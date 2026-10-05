<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Structural check untuk seluruh model: setiap relasi harus menunjuk ke kolom
 * foreign key yang benar-benar ada. Menangkap salah konfigurasi belongsToMany
 * pivot key yang tidak akan terlihat sampai query dijalankan.
 */
class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_belongs_to_relation_has_existing_foreign_key(): void
    {
        $problems = $this->inspectRelations(function (Relation $rel, Model $model): ?string {
            if (! $rel instanceof BelongsTo) {
                return null;
            }

            return $this->missingColumns($model, [$rel->getForeignKeyName()], $model->getTable());
        });

        $this->assertSame([], $problems, 'FK BelongsTo tidak ditemukan: '.implode('; ', $problems));
    }

    #[Test]
    public function every_has_many_relation_has_existing_foreign_key(): void
    {
        $problems = $this->inspectRelations(function (Relation $rel, Model $model): ?string {
            if (! $rel instanceof HasMany && ! $rel instanceof HasOne) {
                return null;
            }

            $table = $rel->getRelated()->getTable();

            return $this->missingColumns($model, [$rel->getForeignKeyName()], $table);
        });

        $this->assertSame([], $problems, 'FK HasMany tidak ditemukan: '.implode('; ', $problems));
    }

    #[Test]
    public function every_belongs_to_many_pivot_key_exists(): void
    {
        $problems = $this->inspectRelations(function (Relation $rel, Model $model): ?string {
            if (! $rel instanceof BelongsToMany) {
                return null;
            }

            $pivot = $rel->getTable();

            return $this->missingColumns($model, [
                $rel->getForeignPivotKeyName(),
                $rel->getRelatedPivotKeyName(),
            ], $pivot);
        });

        $this->assertSame([], $problems, 'Pivot key tidak ditemukan: '.implode('; ', $problems));
    }

    #[Test]
    public function package_service_pivot_uses_correct_columns(): void
    {
        $service = \App\Models\Service::factory()->create();
        $package = \App\Models\ServicePackage::factory()->create();

        $package->services()->attach($service->id, ['quantity' => 2]);

        $this->assertTrue($service->fresh()->packages->contains($package));
        $this->assertSame(2.0, (float) $service->fresh()->packages->first()->pivot->quantity);
    }

    #[Test]
    public function product_category_products_relation_uses_category_id(): void
    {
        $category = \App\Models\ProductCategory::factory()->create();
        $product = \App\Models\Product::factory()->create(['category_id' => $category->id]);

        $this->assertTrue($category->fresh()->products->contains($product));
        $this->assertSame($category->id, $product->fresh()->category->id);
    }

    #[Test]
    public function service_category_services_relation_uses_category_id(): void
    {
        $category = \App\Models\ServiceCategory::factory()->create();
        $service = \App\Models\Service::factory()->create(['category_id' => $category->id]);

        $this->assertTrue($category->fresh()->services->contains($service));
        $this->assertSame($category->id, $service->fresh()->category->id);
    }

    /**
     * @param  array<int, string>  $columns
     * @return string|null Deskripsi masalah, atau null bila semua kolom ada.
     */
    private function missingColumns(Model $model, array $columns, string $table): ?string
    {
        $schema = $model->getConnection()->getSchemaBuilder();

        if (! $schema->hasTable($table)) {
            return "tabel {$table} tidak ada";
        }

        $existing = collect($schema->getColumns($table))->pluck('name');
        $missing = array_values(array_filter($columns, fn (string $c) => ! $existing->contains($c)));

        return $missing === [] ? null : implode(',', $missing).' di '.$table;
    }

    /**
     * @param  callable(Relation, Model): ?string  $inspect
     * @return array<int, string>
     */
    private function inspectRelations(callable $inspect): array
    {
        $problems = [];

        foreach (glob(app_path('Models/*.php')) as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            if ($reflection->isAbstract() || $reflection->isTrait()) {
                continue;
            }

            $model = new $class;

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->class !== $class || $method->getNumberOfParameters() > 0) {
                    continue;
                }

                if (! $this->returnsRelation($method)) {
                    continue;
                }

                try {
                    $relation = $model->{$method->getName()}();

                    if ($relation instanceof MorphTo) {
                        continue;
                    }

                    $issue = $inspect($relation, $model);
                } catch (\Throwable $e) {
                    $problems[] = "{$class}::{$method->getName()}(): ".get_class($e).': '.$e->getMessage();

                    continue;
                }

                if ($issue !== null) {
                    $problems[] = "{$class}::{$method->getName()}() -> {$issue}";
                }
            }
        }

        return $problems;
    }

    private function returnsRelation(\ReflectionMethod $method): bool
    {
        $returnType = $method->getReturnType();

        return $returnType instanceof \ReflectionNamedType
            && ! $returnType->isBuiltin()
            && is_subclass_of($returnType->getName(), Relation::class);
    }
}