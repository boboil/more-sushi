@php
    use App\Models\Blog\VideoScenario;

    /** @var VideoScenario $record */
    $record = $getRecord();
    $failed = $record->status === VideoScenario::STATUS_FAILED;
    $names = \App\Models\Blog\VideoCharacter::query()->pluck('name', 'id');
@endphp

<div style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.875rem;">
    @if ($record->isProcessing())
        <div style="display: flex; align-items: center; gap: 0.5rem; color: rgb(217 119 6);">
            <x-filament::loading-indicator style="width: 1.25rem; height: 1.25rem;" />
            <span>
                {{ $record->extending ? 'Генерується продовження.' : 'Відео генерується.' }}
                Сторінка перевіряє статус автоматично кожні 15 секунд.
            </span>
        </div>
    @endif

    @if ($record->error)
        <div style="border-radius: 0.5rem; padding: 0.75rem; background: rgb(220 38 38 / 0.1); color: rgb(220 38 38);">
            <strong>{{ $failed ? 'Помилка генерації:' : 'Помилка:' }}</strong> {{ $record->error }}
        </div>
    @endif

    @if ($record->video_path)
        <video
            src="{{ asset($record->video_path) }}?v={{ $record->generated_at?->timestamp }}"
            controls
            playsinline
            style="border-radius: 0.5rem; background: #000; {{ $record->aspect_ratio === '9:16' ? 'max-height: 70vh; max-width: 100%;' : 'width: 100%; max-width: 48rem;' }}"
        ></video>
        <p style="opacity: 0.6;">
            Згенеровано {{ $record->generated_at?->format('d.m.Y H:i') }}
            @if ($record->duration)
                · {{ number_format($record->duration, 1) }} с з {{ VideoScenario::MAX_DURATION }} можливих
            @endif
        </p>
    @elseif (! $record->isProcessing() && ! $failed)
        <p style="opacity: 0.6;">Відео ще не генерувалось. Заповніть сценарій і натисніть «Згенерувати відео».</p>
    @endif

    @if ($record->cost > 0)
        <p>
            Вартість: остання генерація <strong>${{ number_format($record->last_cost ?? 0, 2) }}</strong>
            · всього на сценарій <strong>${{ number_format($record->cost, 2) }}</strong>
            @if ($record->last_usage)
                <span style="opacity: 0.6;">
                    ({{ number_format($record->last_usage['total_input_tokens'] ?? 0, 0, '.', ' ') }} вхідних,
                    {{ number_format($record->last_usage['total_output_tokens'] ?? 0, 0, '.', ' ') }} вихідних токенів)
                </span>
            @endif
        </p>
    @endif

    @if ($record->extensions)
        <div>
            <strong>Продовження</strong>
            <ol style="margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.5rem; list-style: decimal; padding-left: 1.25rem;">
                @foreach ($record->extensions as $extension)
                    <li>
                        <span style="opacity: 0.6;">
                            {{ VideoScenario::STATUSES[$extension['status']] ?? $extension['status'] }}
                            @if (! empty($extension['duration']))
                                · +{{ number_format($extension['duration'], 1) }} с
                            @endif
                            @if (! empty($extension['cost']))
                                · ${{ number_format($extension['cost'], 2) }}
                            @endif
                        </span>
                        @foreach ($extension['lines'] ?? [] as $line)
                            <div>
                                <strong>{{ $names[$line['character_id'] ?? null] ?? '—' }}</strong>
                                @if (! empty($line['direction']))
                                    <em style="opacity: 0.7;">({{ $line['direction'] }})</em>
                                @endif
                                {{ $line['text'] ?? '' }}
                            </div>
                        @endforeach
                        @if (! empty($extension['instructions']))
                            <div style="opacity: 0.7;">{{ $extension['instructions'] }}</div>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    @endif

    @if ($record->last_prompt)
        <details>
            <summary style="cursor: pointer; opacity: 0.6;">Промпт, відправлений у модель</summary>
            <pre style="margin-top: 0.5rem; white-space: pre-wrap; border-radius: 0.5rem; padding: 0.75rem; font-size: 0.75rem; background: rgb(127 127 127 / 0.08);">{{ $record->last_prompt }}</pre>
        </details>
    @endif
</div>
