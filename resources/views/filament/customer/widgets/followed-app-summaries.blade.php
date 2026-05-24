<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-sparkles"
                    class="h-5 w-5 text-primary-500"
                />
                <span>AI summaries — followed apps</span>
            </div>
        </x-slot>

        @if ($apps->isEmpty())
            <p class="text-sm italic text-gray-500 dark:text-gray-400">
                Once your followed apps have AI summaries generated, they will appear here.
            </p>
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ($apps as $app)
                    <a
                        href="{{ route('filament.customer.resources.apps.view', ['record' => $app]) }}"
                        class="block rounded-lg border border-gray-200 bg-white p-4 transition hover:border-primary-400 hover:shadow dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-500"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $app->name }}
                                </p>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                    {{ $app->developer_name }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1 text-xs text-amber-500">
                                <span>★</span>
                                <span>{{ number_format((float) $app->average_rating, 1) }}</span>
                            </div>
                        </div>

                        <p class="mt-3 line-clamp-4 text-sm leading-relaxed text-gray-700 dark:text-gray-200">
                            {{ $app->ai_summary }}
                        </p>

                        @if ($app->ai_summary_at)
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Updated {{ $app->ai_summary_at->diffForHumans() }}
                            </p>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
