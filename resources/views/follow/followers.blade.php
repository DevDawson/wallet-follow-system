<x-app-layout>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 md:p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">My Followers</h2>
                    <p class="text-sm text-gray-500 mb-6">People who follow you ({{ $followers->count() }})</p>

                    <div class="space-y-4">
                        @forelse($followers as $follower)
                        <div class="flex items-center justify-between p-4 border border-gray-100 rounded-lg hover:bg-gray-50">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-400 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                    {{ strtoupper(substr($follower->name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ $follower->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $follower->email }}</p>
                                </div>
                            </div>
                            <div>
                                <span class="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full">Follower</span>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No followers yet</h3>
                            <p class="mt-1 text-sm text-gray-500">When someone follows you, they'll appear here.</p>
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