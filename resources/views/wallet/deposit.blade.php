<x-app-layout>
@if(session('success'))
    <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 md:p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Add Funds</h2>
                    <p class="text-sm text-gray-500 mb-6">Deposit money into your wallet securely.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Standard Deposit (for testing/admin) -->
                        <div class="border border-gray-200 rounded-lg p-5 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-700 mb-3">Standard Deposit</h3>
                            <p class="text-xs text-gray-500 mb-4">Use this for testing or manual deposits.</p>
                            <form method="POST" action="{{ route('wallet.deposit') }}">
                                @csrf
                                <div class="mb-4">
                                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount (TZS)</label>
                                    <input type="number" step="0.01" name="amount" id="amount" required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
                                    @error('amount')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition">
                                    Deposit
                                </button>
                            </form>
                        </div>

                        <!-- PesaPal Deposit -->
                        <div class="border border-blue-200 rounded-lg p-5 bg-blue-50">
                            <h3 class="text-lg font-semibold text-blue-700 mb-3">Pay via PesaPal</h3>
                            <p class="text-xs text-blue-600 mb-4">Use card, M-Pesa, Tigo Pesa, etc.</p>
                            <form method="POST" action="{{ route('pesapal.deposit') }}">
                                @csrf
                                <div class="mb-4">
                                    <label for="amount_pesapal" class="block text-sm font-medium text-gray-700 mb-1">Amount (TZS)</label>
                                    <input type="number" step="0.01" name="amount" id="amount_pesapal" required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
                                </div>
                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition">
                                    Continue with PesaPal
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-8 text-center">
                        <a href="{{ route('wallet.index') }}" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">
                            &larr; Back to Wallet
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>