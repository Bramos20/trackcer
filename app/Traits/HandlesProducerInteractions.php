<?php

namespace App\Traits;

use App\Models\User;
use App\Models\Producer;

trait HandlesProducerInteractions
{
    public function handleFollow(User $user, Producer $producer)
    {
        return $user->followedProducers()->toggle($producer->id);
    }

    public function handleFavourite(User $user, Producer $producer)
    {
        return $user->favouriteProducers()->toggle($producer->id);
    }

    public function isFollowing(User $user, Producer $producer): bool
    {
        return $user->followedProducers()->where('producers.id', $producer->id)->exists();
    }

    public function isFavourited(User $user, Producer $producer): bool
    {
        return $user->favouriteProducers()->where('producers.id', $producer->id)->exists();
    }
}