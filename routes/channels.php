<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('users.{userId}', function ($user, string $userId) {
    return (string) $user->id === (string) $userId;
});

Broadcast::channel('admin.notifications', function ($user) {
    return $user->hasRole('admin');
});
