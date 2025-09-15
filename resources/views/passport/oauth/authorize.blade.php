@php
    $user = Auth::user();
@endphp

<x-layouts.auth>
    <div class="max-w-2xl mx-auto py-12">
        <div class="bg-white dark:bg-gray-900 shadow-xl rounded-lg overflow-hidden">
            <!-- Header -->
            <div class="px-8 py-6 bg-gradient-to-r from-blue-600 to-indigo-700 text-white">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-white/10 rounded-full">
                        <flux:icon.shield-check class="w-8 h-8" />
                    </div>
                    <div>
                        <flux:heading size="lg" class="text-white">
                            {{ __('Authorization Request') }}
                        </flux:heading>
                        <flux:text class="text-blue-100">
                            {{ __('A third-party application is requesting access to your account') }}
                        </flux:text>
                    </div>
                </div>
            </div>

            <div class="px-8 py-6">
                <!-- Application Info -->
                <div class="mb-8">
                    <div class="flex items-center space-x-4 mb-6">
                        <div class="p-3 bg-gray-100 dark:bg-gray-800 rounded-full">
                            <flux:icon.cube class="w-8 h-8 text-gray-600 dark:text-gray-400" />
                        </div>
                        <div>
                            <flux:heading size="base" class="mb-1">
                                {{ $client->name }}
                            </flux:heading>
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                {{ __('wants to access your account') }}
                            </flux:text>
                        </div>
                    </div>

                    @if(count($client->redirect_uris) > 0)
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mb-6">
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400 mb-2">
                                {{ __('This application will redirect you to:') }}
                            </flux:text>
                            <flux:text size="sm" class="font-mono text-blue-600 dark:text-blue-400">
                                {{ is_array($client->redirect_uris) ? $client->redirect_uris[0] : $client->redirect_uris }}
                            </flux:text>
                        </div>
                    @endif
                </div>

                <!-- Permissions/Scopes -->
                @if(!empty($scopes))
                    <div class="mb-8">
                        <flux:heading size="sm" class="mb-4 flex items-center">
                            <flux:icon.key class="w-5 h-5 mr-2 text-gray-500" />
                            {{ __('This application is requesting access to:') }}
                        </flux:heading>

                        <div class="space-y-3">
                            @foreach($scopes as $scope)
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <flux:icon.check-circle class="w-5 h-5 text-green-500 flex-shrink-0" />
                                    <div class="flex-1">
                                        <flux:text class="font-medium">
                                            {{ ucfirst($scope->id) }}
                                        </flux:text>
                                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                            {{ $scope->description }}
                                        </flux:text>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- User Info -->
                <div class="mb-8 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                            {{ $user->initials() }}
                        </div>
                        <div>
                            <flux:text class="font-medium">
                                {{ __('Signed in as') }} <strong>{{ $user->name }}</strong>
                            </flux:text>
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                {{ $user->email }}
                            </flux:text>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <!-- Approve Form (POST) -->
                    <form method="POST" action="/oauth/authorize" class="flex-1">
                        @csrf
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">
                        @foreach(request()->query() as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach

                        <button
                            type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <flux:icon.check class="w-4 h-4 mr-2" />
                            {{ __('Authorize Application') }}
                        </button>
                    </form>

                    <!-- Deny Form (DELETE) -->
                    <form method="POST" action="/oauth/authorize" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">
                        @foreach(request()->query() as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach

                        <button
                            type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                        >
                            <flux:icon.x-mark class="w-4 h-4 mr-2" />
                            {{ __('Cancel') }}
                        </button>
                    </form>
                </div>

                <!-- Security Note -->
                <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <div class="flex items-start space-x-3">
                        <flux:icon.exclamation-triangle class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                        <div>
                            <flux:text size="sm" class="text-amber-800 dark:text-amber-200">
                                <strong>{{ __('Security Notice:') }}</strong>
                                {{ __('Only authorize applications you trust. This will give the application access to your account information as specified above.') }}
                            </flux:text>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.auth>