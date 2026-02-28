<x-app-layout>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 md:p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Who I'm Following</h2>
                    <p class="text-sm text-gray-500 mb-6">People you follow ({{ $following->count() }})</p>

                    <div class="space-y-4">
                        @forelse($following as $followed)
                        <div class="flex items-center justify-between p-4 border border-gray-100 rounded-lg hover:bg-gray-50">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-400 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                    {{ strtoupper(substr($followed->name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ $followed->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $followed->email }}</p>
                                </div>
                            </div>
                            <div>
                                <form method="POST" action="{{ route('follow.unfollow', $followed) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs bg-red-100 text-red-600 hover:bg-red-200 px-3 py-1 rounded-full font-medium">
                                        Unfollow
                                    </button>
                                </form>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Not following anyone</h3>
                            <p class="mt-1 text-sm text-gray-500">Discover people to follow from the "All Users" page.</p>
                        </div>
                        @endforelse
                    </div>

                    <div class="mt-8 text-center">
                        <a href="{{ route('follow.index') }}" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">
                            &larr; Back to All Users
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>