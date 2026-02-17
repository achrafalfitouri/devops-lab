<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserExport implements FromCollection, WithHeadings
{
    protected $filters;
    protected $perPage;

    public function __construct(array $filters, int $perPage)
    {
        $this->filters = $filters;
        $this->perPage = $perPage;
    }

    public function collection()
    {
        $query = User::query();

        $filterableFields = ['title_id', 'status', 'role_id'];
        $searchableFields = ['full_name', 'code'];

        foreach ($filterableFields as $field) {
            if (!empty($this->filters[$field])) {
                if ($field === 'status') {
                    $query->where($field, filter_var($this->filters[$field], FILTER_VALIDATE_BOOLEAN));
                } else {
                    $query->where($field, $this->filters[$field]);
                }
            }
        }

        if (!empty($this->filters['search'])) {
            $searchQuery = $this->filters['search'];
            $query->where(function ($q) use ($searchQuery, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'REGEXP', $searchQuery);
                }
            });
        }

        // Load all necessary relationships
        $users = $query->with(['title', 'roles:id,name,description,icon', 'cashRegisters:id,name'])->paginate($this->perPage);

        return collect($users->items())->map(function ($user) {
            return [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'code' => $user->code,
                'email' => $user->email,
                'phone' => $user->phone,
                'birthdate' => $user->birthdate ? $user->birthdate->format('Y-m-d') : 'N/A',
                'status' => $user->status ? 'Active' : 'Inactive',
                'title' => $user->title ? $user->title->name : 'N/A',
                'role' => $this->getAllUserRoles($user),
                'cash_registers' => $user->cashRegisters->count() > 0 ? $user->cashRegisters->pluck('name')->implode(', ') : 'N/A',
                'gender' => $user->gender,
                'address' => $user->address,
                'cin' => $user->cin,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Prénom',
            'Nom',
            'Nom et prénom',
            'Code',
            'E-mail',
            'Téléphone',
            'Date de naissance',
            'Statut',
            'Titre',
            'Rôle',
            'Cash Registres',
            'Genre',
            'Adresse',
            'CIN',
        ];
    }

    protected function getAllUserRoles($user)
    {
        if ($user->roles && $user->roles->count() > 0) {
            return $user->roles->pluck('name')->implode(', ');
        }
        
        return 'N/A';
    }
}