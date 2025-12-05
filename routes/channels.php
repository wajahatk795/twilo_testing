<?php

use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\MediaStreamController;

Broadcast::channel('media', MediaStreamController::class);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
