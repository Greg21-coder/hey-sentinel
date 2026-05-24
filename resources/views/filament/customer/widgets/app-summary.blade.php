@php
    $summary = $this->record?->ai_summary;
    $generatedAt = $this->record?->ai_summary_at;
    $model = $this->record?->ai_summary_model;
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-sparkles"
                    class="h-5 w-5 text-primary-500"
                />
                <span>AI Review Summary</span>
            </div>
        </x-slot>

        @if ($summary)
            <div class="space-y-3">
                <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-200">
                    {{ $summary }}
                </p>

                @if ($generatedAt)
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Generated {{ $generatedAt->diffForHumans() }}
                        @if ($model)
                            · {{ $model }}
                        @endif
                    </p>
                @endif
            </div>
        @else
            <p class="text-sm italic text-gray-500 dark:text-gray-400">
                No summary available yet. Reviews must be processed by the AI pipeline first.
            </p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
