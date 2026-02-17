<?php

namespace App\Http\Controllers;

use App\Exports\UserExport;
use App\Models\CashRegister;
use App\Models\Role;
use App\Models\User;
use App\Models\UserCashRegister;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\PHPMailerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    protected $repository;
    protected $mailerService;
    public function __construct(UserRepositoryInterface $repository, PHPMailerService $mailerService)
    {
        $this->repository = $repository;
        $this->mailerService = $mailerService;
    }
    public function getUsers(Request $request, UserRepositoryInterface $repository)
    {
        try {
            $filters = [
                'title_id' => $request->input('title'),
                'role_id' => $request->input('role'),
                'status' => $request->input('status'),
                'search' => $request->input('search'),
                'archive' => $request->input('archive'),
            ];

            $perPage = $request->input('per_page', 10);
            if (filter_var($filters['archive'], FILTER_VALIDATE_BOOLEAN)) {
                $query = $repository->getUsers()->onlyTrashed();
            } else {
                $query = $repository->getUsers();
            }


            $query->with(['title:id,name', 'roles:id,name,title,description,icon', 'cashRegisters:id,name']);

            $filterableFields = ['title_id', 'status', 'role_id'];
            $searchableFields = ['full_name', 'code'];
            if (!empty($filters['search'])) {
                $searchQuery = $filters['search'];

                $query->where(function ($q) use ($searchQuery, $searchableFields) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'REGEXP', $searchQuery);
                    }
                });
            } else {
                foreach ($filterableFields as $field) {
                    if (!empty($filters[$field])) {
                        if ($field === 'status') {
                            $query->where($field, filter_var($filters[$field], FILTER_VALIDATE_BOOLEAN));
                        } else {
                            $query->where($field, $filters[$field]);
                        }
                    }
                }
            }
            $query->orderBy('created_at', 'desc');
            $users = $query->paginate($perPage);

            if (!$users) {
                throw new \Exception('Utilisateur introuvable', 404);
            }
            return response()->json([
                'status' => 200,
                'current_page' => $users->currentPage(),
                'total_users' => $users->total(),
                'per_page' => $users->perPage(),
                'users' => $users->items(),
            ], 200);
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => 'une erreur est survenue: ' . $e->getMessage(),
            ], $statusCode);
        }
    }

    public function getUserById(Request $request, $id)
    {
        $isArchived = filter_var($request->query('archive'), FILTER_VALIDATE_BOOLEAN);

        $user = $isArchived
            ? $this->repository->getUsers()->onlyTrashed()->with(['title', 'roles:id,name,description,icon', 'cashRegisters:id,name'])->find($id)
            : $this->repository->getUsers()->with(['title', 'roles:id,name,description,icon', 'cashRegisters:id,name'])->find($id);

        if ($user) {
            return response()->json(array_merge(
                $user->toArray(),
                [
                    'title' => $user->title->name ?? null,
                    'title_id' => $user->title->id ?? null,
                    'permissions' => $user->permissions,

                ]
            ), 200);
        }

        return response()->json(['message' => 'Utilisateur introuvable'], 404);
    }



    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->whereNull('deleted_at')
            ],
            'password' => 'required|min:6',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        try {
            $data = [
                'id' => Str::uuid()->toString(),
                'password' => $request->filled('password') ? bcrypt($request->password) : null,
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'full_name' => trim($request->first_name . ' ' . $request->last_name),
                'cin' => $request->cin,
                'phone' => $request->phone,
                'email' => $request->email,
                'birthdate' => $request->birthdate ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->birthdate)->format('Y-m-d') : null,
                'title_id' => $request->title_id,
                'status' => $request->status,
                'gender' => $request->gender,
                'number' => $request->number
            ];

            $data = array_map(function ($value) {
                return empty($value) && $value !== 0 ? null : $value;
            }, $data);

            $user = $this->repository->create($data);

            return response()->json([
                'status' => 200,
                'message' => 'Utilisateur créé avec succès.',
                'id' => $user->id
            ], 200);
        } catch (\Exception $e) {
            $statusCode = (is_numeric($e->getCode()) && $e->getCode() > 0) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => 'Échec de l\'insertion des données: ' . $e->getMessage(),
            ], $statusCode);
        }
    }



    public function uploadPhoto(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'nullable|image|mimes:png,jpeg,jpg|max:5048',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first(), 422);
        }

        try {
            $user = $this->repository->findById($id);

            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Utilisateur introuvable'
                ], 404);
            }

            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $filename = time() . '.' . $file->getClientOriginalExtension();
                $relativePath = 'uploads/' . $filename;
                $file->move(public_path('uploads'), $filename);

                $updated = $this->repository->update($id, ['photo' => $relativePath]);

                if ($updated) {
                    return response()->json([
                        'status' => 200,
                        'message' => 'Photo téléchargée avec succès.',
                        'photo' => url($relativePath)
                    ], 200);
                }

                throw new \Exception('Failed to update photo', 500);
            }

            throw new \Exception('No photo file found in the request', 400);
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], $statusCode);
        }
    }




    public function update(Request $request, $id)
    {
        try {
            $user = $this->repository->findById($id);
            if (!$user) {
                throw new \Exception('Utilisateur introuvable.', 404);
            }
            $userauid = Auth::user()->id;
            $userauth = $this->repository->findById($userauid);
            if (!$userauth->hasRole('user_manager')) {
                return response()->json(['message' => 'Rôle non autorisé.'], 403);
            }
            $data = $request->only([
                'last_name',
                'first_name',
                'cin',
                'phone',
                'email',
                'birthdate',
                'title_id',
                'permissions',
                'gender',
                'number'
            ]);

            $shouldLogout = false;
            if ($request->has('status')) {
                $data['status'] = $request->input('status') !== null ? (bool)$request->input('status') : null;
                if ($data['status'] === false && $id == $userauid) {
                    $shouldLogout = true;
                }
            }

            if (isset($data['code']) && $data['code'] === $user->code) {
                unset($data['code']);
            } else if (isset($data['code'])) {
                $validator = Validator::make($data, [
                    'code' => 'unique:users,code,' . $id,
                ]);
                if ($validator->fails()) {
                    return response()->json(['message' => 'Ce code est déjà utilisé par un autre utilisateur.'], 422);
                }
            }
            $newFirstName = $request->filled('first_name') ? $request->first_name : $user->first_name;
            $newLastName = $request->filled('last_name') ? $request->last_name : $user->last_name;
            $data['full_name'] = trim($newFirstName . ' ' . $newLastName);
            if ($request->filled('password')) {
                $data['password'] = bcrypt($request->password);
                $data['password_request_reset'] = 0;
            }
            foreach ($data as $key => $value) {
                if ($key !== 'status' && empty($value) && $value !== 0 && $value !== false) {
                    $data[$key] = null;
                }
            }
            Log::info('Data to update for user ' . $id . ': ' . json_encode($data));
            $updated = $this->repository->update($id, $data);

            if ($updated) {
                if ($shouldLogout) {
                    $currentUser = $request->user();

                    if ($currentUser && method_exists($currentUser, 'tokens') && $currentUser->tokens()->exists()) {
                        $currentUser->tokens()->delete();

                        return response()->json([
                            'status' => 200,
                            'message' => 'Utilisateur désactivé et tokens révoqués avec succès.',
                            'logout' => true
                        ], 200);
                    } else if (
                        $currentUser && method_exists($currentUser, 'currentAccessToken') &&
                        $currentUser->currentAccessToken() &&
                        !($currentUser->currentAccessToken() instanceof \Laravel\Sanctum\TransientToken)
                    ) {
                        $currentUser->currentAccessToken()->delete();

                        return response()->json([
                            'status' => 200,
                            'message' => 'Utilisateur désactivé et token révoqué avec succès.',
                            'logout' => true
                        ], 200);
                    } else {
                        if (method_exists(Auth::guard(), 'logout')) {
                            Auth::logout();
                            $request->session()->invalidate();
                            $request->session()->regenerateToken();

                            return response()->json([
                                'status' => 200,
                                'message' => 'Utilisateur désactivé et déconnecté avec succès.',
                                'logout' => true
                            ], 200)
                                ->withCookie(cookie()->forget('laravel_session'))
                                ->withCookie(cookie()->forget('XSRF-TOKEN'));
                        } else {
                            return response()->json([
                                'status' => 200,
                                'message' => 'Utilisateur désactivé avec succès.',
                                'logout' => true
                            ], 200);
                        }
                    }
                }

                return response()->json([
                    'status' => 200,
                    'message' => 'Utilisateur mis à jour avec succès.'
                ], 200);
            }
            throw new \Exception('Échec de la mise à jour de l utilisateur.', 500);
        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage() . ', Trace: ' . $e->getTraceAsString());

            $statusCode = $e->getCode();
            if (!is_numeric($statusCode) || $statusCode < 100 || $statusCode > 599) {
                $statusCode = 500;
            } else {
                $statusCode = (int) $statusCode;
            }

            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }


    public function delete($id)
    {
        try {
            $deleted = $this->repository->delete($id);

            if ($deleted) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Utilisateur supprimé avec succès.'
                ], 200);
            }
            throw new \Exception('utilisateur introuvable', 404);
        } catch (\Exception $e) {

            $statusCode = is_numeric($e->getCode()) ? (int) $e->getCode() : 500;
            return response()->json([
                'status' => $statusCode,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }


    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    'email' => __('Les identifiants fournis ne correspondent pas à nos enregistrements.'),
                ]);
            }

            if ($user->status !== 1) {
                throw ValidationException::withMessages([
                    'email' => __('Votre compte n\'est pas actif. Veuillez contacter l\'administrateur.'),
                ]);
            }

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                session()->regenerate();

                $rolesWithPermissions = $user->roles->map(function ($role) {
                    return [
                        'name' => $role->name,
                        'description' => $role->description,
                        'permissions' => $role->permissions->map(function ($permission) {
                            return $permission->name;
                        }),
                    ];
                });

                return response()->json([
                    'status' => 200,
                    'message' => __('Welcome!'),
                    'user' => [
                        ...collect($user),
                        'title' => $user->title->name ?? null,
                        'title_id' => $user->title->id ?? null,
                        'roles' => $rolesWithPermissions,
                        'cashregisters' => $user->cashRegisters->map(function ($cashRegisters) {
                            return [
                                'name' => $cashRegisters->name,
                            ];
                        })
                    ],
                    'hide_toast' => true,
                ], 200);
            }

            throw ValidationException::withMessages([
                'email' => __('Les identifiants fournis ne correspondent pas à nos enregistrements.'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            if (method_exists(Auth::guard(), 'logout')) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json(['message' => 'Déconnexion réussie.'])
                    ->withCookie(cookie()->forget('laravel_session'))
                    ->withCookie(cookie()->forget('XSRF-TOKEN'));
            } else {
                if ($request->user() && method_exists($request->user(), 'currentAccessToken')) {
                    $request->user()->currentAccessToken()->delete();
                }

                return response()->json(['message' => 'Déconnexion réussie.']);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Une erreur est survenue lors de la déconnexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkAuth()
    {
        try {
            $isAuthenticated = Auth::check();

            return response()->json([
                'authenticated' => $isAuthenticated,
                'message' => $isAuthenticated ? 'Utilisateur authentifié' : 'Utilisateur non authentifié'
            ], $isAuthenticated ? 200 : 401);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Une erreur est survenue lors de la vérification de l authentification.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function requestPasswordReset(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            $email = $request->input('email');

            $user = User::where('email', $email)
                ->whereNull('deleted_at')
                ->first();

            if (!$user) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Your request has been submitted. If your email exists in our system, a manager will contact you shortly.'
                ], 200);
            }

            // Update using direct query to ensure it saves
            DB::table('users')
                ->where('id', $user->id)
                ->update(['password_request_reset' => 1]);

            // Refresh the user model to get updated data
            $user->refresh();

            $managers = User::whereHas('roles', function ($query) {
                $query->where('name', 'user_manager');
            })->get();

            if ($managers->isEmpty()) {
                Log::warning('No managers found to send password reset notification', ['user_email' => $email]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Your request has been submitted. A manager will contact you shortly.'
                ], 200);
            }

            foreach ($managers as $manager) {
                $templateData = [
                    'contact' => $manager,
                    'to' => $manager->email,
                    'subject' => 'Password Reset Request',
                    'messageBody' => "User {$user->full_name} ({$user->email}) has requested a password reset. Please update their password and contact them with the new credentials.",
                    'date' => now()->format('d/m/Y'),
                    'recipientName' => $manager->first_name,
                    'recipientEmail' => $manager->email,
                    'userInfo' => $user,
                    'ctaText' => 'Gérer les utilisateurs',
                    'ctaUrl' => env('FRONTEND_URL') . "/users/{$user->id}",
                ];

                $emailHtml = view('emails.formal-red-template-passwordcheck', $templateData)->render();

                $sendResult = $this->mailerService->sendEmail(
                    $manager->email,
                    'Password Reset Request',
                    $emailHtml
                );

                if (is_array($sendResult) && $sendResult['status'] === 'success') {
                    Log::info('Password reset notification sent', [
                        'manager' => $manager->email,
                        'for_user' => $user->email
                    ]);
                } else {
                    Log::error('Failed to send password reset notification', [
                        'manager' => $manager->email,
                        'for_user' => $user->email,
                        'error' => $sendResult['message'] ?? 'Unknown error'
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Your request has been submitted. If your email exists in our system, a manager will contact you shortly.'
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error processing password reset request:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred. Please try again later.'
            ], 500);
        }
    }

    public function assignRole(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);

            $rolesToAssign = $request->input('assign', []);
            $rolesToRevoke = $request->input('revoke', []);

            $additionalAssign = [];
            $additionalRevoke = [];

            if (!empty($rolesToAssign)) {
                foreach ($rolesToAssign as $roleName) {
                    $role = Role::where('name', $roleName)->first();
                    if ($role) {
                        if (strpos($roleName, '_manager') !== false) {
                            $viewerRole = str_replace('_manager', '_viewer', $roleName);
                            if (!in_array($viewerRole, $rolesToAssign) && !$user->roles()->where('name', $viewerRole)->exists()) {
                                $additionalAssign[] = $viewerRole;
                            }
                        }

                        if (!$user->roles()->where('name', $roleName)->exists()) {
                            $user->roles()->attach($role, ['id' => Str::uuid()]);
                        }

                        $user->role_id = $role->id;
                        $user->save();
                    }
                }
            }

            foreach ($additionalAssign as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role && !$user->roles()->where('name', $roleName)->exists()) {
                    $user->roles()->attach($role, ['id' => Str::uuid()]);
                }
            }

            if (!empty($rolesToRevoke)) {
                foreach ($rolesToRevoke as $roleName) {
                    $role = Role::where('name', $roleName)->first();
                    if ($role) {
                        // When disabling viewer, also disable corresponding manager
                        if (strpos($roleName, '_viewer') !== false) {
                            $managerRole = str_replace('_viewer', '_manager', $roleName);
                            if (!in_array($managerRole, $rolesToRevoke) && $user->roles()->where('name', $managerRole)->exists()) {
                                $additionalRevoke[] = $managerRole;
                            }
                        }

                        // When disabling manager, nothing else happens
                        $user->roles()->detach($role);

                        if ($user->role_id == $role->id) {
                            $user->role_id = null;
                            $user->save();
                        }
                    }
                }
            }

            foreach ($additionalRevoke as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $user->roles()->detach($role);

                    if ($user->role_id == $role->id) {
                        $user->role_id = null;
                        $user->save();
                    }
                }
            }

            if (config('session.driver') === 'database') {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            return response()->json([
                'message' => 'Rôles mis à jour avec succès. L’utilisateur a été déconnecté.',
                'assigned' => array_merge($rolesToAssign, $additionalAssign),
                'revoked' => array_merge($rolesToRevoke, $additionalRevoke),
                'logout' => true,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur dans assignRole: ' . $e->getMessage());
            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour des rôles.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function assignUser(Request $request, $cashregisterID)
    {
        try {
            $cashRegister = CashRegister::findOrFail($cashregisterID);

            $previousAssignments = UserCashRegister::where('cash_register_id', $cashregisterID)->get();
            $previousUserIds = $previousAssignments->pluck('user_id')->toArray();

            foreach ($previousAssignments as $record) {
                $record->delete();
            }

            $userToAssign = $request->input('assign', []);
            $rolesToAssign = ['transaction_manager', 'transaction_viewer', 'cashregister_viewer'];

            foreach ($userToAssign as $userId) {
                $user = User::where('id', $userId)->first();
                if (!$user) {
                    continue;
                }

                UserCashRegister::create([
                    'id' => Str::uuid(),
                    'user_id' => $userId,
                    'cash_register_id' => $cashRegister->id,
                ]);

                foreach ($rolesToAssign as $roleName) {
                    $role = Role::where('name', $roleName)->first();
                    if ($role && !$user->roles->contains($role->id)) {
                        $user->roles()->attach($role, ['id' => Str::uuid()]);
                    }
                }
            }

            $userToRevoke = array_diff($previousUserIds, $userToAssign);
            foreach ($userToRevoke as $userId) {
                $user = User::where('id', $userId)->first();
                if (!$user) {
                    continue;
                }

                if ($user->email === 'superuser@example.com') {
                    continue;
                }

                $hasOtherCashRegisters = UserCashRegister::where('user_id', $userId)
                    ->where('cash_register_id', '!=', $cashRegister->id)
                    ->exists();

                if (!$hasOtherCashRegisters) {
                    foreach ($rolesToAssign as $roleName) {
                        $role = Role::where('name', $roleName)->first();
                        if ($role && $user->roles->contains($role->id)) {
                            $user->roles()->detach($role);
                        }
                    }
                }
            }

            return response()->json([
                'message' => 'Utilisateurs et rôles mis à jour avec succès.',
                'assigned' => $userToAssign,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la mise à jour des utilisateurs et des rôles.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function exportUsers(Request $request)
    {
        $filters = [
            'title_id' => $request->input('title'),
            'status' => $request->input('status'),
            'role_id' => $request->input('role'),
            'search' => $request->input('search'),
            'archive' => $request->input('archive'),
        ];

        $perPage = $request->input('per_page', 10);
        if (filter_var($filters['archive'], FILTER_VALIDATE_BOOLEAN)) {
            $query = $this->repository->getUsers()->onlyTrashed();
        } else {
            $query = $this->repository->getUsers();
        }

        $fileName = 'users_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        $filePath = storage_path('app/public/' . $fileName);

        $export = new UserExport($filters, $perPage);

        Excel::store($export, 'public/' . $fileName);

        $downloadUrl = asset('storage/' . $fileName);

        return response()->json([
            'status' => 200,
            'message' => 'Utilisateurs exportés avec succès',
            'download_url' => $downloadUrl,
        ], 200);
    }
}
