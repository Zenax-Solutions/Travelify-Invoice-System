<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col sm:flex-row items-center justify-between mb-4">
            <h3 class="fi-card-heading text-xl font-semibold text-gray-950 dark:text-white mb-2 sm:mb-0">
                Comprehensive Financial Overview
            </h3>

            <div class="flex flex-col sm:flex-row items-center sm:items-end space-y-2 sm:space-y-0 sm:space-x-4 w-full sm:w-auto">
                <select wire:model.live="year" class="fi-input block w-full sm:w-auto border-none bg-transparent py-1.5 pe-8 ps-3 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-500 focus:ring-0 disabled:opacity-70 sm:text-sm sm:leading-6 dark:text-white dark:placeholder:text-gray-400">
                    @for($i = 2020; $i <= now()->year + 1; $i++)
                        <option value="{{ $i }}" @if($year==$i) selected @endif>{{ $i }}</option>
                        @endfor
                </select>

                <select wire:model.live="month" class="fi-input block w-full sm:w-auto border-none bg-transparent py-1.5 pe-8 ps-3 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-500 focus:ring-0 disabled:opacity-70 sm:text-sm sm:leading-6 dark:text-white dark:placeholder:text-gray-400">
                    <option value="1" @if($month=='1' ) selected @endif>January</option>
                    <option value="2" @if($month=='2' ) selected @endif>February</option>
                    <option value="3" @if($month=='3' ) selected @endif>March</option>
                    <option value="4" @if($month=='4' ) selected @endif>April</option>
                    <option value="5" @if($month=='5' ) selected @endif>May</option>
                    <option value="6" @if($month=='6' ) selected @endif>June</option>
                    <option value="7" @if($month=='7' ) selected @endif>July</option>
                    <option value="8" @if($month=='8' ) selected @endif>August</option>
                    <option value="9" @if($month=='9' ) selected @endif>September</option>
                    <option value="10" @if($month=='10' ) selected @endif>October</option>
                    <option value="11" @if($month=='11' ) selected @endif>November</option>
                    <option value="12" @if($month=='12' ) selected @endif>December</option>
                </select>
            </div>
        </div>

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