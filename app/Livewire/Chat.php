<?php

namespace App\Livewire;

use App\Events\MessageSent;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\On;

class Chat extends Component
{
    public string $message = '';
    public $messages = [];

    public function mount()
    {
        $this->loadMessages();
    }

    public function loadMessages()
    {
        $this->messages = ChatMessage::with('user')
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();
    }

    #[On('echo:chat,MessageSent')]
    public function handleMessageSent($payload)
    {
        $this->loadMessages();
    }

    public function sendMessage()
    {
        $this->validate([
            'message' => 'required|string|max:500',
        ]);

        $chatMessage = ChatMessage::create([
            'user_id' => Auth::id(),
            'message' => $this->message,
        ]);

        $chatMessage->load('user');

        broadcast(new MessageSent($chatMessage))->toOthers();

        $this->message = '';
        $this->loadMessages();
    }

    public function render()
    {
        return view('livewire.chat');
    }
}
