<?php

namespace App\Repositories;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\UserLogRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class UserRepository implements UserRepositoryInterface
{
    protected $user;
    protected $userLogRepository;

    public function __construct(User $user, UserLogRepositoryInterface $userLogRepository)
    {
        $this->user = $user;
        $this->userLogRepository = $userLogRepository;
    }

    public function getUsers()
    {
        return $this->user->query();
    }

    public function findById($id)
    {
        return $this->user->find($id);
    }

    public function findWhere(array $conditions, $withTrashed = false)
    {
        $query = $this->user->query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        foreach ($conditions as $condition) {
            if (is_array($condition) && count($condition) === 3) {
                $query->where($condition[0], $condition[1], $condition[2]);
            }
        }

        return $query->get();
    }

    public function create(array $data)
    {
        $currentYear = date('y'); 
        $latestentry = $this->user->orderBy('id', 'desc')->first();  
        if ($latestentry && isset($latestentry->code)) {
            preg_match('/U-(\d{2})-(\d+)/', $latestentry->code, $matches);

            $latestYear = $matches[1];
            $latestCounter = (int)$matches[2];
 
          
            if ($latestYear == $currentYear) {
                $newCounter = $latestCounter + 1;
            } else {
                $newCounter = 3200;
            }
        } else {
            $newCounter = 3200;  
        }

        $data['code'] = 'U-' . $currentYear . '-' . $newCounter;

        return $this->user->create($data);
    }

    public function update($id, array $data)
    {
        $user = $this->user->find($id);
        if ($user) {
            $oldData = $user->toArray(); 
            $user->update($data);
            $newData = $user->toArray(); 
            $this->userLogRepository->createLog(
                'update',
                $oldData,
                $newData,
                Auth::id(),
                $user->id
            );

            return $user;
        }
        return null;
    }

    public function delete($id)
    {
        $user = $this->user->find($id);
        if ($user) {
            $oldData = $user->toArray(); 
            $user->delete(); 
            $this->userLogRepository->createLog(
                'delete',
                $oldData,
                null,
                Auth::id(),
                $user->id
            );

            return true;
        }
        return false;
    }

    public function login(array $credentials)
    {
        if (Auth::attempt($credentials)) {
            return Auth::user();
        }

        return null;
    }

    public function logout()
    {
        Auth::logout();
        Session::invalidate(); 
        Session::regenerateToken(); 
    }
    public function assignRole(User $user, string $roleName)
    {
        $user->assignRole($roleName);
    }

    public function revokeRole(User $user, string $roleName)
    {
        $user->revokeRole($roleName);
    }

}
