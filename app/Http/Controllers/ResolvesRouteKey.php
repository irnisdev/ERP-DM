<?php

namespace App\Http\Controllers;

/**
 * Penanda controller yang me-resolve record-nya sendiri dari route key,
 * bukan lewat route model binding.
 *
 * Dipakai SimpleMasterController yang melayani beberapa model berbeda dalam
 * satu class, sehingga tidak bisa memakai type-hint model konkret.
 */
interface ResolvesRouteKey
{
    /**
     * Resolusi record berdasarkan primary key dari route.
     */
    public function findOrFail(int|string $key): \Illuminate\Database\Eloquent\Model;
}