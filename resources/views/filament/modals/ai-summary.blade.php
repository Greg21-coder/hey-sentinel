<div class="space-y-3">
    <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-200 whitespace-pre-line">
        {{ $record->ai_summary }}
    </p>

    @if ($record->ai_summary_at)
        <p class="border-t border-gray-200 pt-3 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
            Generated {{ $record->ai_summary_at->diffForHumans() }}
            @if ($record->ai_summary_model)
                · {{ $record->ai_summary_model }}
            @endif
        </p>
    @endif
</div>
