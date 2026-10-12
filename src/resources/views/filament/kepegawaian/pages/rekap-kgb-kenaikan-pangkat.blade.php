<x-filament-panels::page>
    @php($stats = $this->getStats())

    <style>
        .rkk-stats {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1rem;
        }
        @media (min-width: 640px) {
            .rkk-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1280px) {
            .rkk-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        .rkk-stat {
            border-radius: 0.75rem;
            padding: 1rem;
            border: 1px solid;
        }
        .rkk-stat--amber { background: #fffbeb; border-color: #fde68a; }
        .rkk-stat--indigo { background: #eef2ff; border-color: #c7d2fe; }
        .rkk-stat--success { background: #f0fdf4; border-color: #bbf7d0; }
        .rkk-stat--danger { background: #fef2f2; border-color: #fecaca; }
        .dark .rkk-stat--amber { background: rgba(245,158,11,.10); border-color: rgba(245,158,11,.30); }
        .dark .rkk-stat--indigo { background: rgba(99,102,241,.10); border-color: rgba(99,102,241,.30); }
        .dark .rkk-stat--success { background: rgba(34,197,94,.10); border-color: rgba(34,197,94,.30); }
        .dark .rkk-stat--danger { background: rgba(239,68,68,.10); border-color: rgba(239,68,68,.30); }
        .rkk-stat-label { font-size: .875rem; font-weight: 500; }
        .rkk-stat--amber .rkk-stat-label { color: #b45309; }
        .rkk-stat--indigo .rkk-stat-label { color: #4338ca; }
        .rkk-stat--success .rkk-stat-label { color: #15803d; }
        .rkk-stat--danger .rkk-stat-label { color: #b91c1c; }
        .dark .rkk-stat--amber .rkk-stat-label { color: #fcd34d; }
        .dark .rkk-stat--indigo .rkk-stat-label { color: #a5b4fc; }
        .dark .rkk-stat--success .rkk-stat-label { color: #86efac; }
        .dark .rkk-stat--danger .rkk-stat-label { color: #fca5a5; }
        .rkk-stat-value { margin-top: .25rem; font-size: 1.875rem; font-weight: 600; letter-spacing: -.025em; color: #030712; }
        .dark .rkk-stat-value { color: #fff; }
        .rkk-stat-hint { margin-top: .25rem; font-size: .75rem; color: #6b7280; }
        .rkk-stat-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; }
        .rkk-stat-head svg { width: 2rem; height: 2rem; flex-shrink: 0; }

        .rkk-filter { margin-top: 1rem; }

        .rkk-months {
            margin-top: 1.5rem;
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 2.5rem 1.5rem;
            align-items: start;
        }
        @media (min-width: 1280px) {
            .rkk-months { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1536px) {
            .rkk-months { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        .rkk-block { margin-top: 1rem; }
        .rkk-block:first-child { margin-top: 0; }
        .rkk-badge {
            display: inline-flex; align-items: center; gap: .375rem;
            border-radius: 9999px; padding: .125rem .625rem;
            font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .025em;
            margin-bottom: .5rem;
        }
        .rkk-badge svg { width: .875rem; height: .875rem; }
        .rkk-badge--kgb { background: #fef3c7; color: #92400e; }
        .rkk-badge--kp { background: #e0e7ff; color: #3730a3; }
        .rkk-badge--jadwal { background: #e0f2fe; color: #075985; }
        .rkk-badge--perkiraan { background: #ede9fe; color: #5b21b6; }
        .dark .rkk-badge--kgb { background: rgba(245,158,11,.15); color: #fcd34d; }
        .dark .rkk-badge--kp { background: rgba(99,102,241,.15); color: #a5b4fc; }
        .dark .rkk-badge--jadwal { background: rgba(14,165,233,.15); color: #7dd3fc; }
        .dark .rkk-badge--perkiraan { background: rgba(139,92,246,.15); color: #c4b5fd; }

        .rkk-list { display: flex; flex-direction: column; gap: .5rem; }
        .rkk-item {
            border-radius: .5rem; padding: .75rem; border: 1px solid;
            border-inline-start-width: 4px;
        }
        .rkk-item--kgb { border-color: #fde68a; border-inline-start-color: #fbbf24; background: rgba(255,251,235,.4); }
        .rkk-item--kp { border-color: #c7d2fe; border-inline-start-color: #818cf8; background: rgba(238,242,255,.4); }
        .rkk-item--jadwal {
            border-color: #bae6fd;
            border-inline-start-color: #38bdf8;
            background: rgba(240,249,255,.5);
            border-style: dashed;
        }
        .dark .rkk-item--kgb { border-color: rgba(245,158,11,.20); border-inline-start-color: #fbbf24; background: rgba(245,158,11,.05); }
        .dark .rkk-item--kp { border-color: rgba(99,102,241,.20); border-inline-start-color: #818cf8; background: rgba(99,102,241,.05); }
        .dark .rkk-item--jadwal { border-color: rgba(14,165,233,.25); border-inline-start-color: #38bdf8; background: rgba(14,165,233,.05); }
        .rkk-item--perkiraan {
            border-color: #ddd6fe;
            border-inline-start-color: #a78bfa;
            background: rgba(245,243,255,.5);
            border-style: dashed;
        }
        .dark .rkk-item--perkiraan { border-color: rgba(139,92,246,.25); border-inline-start-color: #a78bfa; background: rgba(139,92,246,.05); }
        .rkk-item-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; }
        .rkk-item-name { font-size: .875rem; font-weight: 500; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dark .rkk-item-name { color: #fff; }
        a.rkk-item-name { text-decoration: none; display: block; }
        a.rkk-item-name:hover { text-decoration: underline; color: #2563eb; }
        .dark a.rkk-item-name:hover { color: #93c5fd; }
        .rkk-item-nip { font-size: .75rem; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .rkk-item-date { flex-shrink: 0; font-size: .75rem; font-weight: 500; color: #4b5563; }
        .dark .rkk-item-date { color: #d1d5db; }
        .rkk-item-meta {
            margin-top: .5rem; display: flex; flex-wrap: wrap;
            column-gap: .75rem; row-gap: .25rem; font-size: .75rem; color: #4b5563;
        }
        .dark .rkk-item-meta { color: #9ca3af; }
        .rkk-item-meta a { text-decoration: none; }
        .rkk-item--kgb .rkk-item-meta a { color: #b45309; }
        .rkk-item--kp .rkk-item-meta a { color: #4338ca; }
        .rkk-item-meta a:hover { text-decoration: underline; }
        .rkk-empty { font-size: .875rem; color: #6b7280; }
        .rkk-month-card > .fi-section { height: 100%; }

        .rkk-group-title {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .rkk-group-title--upcoming { margin-bottom: -.5rem; }
        .rkk-group-title--past { margin-top: .5rem; margin-bottom: -.5rem; }
        .rkk-group-line { flex: 1; height: 1px; background: #d1d5db; }
        .dark .rkk-group-line { background: rgba(255,255,255,.15); }
        .rkk-group-label {
            font-size: .8125rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #6b7280;
            white-space: nowrap;
        }
        .dark .rkk-group-label { color: #9ca3af; }
        .rkk-group-title--past .rkk-group-label { color: #9ca3af; }
        .dark .rkk-group-title--past .rkk-group-label { color: #6b7280; }
        .rkk-month-card.is-past { opacity: .72; }

        .rkk-batas {
            display: inline-block; margin-inline-start: .375rem;
            font-size: .625rem; font-weight: 700; text-transform: uppercase;
            padding: .0625rem .375rem; border-radius: .25rem;
            background: #fee2e2; color: #991b1b;
        }
        .dark .rkk-batas { background: rgba(239,68,68,.20); color: #fca5a5; }

        .rkk-jalur {
            display: inline-block;
            font-size: .625rem; font-weight: 700; text-transform: uppercase;
            padding: .0625rem .375rem; border-radius: .25rem;
        }
        .rkk-jalur--eselon { background: #f3e8ff; color: #6b21a8; }
        .rkk-jalur--fungsional { background: #cffafe; color: #155e75; }
        .rkk-jalur--pelaksana { background: #f1f5f9; color: #475569; }
        .dark .rkk-jalur--eselon { background: rgba(168,85,247,.20); color: #d8b4fe; }
        .dark .rkk-jalur--fungsional { background: rgba(6,182,212,.20); color: #67e8f9; }
        .dark .rkk-jalur--pelaksana { background: rgba(148,163,184,.20); color: #cbd5e1; }
        .rkk-item--kp.is-maks { border-color: #fecaca; border-inline-start-color: #ef4444; background: rgba(254,242,242,.5); }
        .dark .rkk-item--kp.is-maks { border-color: rgba(239,68,68,.30); border-inline-start-color: #ef4444; background: rgba(239,68,68,.06); }
    </style>

    <div class="rkk-stats">
        @foreach ([
            ['label' => 'KGB Tahun Ini', 'value' => $stats['kgb'], 'tone' => 'amber', 'icon' => 'heroicon-m-banknotes', 'hint' => 'Baris riwayat KGB (PNS)'],
            ['label' => 'Kenaikan Pangkat', 'value' => $stats['kp'], 'tone' => 'indigo', 'icon' => 'heroicon-m-arrow-trending-up', 'hint' => 'Baris riwayat pangkat (PNS)'],
            ['label' => 'Bulan Berisi', 'value' => $stats['bulan'], 'tone' => 'success', 'icon' => 'heroicon-m-calendar-days', 'hint' => 'Bulan dengan KGB/KP'],
            ['label' => 'Sdh di/lewat Batas KP', 'value' => $stats['kp_maks'], 'tone' => 'danger', 'icon' => 'heroicon-m-hand-raised', 'hint' => 'Golongan sudah maksimal'],
        ] as $stat)
            <div class="rkk-stat rkk-stat--{{ $stat['tone'] }}">
                <div class="rkk-stat-head">
                    <div>
                        <p class="rkk-stat-label">{{ $stat['label'] }}</p>
                        <p class="rkk-stat-value">{{ number_format($stat['value']) }}</p>
                        <p class="rkk-stat-hint">{{ $stat['hint'] }}</p>
                    </div>
                    <x-filament::icon :icon="$stat['icon']" />
                </div>
            </div>
        @endforeach
    </div>

    <div class="rkk-filter">
        {{ $this->form }}
    </div>

    @php($months = $this->buildMonths())

    @if ($months === [])
        <x-filament::section class="mt-6">
            <p class="rkk-empty">
                Tidak ada KGB maupun kenaikan pangkat PNS pada tahun ini
                (atau jenis yang dipilih tersembunyi).
            </p>
        </x-filament::section>
    @else
        @php($prevLewat = false)
        <div class="rkk-months">
            @foreach ($months as $bulan)
                @if ($bulan['lewat'] && ! $prevLewat)
                    <div class="rkk-group-title rkk-group-title--past">
                        <span class="rkk-group-line"></span>
                        <span class="rkk-group-label">Sudah Terlewati</span>
                        <span class="rkk-group-line"></span>
                    </div>
                @elseif (! $bulan['lewat'] && $loop->first)
                    <div class="rkk-group-title rkk-group-title--upcoming">
                        <span class="rkk-group-line"></span>
                        <span class="rkk-group-label">Belum Terlewati</span>
                        <span class="rkk-group-line"></span>
                    </div>
                @endif
                @php($prevLewat = $bulan['lewat'])
                <div @class(['rkk-month-card', 'is-past' => $bulan['lewat']])>
                    <x-filament::section>
                        <x-slot name="heading">{{ $bulan['label'] }}</x-slot>
                        <x-slot name="description">
                            {{ $bulan['kgb']->count() + $bulan['kgb_jadwal']->count() }} KGB &middot; {{ $bulan['kp']->count() }} Kenaikan Pangkat
                        </x-slot>

                        @if ($bulan['kgb']->isNotEmpty())
                            <div class="rkk-block">
                                <p class="rkk-badge rkk-badge--kgb">
                                    <x-filament::icon icon="heroicon-m-banknotes" />
                                    Kenaikan Gaji Berkala ({{ $bulan['kgb']->count() }})
                                </p>
                                <div class="rkk-list">
                                    @foreach ($bulan['kgb'] as $kgb)
                                        <div class="rkk-item rkk-item--kgb">
                                            <div class="rkk-item-top">
                                                <div style="min-width:0">
                                                    <a class="rkk-item-name" href="{{ \App\Filament\Kepegawaian\Resources\PegawaiProfilResource::getUrl('view', ['record' => $kgb->nip]) }}">{{ $kgb->profil?->nama ?? $kgb->nip }}</a>
                                                    <p class="rkk-item-nip">{{ $kgb->nip }}</p>
                                                </div>
                                                <p class="rkk-item-date">{{ $kgb->tmt_kgb?->format('d M Y') }}</p>
                                            </div>
                                            <div class="rkk-item-meta">
                                                @if ($kgb->golongan_kgb)
                                                    <span>Gol. {{ $kgb->golongan_kgb }}</span>
                                                @endif
                                                @if ($kgb->masa_kerja_kgb)
                                                    <span>MK {{ $kgb->masa_kerja_kgb }}</span>
                                                @endif
                                                @if ($kgb->nomor_sk_kgb)
                                                    <span>SK {{ $kgb->nomor_sk_kgb }}</span>
                                                @endif
                                                @if ($kgb->file_kgb_url)
                                                    <a href="{{ $kgb->file_kgb_url }}" target="_blank" rel="noopener">Berkas</a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($bulan['kgb_jadwal']->isNotEmpty())
                            <div class="rkk-block">
                                <p class="rkk-badge rkk-badge--jadwal">
                                    <x-filament::icon icon="heroicon-m-clock" />
                                    Jadwal KGB Berikutnya ({{ $bulan['kgb_jadwal']->count() }})
                                </p>
                                <div class="rkk-list">
                                    @foreach ($bulan['kgb_jadwal'] as $jadwal)
                                        <div class="rkk-item rkk-item--jadwal">
                                            <div class="rkk-item-top">
                                                <div style="min-width:0">
                                                    <a class="rkk-item-name" href="{{ \App\Filament\Kepegawaian\Resources\PegawaiProfilResource::getUrl('view', ['record' => $jadwal->nip]) }}">{{ $jadwal->nama ?? $jadwal->nip }}</a>
                                                    <p class="rkk-item-nip">{{ $jadwal->nip }}</p>
                                                </div>
                                                <p class="rkk-item-date">{{ $jadwal->kgb_selanjutnya?->format('d M Y') }}</p>
                                            </div>
                                            <div class="rkk-item-meta">
                                                @if ($jadwal->golongan)
                                                    <span>Gol. {{ $jadwal->golongan }}</span>
                                                @endif
                                                @if ($jadwal->jabatan)
                                                    <span>{{ $jadwal->jabatan }}</span>
                                                @endif
                                                @if ($jadwal->kgb_masa_kerja_saat)
                                                    <span>MK {{ $jadwal->kgb_masa_kerja_saat }}</span>
                                                @endif
                                                <span style="font-style:italic;opacity:.8">belum ada SK</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($bulan['kp']->isNotEmpty())
                            <div class="rkk-block">
                                <p class="rkk-badge rkk-badge--kp">
                                    <x-filament::icon icon="heroicon-m-arrow-trending-up" />
                                    Kenaikan Pangkat ({{ $bulan['kp']->count() }})
                                </p>
                                <div class="rkk-list">
                                    @foreach ($bulan['kp'] as $kp)
                                        @php($batas = $this->batasBaris($kp))
                                        @php($kenaBatas = in_array($batas['status'], ['maks', 'lewat'], true))
                                        <div @class(['rkk-item', 'rkk-item--kp', 'is-maks' => $kenaBatas])>
                                            <div class="rkk-item-top">
                                                <div style="min-width:0">
                                                    <a class="rkk-item-name" href="{{ \App\Filament\Kepegawaian\Resources\PegawaiProfilResource::getUrl('view', ['record' => $kp->nip]) }}">{{ $kp->profil?->nama ?? $kp->nip }}</a>
                                                    @if ($kenaBatas && $batas['maks'])
                                                        <span class="rkk-batas" title="Batas maks KP {{ $batas['label'] }} (jalur {{ $batas['jalur_label'] }}): {{ $batas['maks_label'] }}">
                                                            {{ $batas['status'] === 'lewat' ? 'Lewat Batas' : 'Batas Maks' }} {{ $batas['maks'] }}
                                                        </span>
                                                    @endif
                                                    <p class="rkk-item-nip">{{ $kp->nip }}</p>
                                                </div>
                                                <p class="rkk-item-date">{{ $kp->tmt_sk_pangkat?->format('d M Y') }}</p>
                                            </div>
                                            <div class="rkk-item-meta">
                                                @if ($kp->pangkat)
                                                    <span style="font-weight:500">
                                                        {{ $kp->pangkat }}{{ $kp->golongan ? ' ('.$kp->golongan.')' : '' }}
                                                    </span>
                                                @endif
                                                @if ($kp->jenis_kenaikan_pangkat)
                                                    <span>{{ $kp->jenis_kenaikan_pangkat }}</span>
                                                @endif
                                                @php($jalurKelas = match ($batas['jalur']) { 'struktural' => 'rkk-jalur--eselon', 'fungsional' => 'rkk-jalur--fungsional', default => 'rkk-jalur--pelaksana' })
                                                <span class="rkk-jalur {{ $jalurKelas }}" title="Jalur karier dari jenis jabatan terakhir: {{ $batas['jalur_label'] }}">{{ $batas['jalur_label'] }}</span>
                                                @if ($kp->nomor_sk_pangkat)
                                                    <span>SK {{ $kp->nomor_sk_pangkat }}</span>
                                                @endif
                                                @if ($kp->file_pangkat_url)
                                                    <a href="{{ $kp->file_pangkat_url }}" target="_blank" rel="noopener">Berkas</a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($bulan['kp_perkiraan']->isNotEmpty())
                            <div class="rkk-block">
                                <p class="rkk-badge rkk-badge--perkiraan">
                                    <x-filament::icon icon="heroicon-m-sparkles" />
                                    Perkiraan KP ({{ $bulan['kp_perkiraan']->count() }})
                                </p>
                                <div class="rkk-list">
                                    @foreach ($bulan['kp_perkiraan'] as $perk)
                                        @php($bPerk = $this->batasProfil($perk))
                                        @php($tglPerk = \Carbon\Carbon::parse($perk->tmt_golongan)->addYears(4))
                                        <div class="rkk-item rkk-item--perkiraan">
                                            <div class="rkk-item-top">
                                                <div style="min-width:0">
                                                    <a class="rkk-item-name" href="{{ \App\Filament\Kepegawaian\Resources\PegawaiProfilResource::getUrl('view', ['record' => $perk->nip]) }}">{{ $perk->nama ?? $perk->nip }}</a>
                                                    <p class="rkk-item-nip">{{ $perk->nip }}</p>
                                                </div>
                                                <p class="rkk-item-date">{{ $tglPerk->format('d M Y') }}</p>
                                            </div>
                                            <div class="rkk-item-meta">
                                                @if ($perk->golongan)
                                                    <span>Gol. {{ $perk->golongan }}</span>
                                                @endif
                                                @if ($perk->jabatan)
                                                    <span>{{ $perk->jabatan }}</span>
                                                @endif
                                                @if ($bPerk['maks'])
                                                    @php($jalurKelas2 = match ($bPerk['jalur']) { 'struktural' => 'rkk-jalur--eselon', 'fungsional' => 'rkk-jalur--fungsional', default => 'rkk-jalur--pelaksana' })
                                                    <span class="rkk-jalur {{ $jalurKelas2 }}" title="Jalur {{ $bPerk['jalur_label'] }}">{{ $bPerk['jalur_label'] }}</span>
                                                    <span>Batas {{ $bPerk['maks'] }}</span>
                                                @endif
                                                <span style="font-style:italic;opacity:.8">perkiraan tmt golongan + 4 th</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </x-filament::section>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
