<div class="fi-widget">
    <div class="fi-card p-6 bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 rounded-xl">
        <div class="flex flex-col sm:flex-row items-center justify-between mb-4">
            <h3 class="fi-card-heading text-xl font-semibold text-gray-950 dark:text-white mb-2 sm:mb-0">
                Invoice Metrics Overview
            </h3>

            <div class="flex flex-col sm:flex-row items-center sm:items-end space-y-2 sm:space-y-0 sm:space-x-4 w-full sm:w-auto">
                <select wire:model.live="year" class="fi-input block w-full sm:w-auto border-none bg-transparent py-1.5 pe-8 ps-3 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-500 focus:ring-0 disabled:opacity-70 sm:text-sm sm:leading-6 dark:text-white dark:placeholder:text-gray-400">
                    @foreach (array_combine(range(now()->year, 2020), range(now()->year, 2020)) as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select wire:model.live="month" class="fi-input block w-full sm:w-auto border-none bg-transparent py-1.5 pe-8 ps-3 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-500 focus:ring-0 disabled:opacity-70 sm:text-sm sm:leading-6 dark:text-white dark:placeholder:text-gray-400">
                    <option value="">All Months</option>
                    <option value="01">January</option>
                    <option value="02">February</option>
                    <option value="03">March</option>
                    <option value="04">April</option>
                    <option value="05">May</option>
                    <option value="06">June</option>
                    <option value="07">July</option>
                    <option value="08">August</option>
                    <option value="09">September</option>
                    <option value="10">October</option>
                    <option value="11">November</option>
                    <option value="12">December</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mt-4">
            @foreach ($this->getStats() as $stat)
            <div class="fi-stats-overview-widget-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid gap-y-2">
                    <div class="flex items-center gap-x-2">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $stat->getLabel() }}
                        </h3>
                    </div>

                    <p class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $stat->getValue() }}
                    </p>

                    @if ($description = $stat->getDescription())
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $description }}
                    </p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

    </div>
</div>