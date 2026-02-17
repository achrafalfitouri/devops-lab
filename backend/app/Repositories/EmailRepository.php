<?php

namespace App\Repositories;

use App\Repositories\Contracts\EmailRepositoryInterface;
use App\Models\EmailSent;

class EmailRepository implements EmailRepositoryInterface
{

    protected $Email;

    public function __construct(EmailSent $Email)
    {
        $this->Email = $Email;
    }

    public function getEmails()
    {
        return EmailSent::query();
    }

    public function getById(string $id)
    {
        return EmailSent::findOrFail($id);
    }

    public function create(array $data)
    {
        return EmailSent::create($data);
    }

    public function update(string $id, array $data)
    {
        $Email = EmailSent::findOrFail($id);
        if ($Email) {

            $Email->update($data);


            return $Email;
        }
        return null;
    }

    public function delete(string $id)
    {
        $Email = $this->Email->find($id);

        if ($Email) {

            $Email->delete();

            return true;
        }
        return false;
    }

    public function getQuery()
    {
        return EmailSent::query();
    }
}
