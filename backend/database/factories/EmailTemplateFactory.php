<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition()
    {
        // Default template definition - this is required for the factory system
        // but won't be used directly in our case
        return [
            'name' => 'Template 1',
            'subject' => "Mise à jour du document",
            'content' => "Contenu par défaut",
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (EmailTemplate $template) {
            // This won't be triggered in our seeder
        })->afterMaking(function (EmailTemplate $template) {
            // This won't be triggered in our seeder
        });
    }

    /**
     * Create both email templates at once
     */
    public function createAllTemplates()
    {
        $templates = [
            'Template 1' => "Bonjour {{ \$contact->full_name }},<br><br>
                        Nous avons le plaisir de vous informer que le document intitulé « {{ \$document }} » a récemment été mis à jour.<br><br>
                        Vous pouvez le consulter en cliquant sur le lien ci-dessous :<br><br>
                        Bien à vous,<br>
                        {{ \$client->legal_name }}",

            'Template 2' => "Bonjour {{ \$contact->full_name }},<br><br>
                        Le document « {{ \$document }} » a été actualisé et est désormais accessible.<br><br>
                        Pour en prendre connaissance, veuillez cliquer sur le lien ci-dessous :<br><br>
                        Cordialement,<br>
                        {{ \$client->legal_name }}",
        ];

        foreach ($templates as $name => $content) {
            EmailTemplate::create([
                'name' => $name,
                'subject' => "Mise à jour du document",
                'content' => $content,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this;
    }
    }

