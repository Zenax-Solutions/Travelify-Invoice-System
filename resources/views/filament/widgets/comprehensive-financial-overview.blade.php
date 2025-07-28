<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            @foreach($this->getStats() as $stat)
            <div style="padding: 10px;" class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            @if($stat->getIcon())
                            <x-filament::icon
                                :icon="$stat->getIcon()"
                                class="h-6 w-6 text-{{ $stat->getColor() }}-600" />
                            @endif
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    {{ $stat->getLabel() }}
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ $stat->getValue() }}
                                </dd>
                                @if($stat->getDescription())
                                <dd class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ $stat->getDescription() }}
                                </dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>