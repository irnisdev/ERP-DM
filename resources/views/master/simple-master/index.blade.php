@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ $title }}</h1>
        <a href="{{ route($routeBase.'.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Tambah {{ $singular }}
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        @foreach ($headers as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php $record = $row[count($row) - 1]; @endphp
                        <tr>
                            @foreach (array_slice($row, 0, -1) as $cell)
                                <td>
                                    @if (is_array($cell) && isset($cell['html']))
                                        {!! $cell['html'] !!}
                                    @else
                                        {{ $cell ?? '—' }}
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-end">
                                <a href="{{ route($routeBase.'.edit', $record) }}"
                                   class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" class="d-inline"
                                      action="{{ route($routeBase.'.destroy', $record) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Hapus {{ $singular }} ini?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($headers) }}" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-1"></i>Belum ada data
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection