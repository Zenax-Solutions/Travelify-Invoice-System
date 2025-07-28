<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Quick Customer Creation Section -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6">
            <div class="flex items-center space-x-3 mb-4">
                <div class="flex-shrink-0">
                    <x-heroicon-o-user-plus class="h-8 w-8 text-blue-600" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-blue-900">Quick Customer Creation</h3>
                    <p class="text-blue-700">Create customers instantly while creating invoices</p>
                </div>
            </div>

            <div class="space-y-3 text-sm text-blue-800">
                <div class="flex items-start space-x-2">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-200 text-blue-800 rounded-full flex items-center justify-center text-xs font-semibold">1</span>
                    <p>When creating a new invoice, click on the <strong>Customer</strong> dropdown field.</p>
                </div>
                <div class="flex items-start space-x-2">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-200 text-blue-800 rounded-full flex items-center justify-center text-xs font-semibold">2</span>
                    <p>If the customer doesn't exist, click <strong>"Create option"</strong> at the bottom of the dropdown.</p>
                </div>
                <div class="flex items-start space-x-2">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-200 text-blue-800 rounded-full flex items-center justify-center text-xs font-semibold">3</span>
                    <p>Fill in the customer details in the modal that appears and click <strong>"Create Customer"</strong>.</p>
                </div>
                <div class="flex items-start space-x-2">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-200 text-blue-800 rounded-full flex items-center justify-center text-xs font-semibold">4</span>
                    <p>The new customer will be automatically selected and you can continue with your invoice.</p>
                </div>
            </div>
        </div>

        <!-- Quick Service Creation Section -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-6">
            <div class="flex items-center space-x-3 mb-4">
                <div class="flex-shrink-0">
                    <x-heroicon-o-plus-circle class="h-8 w-8 text-green-600" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-green-900">Quick Service Creation</h3>
                    <p class="text-green-700">Add new services without leaving the invoice form</p>
                </div>
            </div>

            <div class="space-y-3 text-sm text-green-800">
                <div class="flex items-start space-x-2">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-200 text-green-800 rounded-full flex items-center justify-center text-xs font-semibold">1</span>
                    <p>In the <strong>Services</strong> section, click on any <strong>Service</strong> dropdown.</p>
                </div>
                <div class="flex items-start space-x-2">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-200 text-green-800 rounded-full flex items-center justify-center text-xs font-semibold">2</span>
                    <p>If the service doesn't exist, click <strong>"Create option"</strong> at the bottom.</p>
                </div>
                <div class="flex items-start space-x-2">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-200 text-green-800 rounded-full flex items-center justify-center text-xs font-semibold">3</span>
                    <p>Select or create a category, then enter the service name, description, and default price.</p>
                </div>
                <div class="flex items-start space-x-2">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-200 text-green-800 rounded-full flex items-center justify-center text-xs font-semibold">4</span>
                    <p>The service will be created and automatically selected with its default price populated.</p>
                </div>
            </div>
        </div>

        <!-- Tips Section -->
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-lg p-6">
            <div class="flex items-center space-x-3 mb-4">
                <div class="flex-shrink-0">
                    <x-heroicon-o-light-bulb class="h-8 w-8 text-amber-600" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-amber-900">Pro Tips</h3>
                    <p class="text-amber-700">Make the most of your invoice creation workflow</p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4 text-sm text-amber-800">
                <div class="space-y-2">
                    <h4 class="font-medium">Customer Management</h4>
                    <ul class="space-y-1 list-disc list-inside ml-2">
                        <li>Email field is optional but recommended for sending invoices</li>
                        <li>Phone numbers help with customer communication</li>
                        <li>Complete addresses are useful for delivery services</li>
                    </ul>
                </div>
                <div class="space-y-2">
                    <h4 class="font-medium">Service Management</h4>
                    <ul class="space-y-1 list-disc list-inside ml-2">
                        <li>Organize services with categories for better management</li>
                        <li>Set realistic default prices to speed up invoice creation</li>
                        <li>Use clear, descriptive names for easy identification</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Benefits Section -->
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-lg p-6">
            <div class="flex items-center space-x-3 mb-4">
                <div class="flex-shrink-0">
                    <x-heroicon-o-clock class="h-8 w-8 text-purple-600" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-purple-900">Time-Saving Benefits</h3>
                    <p class="text-purple-700">Why use instant creation features</p>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-4 text-sm">
                <div class="text-center p-4 bg-white rounded-lg border border-purple-100">
                    <x-heroicon-o-bolt class="h-8 w-8 text-purple-600 mx-auto mb-2" />
                    <h4 class="font-medium text-purple-900 mb-1">Faster Workflow</h4>
                    <p class="text-purple-700">No need to navigate away from invoice creation</p>
                </div>
                <div class="text-center p-4 bg-white rounded-lg border border-purple-100">
                    <x-heroicon-o-check-circle class="h-8 w-8 text-purple-600 mx-auto mb-2" />
                    <h4 class="font-medium text-purple-900 mb-1">Reduced Errors</h4>
                    <p class="text-purple-700">Immediate context means fewer mistakes</p>
                </div>
                <div class="text-center p-4 bg-white rounded-lg border border-purple-100">
                    <x-heroicon-o-users class="h-8 w-8 text-purple-600 mx-auto mb-2" />
                    <h4 class="font-medium text-purple-900 mb-1">Better Experience</h4>
                    <p class="text-purple-700">Seamless user experience for all team members</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>