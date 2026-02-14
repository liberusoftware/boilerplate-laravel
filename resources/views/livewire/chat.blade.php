<div class="flex flex-col h-screen max-w-4xl mx-auto p-4">
    <div class="flex-1 overflow-y-auto mb-4 space-y-3 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Real-Time Chat</h2>
        
        @if($messages->isEmpty())
            <p class="text-gray-500 dark:text-gray-400 text-center py-8">No messages yet. Be the first to send a message!</p>
        @else
            @foreach($messages as $msg)
                <div class="flex items-start space-x-2 {{ $msg->user_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-xs lg:max-w-md {{ $msg->user_id === auth()->id() ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white' }} rounded-lg p-3">
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="text-xs font-semibold">{{ $msg->user->name }}</span>
                            <span class="text-xs opacity-75">{{ $msg->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm break-words">{{ $msg->message }}</p>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <form wire:submit.prevent="sendMessage" class="flex space-x-2">
            <input 
                type="text" 
                wire:model="message" 
                placeholder="Type your message..."
                class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                autocomplete="off"
            >
            <button 
                type="submit"
                class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>Send</span>
                <span wire:loading>Sending...</span>
            </button>
        </form>
        @error('message')
            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>
</div>
