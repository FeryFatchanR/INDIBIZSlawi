<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateUser extends CreateRecord
{
    use ManagesRoles;

    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $user = new User(collect($data)->only(['name', 'email', 'phone', 'password'])->all());
            $user->email_verified_at = now();
            $user->save();
            $this->applyRolesAndStatus($user, $data);

            return $user;
        });
    }
}
