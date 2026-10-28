@php
    /** @var \App\Models\Blog\VideoReel $record */
    $record = $getRecord();
@endphp

<div style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.875rem;">
    @if ($record->error)
        <div style="border-radius: 0.5rem; padding: 0.75rem; background: rgb(220 38 38 / 0.1); color: rgb(220 38 38);">
            <strong>Помилка:</strong> {{ $record->error }}
        </div>
    @endif

    @if ($record->video_path)
        <video
            src="{{ asset($record->video_path) }}?v={{ $record->built_at?->timestamp }}"
            controls
            playsinline
            style="border-radius: 0.5rem; background: #000; max-height: 70vh; max-width: 100%;"
        ></video>
        <p style="opacity: 0.6;">
            Зібрано {{ $record->built_at?->format('d.m.Y H:i') }} · {{ number_format($record->duration ?? 0, 1) }} с
        </p>
    @else
        <p style="opacity: 0.6;">Ролик ще не зібраний. Додайте кліпи по черзі і натисніть «Зібрати ролик».</p>
    @endif
</div>
