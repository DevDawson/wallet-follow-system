<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-2xl font-bold mb-4">Ongeza Pesa</h2>

                    <form method="POST" action="{{ route('wallet.deposit') }}" class="space-y-6">
                        @csrf

                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700">Kiasi (TZS)</label>
                            <input type="number" step="0.01" name="amount" id="amount" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('amount')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-gray-600 font-bold py-2 px-4 rounded">
                                Ongeza
                            </button>
                            <a href="{{ route('wallet.index') }}" class="text-gray-600 hover:text-gray-900">
                                Nyuma
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>