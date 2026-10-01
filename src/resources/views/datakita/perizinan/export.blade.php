<!DOCTYPE html>
<html>

<head>
    <title>Export Data Proyek & Perizinan</title>
    <style>
        body { font-family: sans-serif; }
        table { margin: 20px auto; border-collapse: collapse; }
        table th, table td { border: 1px solid #3c3c3c; padding: 3px 8px; }
        th { background-color: #f2f2f2; }
    </style>
</head>

@php
    $groupLabels = ['k' => 'Data Kantor', 'p' => 'Data Proyek', 'i' => 'Data Perizinan'];
    $order = ['k', 'p', 'i'];
@endphp

<body>
    <div class="container mt-4">
        <h2>Data Proyek, Kantor, dan Perizinan</h2>
        <table border="1" class="table">
            <thead>
                <tr>
                    <th rowspan="2">No</th>
                    @foreach ($order as $alias)
                        <th colspan="{{ count($groups[$alias] ?? []) }}">{{ $groupLabels[$alias] }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($order as $alias)
                        @foreach ($groups[$alias] ?? [] as $column)
                            <th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>
                        @endforeach
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        @foreach ($order as $alias)
                            @foreach ($groups[$alias] ?? [] as $column)
                                <td>{{ $row->{"{$alias}__{$column}"} ?? '' }}</td>
                            @endforeach
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
