<?php

namespace App\\Actions\\Fortify;

use App\\Models\\User;
use Illuminate\\Support\\Facades\\Validator;
use Illuminate\\Validation\\Rules\\Password;
use Laravel\\Fortify\\Contracts\\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => Strtolower($input['email']),
            'password' => $input['password'],
        ]);

        $user->assignRole('customer');

        return $user;
    }
}
