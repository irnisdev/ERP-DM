<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Route model binding membutuhkan nama parameter controller sama dengan nama
 * segment route. Bila tidak cocok, Laravel tidak me-resolve binding dan model
 * diterima sebagai instance kosong — tanpa error, tapi operasi diam-diam
 * tidak pernah tersimpan.
 *
 * Contoh nyata: `Route::resource('packages')->parameters(['packages' =>
 * 'servicePackage'])` dengan controller `destroy(ServicePackage $package)`
 * menghasilkan DELETE 302 ke index tetapi tidak ada baris yang terhapus.
 *
 * Test ini memeriksa setiap route yang menerima tepat satu parameter controller
 * bertipe UrlRoutable, lalu memastikan namanya cocok dengan segment route.
 */
class RouteBindingTest extends TestCase
{
    #[Test]
    public function controller_parameter_name_matches_route_segment(): void
    {
        $problems = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $action = $route->getActionName();

            if (! str_starts_with($action, 'App\\Http\\Controllers\\')) {
                continue;
            }

            [$class, $method] = explode('@', $action);

            if (! class_exists($class) || ! method_exists($class, $method)) {
                $problems[] = "{$route->uri()} -> {$action} tidak bisa di-reflection";

                continue;
            }

            $parameters = (new ReflectionMethod($class, $method))->getParameters();

            if (count($parameters) !== 1) {
                continue;
            }

            // Nama parameter di route, sesuai urutan kemunculan di URI.
            // products/{product}/prices/edit menghasilkan ['product'].
            preg_match_all('/\{([^}?]+)\??\}/', $route->uri(), $matches);
            $segments = $matches[1];

            // Parameter controller bertipe model, sesuai urutan.
            $modelParameters = [];
            foreach ($parameters as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof \ReflectionNamedType
                    && ! $type->isBuiltin()
                    && is_subclass_of($type->getName(), \Illuminate\Contracts\Routing\UrlRoutable::class)) {
                    $modelParameters[] = $parameter->getName();
                }
            }

            // Controller yang me-resolve sendiri (SimpleMasterController)
            // memang tidak memakai route model binding.
            if (is_subclass_of($class, \App\Http\Controllers\ResolvesRouteKey::class)) {
                $this->assertTrue(
                    method_exists($class, 'findOrFail'),
                    "{$class} harus mengimplementasikan findOrFail()"
                );

                continue;
            }

            if ($modelParameters !== $segments) {
                $problems[] = sprintf(
                    'GET %s: parameter model controller [%s] tidak cocok dengan segment route [%s]. Route model binding tidak berfungsi.',
                    $route->uri(),
                    implode(', ', $modelParameters),
                    implode(', ', $segments)
                );
            }
        }

        $this->assertSame([], $problems, implode("\n", $problems));
    }

    #[Test]
    public function every_route_action_resolves_to_an_existing_method(): void
    {
        $problems = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();

            if (! str_starts_with($action, 'App\\Http\\Controllers\\')) {
                continue;
            }

            [$class, $method] = explode('@', $action);

            if (! class_exists($class)) {
                $problems[] = "{$route->uri()} -> class {$class} tidak ada";

                continue;
            }

            if (! method_exists($class, $method)) {
                $problems[] = "{$route->uri()} -> method {$class}::{$method} tidak ada";
            }
        }

        $this->assertSame([], $problems, implode("\n", $problems));
    }
}