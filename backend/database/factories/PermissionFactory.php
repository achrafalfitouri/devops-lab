<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = \App\Models\Permission::class;

    public function definition()
    {
      $permissions = [
        'view_client',
        'edit_client',
        'add_client',
        'delete_client',
        'view_user',
        'edit_user',
        'add_user',
        'delete_user',
        'view_cashregister',
        'edit_cashregister',
        'add_cashregister',
        'delete_cashregister',
        'assign_role',
        'assign_cashregister',
        'add_transaction',
        'view_transaction',
        'Document_viewer',
        'Payments_viewer',
        'Payments_manager',
        'logs_viewer',
     
    ];



    return [
        'name' => $this->faker->randomElement($permissions),
    ];
    }

}
