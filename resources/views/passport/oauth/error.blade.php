@php
    $errorTitle = match($error ?? '') {
        'invalid_request' => __('Invalid Request'),
        'unauthorized_client' => __('Unauthorized Application'),
        'access_denied' => __('Access Denied'),
        'unsupported_response_type' => __('Unsupported Response Type'),
        'invalid_scope' => __('Invalid Scope'),
        'server_error' => __('Server Error'),
        'temporarily_unavailable' => __('Service Temporarily Unavailable'),
        default => __('OAuth Error'),
    };

    $errorMessage = $error_description ?? match($error ?? '') {
        'invalid_request' => __('The request is missing a required parameter, includes an invalid parameter value, includes a parameter more than once, or is otherwise malformed.'),
        'unauthorized_client' => __('The client is not authorized to request an authorization code using this method.'),
        'access_denied' => __('The resource owner or authorization server denied the request.'),
        'unsupported_response_type' => __('The authorization server does not support obtaining an authorization code using this method.'),
        'invalid_scope' => __('The requested scope is invalid, unknown, or malformed.'),
        'server_error' => __('The authorization server encountered an unexpected condition that prevented it from fulfilling the request.'),
        'temporarily_unavailable' => __('The authorization server is currently unable to handle the request due to temporary overloading or maintenance.'),
        default => __('An unexpected error occurred during the authorization process.'),
    };

    $errorIcon = match($error ?? '') {
        'access_denied' => 'shield-exclamation',
        'unauthorized_client' => 'key',
        'server_error' => 'exclamation-triangle',
        'temporarily_unavailable' => 'clock',
        default => 'x-circle',
    };
@endphp

<x-layouts.auth>
    <div class="max-w-2xl mx-auto py-12">
        <div class="bg-white dark:bg-gray-900 shadow-xl rounded-lg overflow-hidden">
            <!-- Header -->
            <div class="px-8 py-6 bg-gradient-to-r from-red-600 to-red-700 text-white">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-white/10 rounded-full">
                        <flux:icon name="{{ $errorIcon }}" class="w-8 h-8" />
                    </div>
                    <div>
                        <flux:heading size="lg" class="text-white">
                            {{ $errorTitle }}
                        </flux:heading>
                        <flux:text class="text-red-100">
                            {{ __('OAuth Authorization Failed') }}
                        </flux:text>
                    </div>
                </div>
            </div>

            <div class="px-8 py-6">
                <!-- Error Details -->
                <div class="mb-8">
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-6 border border-red-200 dark:border-red-800">
                        <flux:text class="text-red-800 dark:text-red-200">
                            {{ $errorMessage }}
                        </flux:text>

                        @if(isset($error) && $error !== $errorTitle)
                            <div class="mt-4 pt-4 border-t border-red-200 dark:border-red-700">
                                <flux:text size="sm" class="text-red-600 dark:text-red-400">
                                    <strong>{{ __('Error Code:') }}</strong> {{ $error }}
                                </flux:text>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Troubleshooting -->
                <div class="mb-8">
                    <flux:heading size="sm" class="mb-4 flex items-center">
                        <flux:icon.light-bulb class="w-5 h-5 mr-2 text-amber-500" />
                        {{ __('What you can do:') }}
                    </flux:heading>

                    <div class="space-y-3">
                        @if(($error ?? '') === 'access_denied')
                            <div class="flex items-start space-x-3">
                                <flux:icon.arrow-right class="w-4 h-4 text-gray-400 mt-1 flex-shrink-0" />
                                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                    {{ __('You chose not to authorize the application. You can try again if you change your mind.') }}
                                </flux:text>
                            </div>
                        @elseif(($error ?? '') === 'unauthorized_client')
                            <div class="flex items-start space-x-3">
                                <flux:icon.arrow-right class="w-4 h-4 text-gray-400 mt-1 flex-shrink-0" />
                                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                    {{ __('Contact the application developer to report this issue.') }}
                                </flux:text>
                            </div>
                        @else
                            <div class="flex items-start space-x-3">
                                <flux:icon.arrow-right class="w-4 h-4 text-gray-400 mt-1 flex-shrink-0" />
                                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                    {{ __('Try the authorization process again.') }}
                                </flux:text>
                            </div>
                            <div class="flex items-start space-x-3">
                                <flux:icon.arrow-right class="w-4 h-4 text-gray-400 mt-1 flex-shrink-0" />
                                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                    {{ __('Contact the application developer if the problem persists.') }}
                                </flux:text>
                            </div>
                        @endif
                        <div class="flex items-start space-x-3">
                            <flux:icon.arrow-right class="w-4 h-4 text-gray-400 mt-1 flex-shrink-0" />
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                {{ __('Check your OAuth applications in settings if needed.') }}
                            </flux:text>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="javascript:history.back()" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <flux:icon.arrow-left class="w-4 h-4 mr-2" />
                        {{ __('Go Back') }}
                    </a>

                    <a href="{{ route('dashboard') }}" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        <flux:icon.home class="w-4 h-4 mr-2" />
                        {{ __('Dashboard') }}
                    </a>
                </div>

                <!-- Additional Help -->
                <div class="mt-6 text-center">
                    <flux:text size="sm" class="text-gray-500 dark:text-gray-400">
                        {{ __('Need help?') }}
                        <a href="{{ route('settings.oauth') }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                            {{ __('Manage your OAuth applications') }}
                        </a>
                    </flux:text>
                </div>
            </div>
        </div>
    </div>
</x-layouts.auth>