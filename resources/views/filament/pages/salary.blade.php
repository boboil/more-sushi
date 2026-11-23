@php
    /** @var array $report */
    $days = $report['days'];
    $employees = $report['employees'];
    $totals = $report['totals'];
    $money = fn ($v) => number_format((float) $v, 0, '.', ' ');
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    $unallocated = array_filter($days, fn ($d) => $d['unallocated']);
    $noCoefficient = array_filter($employees, fn ($e) => $e['noCoefficient']);
    $openDay = fn (string $date) => "mountAction('editDay', { date: '{$date}' })";
@endphp

<x-filament-panels::page>
    <style>
        .sal-bar { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; justify-content: space-between; }
        .sal-month { display: flex; align-items: center; gap: .5rem; font-size: 1.125rem; font-weight: 600; }
        .sal-seg { display: inline-flex; border: 1px solid rgb(0 0 0 / .1); border-radius: .5rem; overflow: hidden; }
        .sal-seg button { padding: .375rem .75rem; font-size: .875rem; }
        .sal-seg button.on { background: var(--primary-500); color: #fff; }
        .sal-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .75rem; }
        .sal-card { border: 1px solid rgb(0 0 0 / .08); border-radius: .75rem; padding: .75rem 1rem; background: #fff; }
        .sal-card small { display: block; opacity: .6; font-size: .75rem; }
        .sal-card b { font-size: 1.25rem; font-variant-numeric: tabular-nums; }
        .sal-wrap { overflow-x: auto; border: 1px solid rgb(0 0 0 / .08); border-radius: .75rem; background: #fff; }
        .sal-table { border-collapse: separate; border-spacing: 0; font-size: .8125rem; font-variant-numeric: tabular-nums; width: max-content; min-width: 100%; }
        .sal-table th, .sal-table td { border-bottom: 1px solid rgb(0 0 0 / .06); border-right: 1px solid rgb(0 0 0 / .04); padding: .375rem .25rem; text-align: center; white-space: nowrap; }
        .sal-table th.name, .sal-table td.name { position: sticky; left: 0; z-index: 1; background: inherit; text-align: left; padding: .375rem .75rem; min-width: 11rem; }
        .sal-table tr { background: #fff; }
        .sal-table thead tr, .sal-table tr.sal-meta { background: rgb(0 0 0 / .02); }
        .sal-table .day { min-width: 2.75rem; cursor: pointer; }
        .sal-table .day:hover { background: color-mix(in oklab, var(--primary-500) 15%, transparent); }
        .sal-table .we { background: rgb(0 0 0 / .025); }
        .sal-table .today { box-shadow: inset 0 -2px 0 var(--primary-500); }
        .sal-table .self { color: rgb(217 119 6); font-weight: 600; }
        .sal-table .warn { color: rgb(220 38 38); }
        .sal-table .sum { font-weight: 600; min-width: 4.5rem; }
        .sal-table .muted { opacity: .55; font-size: .75rem; }
        .sal-note { font-size: .8125rem; opacity: .75; }
        .sal-alert { border-radius: .5rem; padding: .625rem .875rem; font-size: .875rem; background: rgb(220 38 38 / .08); color: rgb(185 28 28); }
        .dark .sal-card, .dark .sal-wrap, .dark .sal-table tr { background: rgb(24 24 27); }
        .dark .sal-table thead tr, .dark .sal-table tr.sal-meta { background: rgb(39 39 42); }
        .dark .sal-table .we { background: rgb(255 255 255 / .03); }
        .dark .sal-card, .dark .sal-wrap, .dark .sal-seg { border-color: rgb(255 255 255 / .1); }
        .dark .sal-table th, .dark .sal-table td { border-color: rgb(255 255 255 / .06); }
        .dark .sal-alert { color: rgb(252 165 165); }
    </style>

    <div class="sal-bar">
        <div class="sal-month">
            <x-filament::icon-button icon="heroicon-m-chevron-left" wire:click="shiftMonth(-1)" label="Попередній місяць" />
            <span>{{ $monthLabel }}</span>
            <x-filament::icon-button icon="heroicon-m-chevron-right" wire:click="shiftMonth(1)" label="Наступний місяць" />
        </div>
        <div class="sal-seg" role="group" aria-label="Що показувати в клітинках">
            <button type="button" wire:click="setMode('hours')" @class(['on' => $mode === 'hours'])>Години</button>
            <button type="button" wire:click="setMode('money')" @class(['on' => $mode === 'money'])>Гривні</button>
        </div>
    </div>

    <div class="sal-cards">
        <div class="sal-card"><small>Каса за місяць</small><b>{{ $money($totals['cash']) }} ₴</b></div>
        <div class="sal-card"><small>Фонд зарплати</small><b>{{ $money($totals['pool']) }} ₴</b></div>
        <div class="sal-card"><small>Розподілено</small><b>{{ $money($totals['paid']) }} ₴</b></div>
        <div class="sal-card"><small>Годин усього</small><b>{{ $num($totals['hours']) }}</b></div>
    </div>

    @if ($unallocated)
        <div class="sal-alert">
            Є каса, але ніхто не відмітив години:
            {{ collect($unallocated)->map(fn ($d) => $d['day'])->implode(', ') }} — фонд цих днів нікому не нараховано.
        </div>
    @endif
    @if ($noCoefficient)
        <div class="sal-alert">
            Немає коефіцієнта посади, тому 0 ₴:
            {{ collect($noCoefficient)->pluck('name')->implode(', ') }}. Задайте коефіцієнт ролі в «Відсоток і коефіцієнти» або призначте роль у «Користувачі».
        </div>
    @endif

    <div class="sal-wrap">
        <table class="sal-table">
            <thead>
                <tr>
                    <th class="name">Працівник</th>
                    @foreach ($days as $date => $day)
                        @php $dow = \Carbon\CarbonImmutable::parse($date)->dayOfWeekIso; @endphp
                        <th class="day @if ($dow >= 6) we @endif @if ($date === $today) today @endif" wire:click="{{ $openDay($date) }}" title="Відкрити день">
                            {{ $day['day'] }}<br><span class="muted">{{ ['', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'нд'][$dow] }}</span>
                        </th>
                    @endforeach
                    <th class="sum">Годин</th>
                    <th class="sum">Сума, ₴</th>
                    <th class="sum">₴/год</th>
                    <th class="sum">Днів</th>
                </tr>
            </thead>
            <tbody>
                <tr class="sal-meta">
                    <td class="name">Каса (Poster), ₴</td>
                    @foreach ($days as $date => $day)
                        <td class="day" wire:click="{{ $openDay($date) }}">{{ $day['cash'] !== null ? $money($day['cash']) : '' }}</td>
                    @endforeach
                    <td class="sum" colspan="4" style="text-align:left">{{ $money($totals['cash']) }}</td>
                </tr>
                <tr class="sal-meta">
                    <td class="name">Фонд, ₴</td>
                    @foreach ($days as $date => $day)
                        <td class="day @if ($day['unallocated']) warn @endif" wire:click="{{ $openDay($date) }}" title="{{ $day['percent'] !== null ? $day['percent'] . '% від каси' : '' }}">{{ $day['pool'] > 0 ? $money($day['pool']) : '' }}</td>
                    @endforeach
                    <td class="sum" colspan="4" style="text-align:left">{{ $money($totals['pool']) }}</td>
                </tr>
                @forelse ($employees as $employee)
                    <tr wire:key="emp-{{ $employee['id'] }}">
                        <td class="name">
                            {{ $employee['name'] }}
                            <div class="muted">{{ $employee['role'] ?? 'без посади' }} · {{ $num($employee['coefficient']) }}</div>
                        </td>
                        @foreach ($days as $date => $day)
                            @php $cell = $employee['cells'][$date] ?? null; @endphp
                            <td
                                wire:click="{{ $openDay($date) }}"
                                @class([
                                    'day',
                                    'self' => $cell && $cell['source'] === \App\Models\WorkingHours::SOURCE_SELF,
                                    'warn' => $cell && $employee['noCoefficient'],
                                ])
                                @if ($cell) title="{{ $num($cell['hours']) }} год · {{ $money($cell['pay']) }} ₴{{ match ($cell['source']) { 'self' => ' · внесено працівником', 'cashier' => ' · вніс касир', default => '' } }}" @endif
                            >
                                @if ($cell)
                                    {{ $mode === 'money' ? $money($cell['pay']) : $num($cell['hours']) }}
                                @endif
                            </td>
                        @endforeach
                        <td class="sum">{{ $num($employee['hours']) }}</td>
                        <td class="sum">{{ $money($employee['pay']) }}</td>
                        <td class="sum">{{ $money($employee['perHour']) }}</td>
                        <td class="sum">{{ $employee['daysWorked'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="name" colspan="{{ count($days) + 5 }}">
                            Немає працівників з коефіцієнтом. Задайте коефіцієнти посад у «Відсоток і коефіцієнти».
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="sal-note">
        Каса — виторг з Poster по категоріях продукції, оновлюється сама при відкритті сторінки. Натисніть на день, щоб внести години. <span style="color: rgb(217 119 6); font-weight: 600;">Помаранчеві</span> години працівник вніс сам.
        Фонд дня = каса × відсоток; він ділиться між тими, хто працював, пропорційно «години × коефіцієнт».
    </p>
</x-filament-panels::page>
