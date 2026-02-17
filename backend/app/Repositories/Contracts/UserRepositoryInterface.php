<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function getUsers();
    public function findById($id);
    public function findWhere(array $conditions, $withTrashed = false);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function login(array $credentials);
    public function logout();
    public function assignRole(User $user, string $roleName);



    public function revokeRole(User $user, string $roleName);
}
