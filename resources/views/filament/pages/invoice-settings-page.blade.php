<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-primary-100 dark:bg-primary-900 rounded-lg">
                    <x-heroicon-o-document-text class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Invoice Settings
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Customize how your invoices appear to customers
                    </p>
                </div>
            </div>

            <form wire:submit="save">
                {{ $this->form }}

                <div class="mt-6 flex justify-end">
                    {{ $this->getFormActions()[0] }}
                </div>
            </form>
        </div>

        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                <x-heroicon-o-information-circle class="w-5 h-5 inline mr-2 text-blue-500" />
                Settings Guide
            </h3>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <h4 class="font-medium text-gray-800 dark:text-gray-200">Company Info</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Configure your company name, tagline, and logo display settings.
                        </p>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-800 dark:text-gray-200">Invoice Sections</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Toggle visibility of different sections on your invoices.
                        </p>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-800 dark:text-gray-200">Payment Info</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Add multiple bank accounts for customer payments.
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <h4 class="font-medium text-gray-800 dark:text-gray-200">Notes & Footer</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Customize footer messages and additional information.
                        </p>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-800 dark:text-gray-200">Email Settings</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Configure email templates for sending invoices.
                        </p>
                    </div>

                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            <x-heroicon-o-light-bulb class="w-4 h-4 inline mr-1" />
                            <strong>Tip:</strong> Changes will apply to all new invoices. Existing invoices remain unchanged.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>