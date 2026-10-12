<x-filament-widgets::widget>
    @php($alerts = $this->getAlerts())
    @php($total = $this->total())

    <style>
        .am-wrap { display: flex; flex-direction: column; gap: 1rem; }
        .am-section {
            border: 1px solid;
            border-radius: .75rem;
            overflow: hidden;
        }
        .am-section--kgb { border-color: #fde68a; }
        .am-section--kp { border-color: #c7d2fe; }
        .am-section--pensiun { border-color: #fecaca; }
        .dark .am-section--kgb { border-color: rgba(245,158,11,.30); }
        .dark .am-section--kp { border-color: rgba(99,102,241,.30); }
        .dark .am-section--pensiun { border-color: rgba(239,68,68,.30); }

        .am-head {
            display: flex; align-items: center; gap: .5rem;
            padding: .625rem .875rem;
            font-size: .8125rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: .03em;
        }
        .am-head svg { width: 1.125rem; height: 1.125rem; }
        .am-head--kgb { background: #fffbeb; color: #92400e; }
        .am-head--kp { background: #eef2ff; color: #3730a3; }
        .am-head--pensiun { background: #fef2f2; color: #991b1b; }
        .dark .am-head--kgb { background: rgba(245,158,11,.12); color: #fcd34d; }
        .dark .am-head--kp { background: rgba(99,102,241,.12); color: #a5b4fc; }
        .dark .am-head--pensiun { background: rgba(239,68,68,.12); color: #fca5a5; }
        .am-count {
            margin-inline-start: auto;
            font-size: .75rem;
            font-weight: 700;
            padding: .0625rem .5rem;
            border-radius: 9999px;
            background: rgba(0,0,0,.08);
        }
        .dark .am-count { background: rgba(255,255,255,.12); }

        .am-body { padding: .5rem .875rem .75rem; display: flex; flex-direction: column; gap: .375rem; }
        .am-row {
            display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem;
            padding: .375rem 0;
            border-top: 1px solid rgba(0,0,0,.06);
            font-size: .8125rem;
        }
        .dark .am-row { border-top-color: rgba(255,255,255,.07); }
        .am-row:first-child { border-top: none; }
        .am-name { font-weight: 500; color: #111827; text-decoration: none; }
        .am-name:hover { text-decoration: underline; color: #2563eb; }
        .dark .am-name { color: #fff; }
        .dark .am-name:hover { color: #93c5fd; }
        .am-sub { font-size: .75rem; color: #6b7280; margin-top: .0625rem; }
        .am-date { flex-shrink: 0; text-align: right; font-size: .75rem; font-weight: 600; color: #374151; white-space: nowrap; }
        .dark .am-date { color: #d1d5db; }
        .am-rel { display: block; font-size: .6875rem; font-weight: 500; color: #9ca3af; }
        .am-late {
            display: inline-block; margin-top: .0625rem;
            font-size: .625rem; font-weight: 700; text-transform: uppercase;
            color: #dc2626;
        }
        .am-empty { font-size: .8125rem; color: #6b7280; padding: .25rem 0; }

        .am-status {
            display: inline-block; margin-inline-start: .375rem;
            font-size: .625rem; font-weight: 700;
            padding: .0625rem .375rem; border-radius: .25rem;
            background: rgba(0,0,0,.07); color: #4b5563;
        }
        .dark .am-status { background: rgba(255,255,255,.10); color: #d1d5db; }
        .am-status--pns { background: #dbeafe; color: #1e40af; }
        .am-status--pppk { background: #dcfce7; color: #166534; }
        .am-status--pppk-pw { background: #fef9c3; color: #854d0e; }
        .dark .am-status--pns { background: rgba(59,130,246,.20); color: #93c5fd; }
        .dark .am-status--pppk { background: rgba(34,197,94,.20); color: #86efac; }
        .dark .am-status--pppk-pw { background: rgba(234,179,8,.20); color: #fde047; }
    </style>

    @if ($total === 0)
        <x-filament::section>
            <x-slot name="heading">Alert Jatuh Tempo</x-slot>
            <p class="am-empty">Tidak ada KGB, kenaikan pangkat, atau pensiun dalam waktu dekat.</p>
        </x-filament::section>
    @else
        <div class="am-wrap">
            @if ($alerts['kgb']->isNotEmpty())
                <div class="am-section am-section--kgb">
                    <div class="am-head am-head--kgb">
                        <x-filament::icon icon="heroicon-m-banknotes" />
                        KGB ≤ 3 Bulan
                        <span class="am-count">{{ $alerts['kgb']->count() }}</span>
                    </div>
                    <div class="am-body">
                        @foreach ($alerts['kgb'] as $row)
                            <div class="am-row">
                                <div style="min-width:0">
                                    <a class="am-name" href="{{ \App\Filament\Kepegawaian\Resources\PegawaiProfilResource::getUrl('view', ['record' => $row['nip']]) }}">{{ $row['nama'] }}</a>
                                    <p class="am-sub">{{ $row['golongan'] ? 'Gol. '.$row['golongan'] : '' }}{{ $row['jabatan'] ? ($row['golongan'] ? ' · ' : '').$row['jabatan'] : '' }}</p>
                                </div>
                                <div class="am-date">
                                    {{ $row['tanggal']->format('d M Y') }}
                                    <span class="am-rel">{{ $row['tanggal']->diffForHumans() }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($alerts['kp']->isNotEmpty())
                <div class="am-section am-section--kp">
                    <div class="am-head am-head--kp">
                        <x-filament::icon icon="heroicon-m-arrow-trending-up" />
                        Kenaikan Pangkat ≤ 6 Bulan (perkiraan)
                        <span class="am-count">{{ $alerts['kp']->count() }}</span>
                    </div>
                    <div class="am-body">
                        @foreach ($alerts['kp'] as $row)
                            <div class="am-row">
                                <div style="min-width:0">
                                    <a class="am-name" href="{{ \App\Filament\Kepegawaian\Resources\PegawaiProfilResource::getUrl('view', ['record' => $row['nip']]) }}">{{ $row['nama'] }}</a>
                                    <p class="am-sub">{{ $row['golongan'] ? 'Gol. '.$row['golongan'] : '' }}{{ $row['jabatan'] ? ($row['golongan'] ? ' · ' : '').$row['jabatan'] : '' }}</p>
                                </div>
                                <div class="am-date">
                                    {{ $row['tanggal']->format('d M Y') }}
                                    @if ($row['terlambat'])
                                        <span class="am-late">Terlambat</span>
                                    @else
                                        <span class="am-rel">{{ $row['tanggal']->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($alerts['pensiun']->isNotEmpty())
                <div class="am-section am-section--pensiun">
                    <div class="am-head am-head--pensiun">
                        <x-filament::icon icon="heroicon-m-clock" />
                        Pensiun ≤ 1 Tahun
                        <span class="am-count">{{ $alerts['pensiun']->count() }}</span>
                    </div>
                    <div class="am-body">
                        @foreach ($alerts['pensiun'] as $row)
                            <div class="am-row">
                                <div style="min-width:0">
                                    <a class="am-name" href="{{ \App\Filament\Kepegawaian\Resources\PegawaiProfilResource::getUrl('view', ['record' => $row['nip']]) }}">{{ $row['nama'] }}</a>
                                    @php($cls = match ($row['status_short']) { 'PNS' => 'am-status--pns', 'PPPK' => 'am-status--pppk', 'PPPK PW' => 'am-status--pppk-pw', default => '' })
                                    <span class="am-status {{ $cls }}">{{ $row['status_short'] }}</span>
                                    <p class="am-sub">{{ $row['golongan'] ? 'Gol. '.$row['golongan'] : '' }}{{ $row['jabatan'] ? ($row['golongan'] ? ' · ' : '').$row['jabatan'] : '' }}</p>
                                </div>
                                <div class="am-date">
                                    {{ $row['tanggal']->format('d M Y') }}
                                    <span class="am-rel">{{ $row['tanggal']->diffForHumans() }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</x-filament-widgets::widget>
