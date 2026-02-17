<?php

namespace App\Repositories;

use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\ClientLogRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class ClientRepository implements ClientRepositoryInterface
{
    protected $client;
    protected $clientLogRepository;

    public function __construct(Client $client, ClientLogRepositoryInterface $clientLogRepository)
    {
        $this->client = $client;
        $this->clientLogRepository = $clientLogRepository;
    }

    public function getClients()
    {
        return $this->client->query();
    }

    public function findById($id)
    {
        return $this->client->find($id);
    }

    public function create(array $data)
    {
        $currentYear = date('y');
        $latestentry = $this->client->orderBy('id', 'desc')->first();

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

        return $this->client->create($data);
    }

    public function update($id, array $data)
    {
        $client = $this->client->find($id);

        if ($client) {
            $oldValue = $client->toArray(); 
            $client->update($data);
            $newValue = $client->toArray(); 
            $this->clientLogRepository->createLog(
                'update',
                $oldValue,
                $newValue,
                Auth::id(),   
                $id           
            );

            return $client;
        }

        return null;
    }

    public function delete($id)
    {
        $client = $this->client->withTrashed()->find($id);

        if ($client) {
            $oldValue = $client->toArray();

            $client->delete();

            $this->clientLogRepository->createLog(
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
