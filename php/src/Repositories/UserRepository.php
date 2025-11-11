<?php

namespace AccountingSystem\Repositories;

use Illuminate\Database\Capsule\Manager as Capsule;

class UserRepository
{
    public function findByUsernameOrEmail(string $usernameOrEmail): ?array
    {
        $user = Capsule::table('users')
            ->where('username', $usernameOrEmail)
            ->orWhere('email', $usernameOrEmail)
            ->first();

        return $user ? (array) $user : null;
    }

    public function findById(int $userId): ?array
    {
        $user = Capsule::table('users')
            ->where('user_id', $userId)
            ->first();

        return $user ? (array) $user : null;
    }

    public function findByCompany(int $companyId, array $filters = []): array
    {
        $query = Capsule::table('users')
            ->where('company_id', $companyId)
            ->orderBy('last_name')
            ->orderBy('first_name');

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('first_name', 'like', $search)
                  ->orWhere('last_name', 'like', $search);
            });
        }

        return $query->get()->toArray();
    }

    public function create(array $data): array
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = Capsule::table('users')->insertGetId($data);
        
        return $this->findById($id);
    }

    public function update(int $userId, array $data): ?array
    {
        $data['updated_at'] = now();

        $affected = Capsule::table('users')
            ->where('user_id', $userId)
            ->update($data);

        if ($affected) {
            return $this->findById($userId);
        }

        return null;
    }

    public function delete(int $userId): bool
    {
        $affected = Capsule::table('users')
            ->where('user_id', $userId)
            ->delete();

        return $affected > 0;
    }

    public function updateLastLogin(int $userId): void
    {
        Capsule::table('users')
            ->where('user_id', $userId)
            ->update(['last_login' => now()]);
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = Capsule::table('users')->where('email', $email);

        if ($excludeId) {
            $query->where('user_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $query = Capsule::table('users')->where('username', $username);

        if ($excludeId) {
            $query->where('user_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function countByCompany(int $companyId): int
    {
        return Capsule::table('users')
            ->where('company_id', $companyId)
            ->count();
    }

    public function getActiveUsersByCompany(int $companyId): array
    {
        return Capsule::table('users')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->select('user_id', 'username', 'email', 'first_name', 'last_name', 'role')
            ->get()
            ->toArray();
    }
}