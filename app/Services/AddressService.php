<?php

namespace App\Services;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AddressService
{
    public function create(User $user, array $data): Address
    {
        return DB::transaction(function () use ($user, $data) {
            if (($data['is_default'] ?? false) || ! $user->addresses()->exists()) {
                $user->addresses()->update(['is_default' => false]);
                $data['is_default'] = true;
            }

            return $user->addresses()->create($data);
        });
    }

    public function update(Address $address, array $data): Address
    {
        return DB::transaction(function () use ($address, $data) {
            if ($data['is_default'] ?? false) {
                $address->user->addresses()->update(['is_default' => false]);
            }

            $address->update($data);

            return $address->fresh();
        });
    }

    public function delete(Address $address): void
    {
        $wasDefault = $address->is_default;
        $user = $address->user;
        $address->delete();

        if ($wasDefault) {
            $user->addresses()->first()?->update(['is_default' => true]);
        }
    }
}
