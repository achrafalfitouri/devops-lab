<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\City;
use App\Models\ClientType;
use App\Models\Gamutes;
use App\Models\Status;
use App\Models\BusinessSector;
use App\Models\Titles;
use App\Models\CashRegister;
use App\Models\CashRegisterDailyBalances;
use App\Models\CashTransaction;
use App\Models\CashTransactionType;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Quote;
use App\Models\OrderNote;
use App\Models\ProductionNote;
use App\Models\OutputNote;
use App\Models\DeliveryNote;
use App\Models\ReturnNote;
use App\Models\InvoiceCredit;
use App\Models\Invoice;
use App\Models\DocumentItem;
use App\Models\Contact;
use App\Models\OrderReceipt;
use App\Models\Payment;
use App\Models\PaymentType;
use Illuminate\Support\Str;
use App\Models\Detail;
use App\Models\EmailTemplate;
use App\Models\QuoteItem;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $details = [
            ['name' => 'Nom juridique', 'detail' => '360print'],
            ['name' => 'ICE', 'detail' => '003206806000087'],
            ['name' => 'IF', 'detail' => '53579478'],
            ['name' => 'TP', 'detail' => '64200842'],
            ['name' => 'RC', 'detail' => '132591'],
            ['name' => 'Téléphone', 'detail' => '08 08 68 03 80 - 06 70 03 60 40'],
            ['name' => 'Email', 'detail' => 'contact@360print.ma'],
            ['name' => 'Site web', 'detail' => ' www.360print.ma'],
            ['name' => 'Adresse', 'detail' => 'IMMEUBLE N°5, RUE CAPITAINE AUDIBERE CAMP EL GHOUL GUELIZ MARRAKECH.'],
        ];

        foreach ($details as $detail) {
            Detail::create([
                'name' => $detail['name'],
                'detail' => $detail['detail'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $titles = [
            "Chef d'impression et finition",
            "Équipe d'impression et finition",
            "Chef de couture et broderie",
            "Équipe de couture et broderie",
            "Chef packaging",
            "Équipe packaging",
            "Chef signalisation et habillage",
            "Équipe de signalisation et habillage",
            "Responsable RH",
            "Responsable Finance et comptabilité",
            "Responsable Facturation",
            "Responsable Achat",
            "Responsable commercial & marketing",
            "Responsable Recouvrement",
            "Responsable Caisse",
            "Responsable de stock",
            "Responsable administrative",
            "Direction général"
        ];

        foreach ($titles as $title) {
            Titles::create(['name' => $title]);
        }

        $cities = [
            "Marrakech",
            "Casablanca",
            "Rabat",
            "Agadir",
            "Aïn Harrouda",
            "Aït Melloul",
            "Al Hoceïma",
            "Azrou",
            "Beni Ansar",
            "Beni Mellal",
            "Ben Guerir",
            "Benslimane",
            "Berrechid",
            "Berkane",
            "Bouskoura",
            "Dar Bouazza",
            "Dcheira El Jihadia",
            "Drargua",
            "El Jadida",
            "El Kelaâ des Sraghna",
            "Errachidia",
            "Essaouira",
            "Fès",
            "Fnideq",
            "Fquih Ben Salah",
            "Guelmim",
            "Guercif",
            "Inezgane",
            "Kénitra",
            "Khémisset",
            "Khénifra",
            "Khouribga",
            "Ksar El Kébir",
            "Lahraouyine",
            "Larache",
            "Lqliâa",
            "Martil",
            "Meknès",
            "Midelt",
            "M'diq",
            "Mohammédia",
            "Nador",
            "Ouarzazate",
            "Oued Zem",
            "Oujda",
            "Oulad Teïma",
            "Ouazzane",
            "Safi",
            "Salé",
            "Sefrou",
            "Settat",
            "Sidi Bennour",
            "Sidi Kacem",
            "Sidi Slimane",
            "Skhirat",
            "Souk El Arbaa",
            "Suq as-Sabt Awlad an-Nama",
            "Tan-Tan",
            "Taza",
            "Témara",
            "Tétouan",
            "Tifelt",
            "Tiznit",
            "Tanger",
            "Taroudant",
            "Taourirt",
            "Youssoufia"
        ];


        foreach ($cities as $city) {
            City::create(['name' => $city]);
        }

        $types = ["Particulier", "Entreprise", "Institution"];
        foreach ($types as $type) {
            ClientType::create(['name' => $type]);
        }

        $gamutes = ["Bronze", "Silver", "Gold", "Platinium"];
        foreach ($gamutes as $gamute) {
            Gamutes::create(['name' => $gamute]);
        }

        $statuses = ["Actif", "Inactif", "En litige"];
        foreach ($statuses as $status) {
            Status::create(['name' => $status]);
        }

        $sectors = [
            "Tourisme",
            "Restauration",
            "Transport",
            "Concessionnaire",
            "Education",
            "Medecine",
            "BTP",
            "Evenementiel",
            "Sous-traitance",
            "Textile",
            "Finance et légal",
            "Communication et marketing",
            "Beauté et bien-être",
            "Cosmétique",
            "Marché public",
            "Institutions et associations",
            "Autres services",
            "Autres produits"
        ];

        foreach ($sectors as $sector) {
            BusinessSector::create(['name' => $sector]);
        }

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
            'edit_transaction',
            'delete_transaction',
            'view_document',
            'add_document',
            'edit_document',
            'delete_document',
            'view_payment',
            'add_payment',
            'edit_payment',
            'delete_payment',
            'view_logs',
            'view_stats',
            'view_archive',

        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $roles = [
            [
                'name' => 'client_viewer',
                'title' => ' Consulter les clients',
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
                'title' => 'Consulter les utilisateurs',
                'description' => 'Consulter les informations des utilisateurs.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'user_manager',
                'title' => ' Gérer les utilisateurs',
                'description' => 'Gérer les informations des utilisateurs : consulter, ajouter,  modifier et supprimer.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'transaction_manager',
                'title' => ' Gérer les transactions',
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
                'title' => 'Gérer des permissions',
                'description' => 'Gérer les informations des permissions : consulter, ajouter,  modifier et supprimer.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'cashregister_manager',
                'title' => ' Gérer les caisses',
                'description' => 'Gérer les informations des caisses : consulter, ajouter,  modifier et supprimer.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'cashregister_viewer',
                'title' => ' Consulter les caisses',
                'description' => 'Consulter les informations des caisses.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'email_manager',
                'title' => ' Gérer les emails',
                'description' => 'Gérer les informations des emails: consulter, ajouter.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'document_viewer',
                'title' => 'Consulter les documents',
                'description' => 'Consulter les informations des documents.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'document_manager',
                'title' => ' Gérer les documents',
                'description' => 'Gérer les informations des documents : consulter, ajouter,  modifier et supprimer.',
                'icon' => "tabler-user",
            ],

            [
                'name' => 'logs_viewer',
                'title' => ' Consulter les logs',
                'description' => 'Consulter les informations des logs.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'payment_viewer',
                'title' => 'Consulter les paiements',
                'description' => 'Consulter les informations des paiements.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'payment_manager',
                'title' => ' Gérer les paiements',
                'description' => 'Gérer les informations des paiements : consulter, ajouter,  modifier et supprimer.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'stats_viewer',
                'title' => ' Consulter les statistiques',
                'description' => 'Consulter les informations des paiements.',
                'icon' => "tabler-user",
            ],
            [
                'name' => 'archive_viewer',
                'title' => ' Consulter les archives',
                'description' => 'Consulter les informations des archives',
                'icon' => "tabler-user",
            ],
        ];


        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }

        $roles = Role::all();
        $permissions = Permission::all();

        foreach ($roles as $role) {
            $roleNameParts = explode('_', $role->name);
            $roleEntity = $roleNameParts[0];
            $roleType = $roleNameParts[1] ?? null;

            $filteredPermissions = $permissions->filter(function ($permission) use ($roleEntity) {
                return Str::contains($permission->name, $roleEntity);
            });

            if ($roleType === 'viewer') {
                $viewPermissions = $filteredPermissions->filter(function ($permission) {
                    return Str::startsWith($permission->name, 'view');
                });
                $role->permissions()->attach($viewPermissions);
            } elseif ($roleType === 'manager') {
                $role->permissions()->attach($filteredPermissions);
            }

            if ($role->name === 'transaction_manager') {
                $cashregisterPermissions = $permissions->whereIn('name', [
                    'add_transaction',
                    'view_transaction'
                ]);
                $role->permissions()->attach($cashregisterPermissions);
            }

            if ($role->name === 'permission_manager') {
                $assignRolePermission = $permissions->firstWhere('name', 'assign_role');
                if ($assignRolePermission) {
                    $role->permissions()->attach($assignRolePermission);
                }
            }

            if ($role->name === 'cashregister_manager') {
                $assignPermission = $permissions->firstWhere('name', 'assign-cashregister');
                if ($assignPermission) {
                    $role->permissions()->sync([$assignPermission->id]);
                }
            }
        }

        $paymentTypes = ['Espèce', 'Chèque', 'Virement', 'Effet'];
        foreach ($paymentTypes as $paymentType) {
            PaymentType::create(['name' => $paymentType]);
        }

        $clientDePassage = Client::factory()->clientDePassage()->create();

        $companies = [
            'Société Maroc Télécom',
            'Cosumar',
            'LabelVie',
            'Attijariwafa Bank',
            'Royal Air Maroc',
            'BMCE Bank of Africa',
            'Banque Populaire',
            'Inwi',
            'Orange Maroc',
            'Afriquia',
            'Total Maroc',
            'Marjane',
            'Aswak Assalam',
            'Managem',
            'ONA Group',
            'Holmarcom',
            'Société Générale Maroc',
            'Crédit Agricole du Maroc',
            'CIH Bank',
            'Lydec',
            'RATP Dev Casablanca',
            'Marsa Maroc',
            'Renault Maroc',
            'Sopriam (Peugeot, Citroën)',
            'Maghreb Steel',
            'Cosmos Electro',
            'BIM Stores Maroc',
            'Centrale Danone Maroc',
            'Dislog Group'
        ];

        foreach ($companies as $company) {
            Client::create(['legal_name' => $company, 'trade_name' => $company]);
        }

        Client::factory()->count(10)->create();
        Client::factory()->clientDePassage()->create();

        $caisse =['Marjane', 'Aswak Assalam', 'Label\'Vie', 'Carrefour Market', 'Kitea', 'Bim', 'Sopriam'];

        foreach ($caisse as $caisse) {
            CashRegister::create(['name' => $caisse]);
        }
        CashTransactionType::factory()->create(['name' => 'Entrée', 'sign' => 1]);
        CashTransactionType::factory()->create(['name' => 'Sortie', 'sign' => -1]);

        User::factory()->count(5)->create()->each(function ($user) use ($roles) {
            $user->roles()->attach($roles->random(1));
        });

        $superUser = User::factory()->create([
            'email' => 'superuser@example.com',
            'password' => bcrypt('123456'),
            'status' => 1,
        ]);
        $superUser->roles()->attach($roles->pluck('id'));

        CashTransaction::factory()->count(5)->create([
            'user_id' => $superUser->id
        ]);

        CashRegisterDailyBalances::factory()->count(5)->create();
        Payment::factory()->count(5)->create();


        Contact::factory()->count(5)->create();
        OrderReceipt::factory()->count(5)->create();
        Quote::factory()->count(10)->create();
        QuoteItem::factory()->count(5)->create();
        OrderNote::factory()->count(10)->create();
        ProductionNote::factory()->count(10)->create();
        OutputNote::factory()->count(10)->create();
        DeliveryNote::factory()->count(10)->create();
        ReturnNote::factory()->count(10)->create();
        InvoiceCredit::factory()->count(10)->create();
        Invoice::factory()->count(10)->create();
        DocumentItem::factory()->count(10)->create();

        EmailTemplate::factory()->createAllTemplates();
    }
}
