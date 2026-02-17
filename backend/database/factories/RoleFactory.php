<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = \App\Models\Role::class;

    protected static $roleIndex = 0;

    public function definition()
    {
        $roleData = $this->getNextRoleData();

        return [
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'name' => $roleData['name'],
            'title' => $roleData['title'],
            'description' => $roleData['description'],
            'icon' => $this->getRoleIcon($roleData['name']),
        ];
    }


    private function getRoleData()
    {
        return [
            [
                'name' => 'client_viewer',
                'title' => ' Visualiser les clients',
                'description' => 'Consulter les informations des clients.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'client_manager',
                'title' => 'Gérer les clients',
                'description' => 'Gérer les informations des clients : consulter, ajouter,  modifier et supprimer.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'user_viewer',
                'title' => 'Visualiser les Utilisateurs',
                'description' => 'Consulter les informations des utilisateurs.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'user_manager',
                'title' => ' Gérer les Utilisateurs',
                'description' => 'Gérer les informations des utilisateurs : consulter, ajouter,  modifier et supprimer.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'transaction_manager',
                'title' => ' Gérer les Transactions',
                'description' => 'Gérer les informations des transactions : consulter, ajouter,  modifier et supprimer.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'transaction_viewer',
                'title' => ' Consulter les transactions',
                'description' => 'Consulter les informations des transactions.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'permission_manager',
                'title' => 'Gérer des Permissions',
                'description' => 'Gérer les informations des permissions : consulter, ajouter,  modifier et supprimer.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'cashregister_manager',
                'title' => ' Gérer les Caisse',
                'description' => 'Gérer les informations des caisse : consulter, ajouter,  modifier et supprimer.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'email_manager',
                'title' => ' Gérer les Emails',
                'description' => 'Gérer les informations des emails : consulter, ajouter,  modifier et supprimer.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'document_viewer',
                'title' => 'Visualiser les Documents',
                'description' => 'Consulter les informations des documents.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'document_manager',
                'title' => ' Gérer les Documents',
                'description' => 'Gérer les informations des documents : consulter, ajouter,  modifier et supprimer.',
                'icon' => "tabler-user",
            ],

            [
                'name' => 'logs_viewer',
                'title' => ' Visualiser les Logs',
                'description' => 'Consulter les informations des logs.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'payment_viewer',
                'title' => 'Visualiser les Paiements',
                'description' => 'Consulter les informations des paiements.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'payment_manager',
                'title' => ' Gérer les Paiements',
                'description' => 'Gérer les informations des paiements : consulter, ajouter,  modifier et supprimer.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'stats_viewer',
                'title' => ' Visualiser les Statistiques',
                'description' => 'Consulter les informations des paiements.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'archive_viewer',
                'title' => ' Visualiser les archives',
                'description' => 'Consulter les informations des paiements',
                'icon' => "tabler-user",
            ],
        ];
    }

    private function getNextRoleData()
    {
        $roles = $this->getRoleData();

        $role = $roles[self::$roleIndex];

        self::$roleIndex = (self::$roleIndex + 1) % count($roles);

        return $role;
    }

    private function getRoleIcon($roleName)
    {

        $iconMapping = [
            'client_viewer' => 'tabler-user',
            'client_manager' => 'tabler-user',
            'user_viewer' => 'tabler-user',
            'user_manager' => 'tabler-user',
            'transaction_manager' => 'tabler-user',
            'permission_manager' => 'tabler-user',
            'cashregister_manager' => 'tabler-user',
            'email_manager' => 'tabler-user',
            'Document_viewer' => 'tabler-user',
            'Document_manager' => 'tabler-user',
            'Payments_viewer' => 'tabler-user',
            'Payments_manager' => 'tabler-user',
            'logs_manager' => 'tabler-user',
            'stats_viewer' => 'tabler-user',
            'archive_viewer' => 'tabler-user',




        ];



        return isset($iconMapping[$roleName])
        ?  "tabler-user"
        : "tabler-user";
    }
}
