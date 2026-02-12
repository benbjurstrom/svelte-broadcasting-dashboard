<?php

use App\Models\Post;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('orders.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat-room', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});

Broadcast::channel('App.Models.Post.{id}', function ($user, $id) {
    return Post::where('id', $id)->where('user_id', $user->id)->exists();
});
