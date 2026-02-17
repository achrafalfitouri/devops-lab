<?php

namespace App\Repositories;

use App\Models\Contact;
use App\Repositories\Contracts\ContactRepositoryInterface;
use App\Repositories\Contracts\ContactLogRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class ContactRepository implements ContactRepositoryInterface
{
    protected $Contact;
    protected $ContactLogRepository;

    public function __construct(Contact $Contact, ContactLogRepositoryInterface $ContactLogRepository)
    {
        $this->Contact = $Contact;
        $this->ContactLogRepository = $ContactLogRepository;
    }
    public function getAllContacts()
    {
        return Contact::query();
    }

    public function getById($id)
    {
        return $this->Contact->find($id);
    }

    public function create(array $data)
    {
        $currentYear = date('y');
        $latestentry = $this->Contact->orderBy('id', 'desc')->first();

        if ($latestentry && isset($latestentry->code)) {
            preg_match('/C-(\d{2})-(\d+)/', $latestentry->code, $matches);

            $latestYear = $matches[1];
            $latestCounter = (int)$matches[2];

            if ($latestYear == $currentYear) {
                $newCounter = $latestCounter + 1;
            } else {
                $newCounter = 0;
            }
        } else {
            $newCounter = 3200;
        }

        $data['code'] = 'C-' . $currentYear . '-' . $newCounter;

        return $this->Contact->create($data);
    }

    public function update($id, array $data)
    {
        $Contact = $this->Contact->find($id);

        if ($Contact) {
            $oldValue = $Contact->toArray();
            $Contact->update($data);
            $newValue = $Contact->toArray();

            $this->ContactLogRepository->createLog(
                'update',
                $oldValue,
                $newValue,
                Auth::id(),
                $id
            );

            return $Contact;
        }

        return null;
    }


    public function delete($id)
    {
        $Contact = $this->Contact->withTrashed()->find($id);

        if ($Contact) {
            $oldValue = $Contact->toArray();

            $Contact->delete();

            $this->ContactLogRepository->createLog(
                'delete',
                $oldValue,
                null,
                Auth::check() ? Auth::id() : null,
                $id
            );

            return true;
        }

        return false;
    }
}
