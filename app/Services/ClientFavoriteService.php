<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClientFavoriteService
{
    /**
     * Toggle favorite status for a client
     */
    public function toggle(User $user, Client $client): bool
    {
        // Ensure the client belongs to the user's company
        if ($client->company_id !== $user->company_id) {
            return false;
        }

        $exists = DB::table('user_favorite_clients')
            ->where('user_id', $user->id)
            ->where('client_id', $client->id)
            ->exists();

        if ($exists) {
            // Remove from favorites
            DB::table('user_favorite_clients')
                ->where('user_id', $user->id)
                ->where('client_id', $client->id)
                ->delete();

            return false;
        } else {
            // Add to favorites (max 5)
            $favoriteCount = $this->getFavoriteCount($user);

            if ($favoriteCount >= 5) {
                // Remove the oldest favorite to make room
                $oldestFavorite = DB::table('user_favorite_clients')
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'asc')
                    ->first();

                if ($oldestFavorite) {
                    DB::table('user_favorite_clients')
                        ->where('id', $oldestFavorite->id)
                        ->delete();
                }
            }

            DB::table('user_favorite_clients')->insert([
                'user_id' => $user->id,
                'client_id' => $client->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        }
    }

    /**
     * Check if a client is favorited by the user
     */
    public function isFavorite(User $user, Client $client): bool
    {
        return DB::table('user_favorite_clients')
            ->where('user_id', $user->id)
            ->where('client_id', $client->id)
            ->exists();
    }

    /**
     * Get user's favorite clients
     */
    public function getFavoriteClients(User $user, int $limit = 5): Collection
    {
        return Client::whereIn('id', function ($query) use ($user) {
            $query->select('client_id')
                ->from('user_favorite_clients')
                ->where('user_id', $user->id);
        })
            ->where('company_id', $user->company_id)
            ->where('status', 'active')
            ->orderByDesc(function ($query) use ($user) {
                $query->select('created_at')
                    ->from('user_favorite_clients')
                    ->whereColumn('client_id', 'clients.id')
                    ->where('user_id', $user->id);
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Get count of user's favorite clients
     */
    public function getFavoriteCount(User $user): int
    {
        return DB::table('user_favorite_clients')
            ->where('user_id', $user->id)
            ->count();
    }

    /**
     * Get recent clients (excluding favorites to avoid duplication)
     */
    public function getRecentClients(User $user, int $limit = 3): Collection
    {
        $favoriteClientIds = DB::table('user_favorite_clients')
            ->where('user_id', $user->id)
            ->pluck('client_id');

        return Client::where('company_id', $user->company_id)
            ->where('status', 'active')
            ->whereNotIn('id', $favoriteClientIds)
            ->whereNotNull('accessed_at')
            ->orderBy('accessed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get smart client suggestions (favorites + recent up to 8 total)
     */
    public function getSmartClientSuggestions(User $user): array
    {
        $favorites = $this->getFavoriteClients($user, 5);
        $remainingSlots = 8 - $favorites->count();

        $recent = [];
        if ($remainingSlots > 0) {
            $recent = $this->getRecentClients($user, $remainingSlots);
        }

        return [
            'favorites' => $favorites,
            'recent' => $recent,
            'total' => $favorites->count() + collect($recent)->count(),
        ];
    }

    /**
     * Mark a client as accessed (for recent tracking)
     */
    public function markAsAccessed(Client $client): void
    {
        $client->markAsAccessed();
    }

    /**
     * Remove a client from favorites
     */
    public function removeFavorite(User $user, Client $client): bool
    {
        return DB::table('user_favorite_clients')
            ->where('user_id', $user->id)
            ->where('client_id', $client->id)
            ->delete() > 0;
    }

    /**
     * Add a client to favorites (with limit enforcement)
     */
    public function addFavorite(User $user, Client $client): bool
    {
        // Ensure the client belongs to the user's company
        if ($client->company_id !== $user->company_id) {
            return false;
        }

        // Check if already favorited
        if ($this->isFavorite($user, $client)) {
            return true;
        }

        $favoriteCount = $this->getFavoriteCount($user);

        if ($favoriteCount >= 5) {
            return false; // Max favorites reached
        }

        DB::table('user_favorite_clients')->insert([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }
}
