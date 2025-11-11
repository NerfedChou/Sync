<?php

namespace AccountingSystem\Repositories;

use AccountingSystem\Models\Company;

class CompanyRepository
{
    public function findAll(): array
    {
        return Company::orderBy('company_name')->get()->toArray();
    }

    public function findById(int $id): ?Company
    {
        return Company::find($id);
    }

    public function findByActive(bool $active = true): array
    {
        return Company::where('is_active', $active)
            ->orderBy('company_name')
            ->get()
            ->toArray();
    }

    public function create(array $data): Company
    {
        $company = new Company();
        $company->fill($data);
        $company->save();
        return $company;
    }

    public function update(int $id, array $data): bool
    {
        $company = $this->findById($id);
        if (!$company) {
            return false;
        }

        return $company->update($data);
    }

    public function delete(int $id): bool
    {
        $company = $this->findById($id);
        if (!$company) {
            return false;
        }

        return $company->delete();
    }

    public function exists(string $taxId): bool
    {
        return Company::where('tax_id', $taxId)->exists();
    }

    public function count(): int
    {
        return Company::count();
    }

    public function countActive(): int
    {
        return Company::where('is_active', true)->count();
    }
}