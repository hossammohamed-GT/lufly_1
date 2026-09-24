<?php

declare(strict_types=1);

namespace Modules\Users\Services;

use App\Services\ActivityLogger;
use Core\Database\Paginator;
use Core\Exceptions\NotFoundException;
use Modules\Users\Models\User;
use Modules\Users\Repositories\UserRepository;

class UserService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ActivityLogger $activity,
    ) {
    }

    public function create(array $data, array $roleIds = []): User
    {
        $data['password'] = password_hash((string) $data['password'], PASSWORD_BCRYPT, [
            'cost' => (int) config('security.bcrypt_cost', 12),
        ]);

        $user = $this->users->create($data);

        if ($roleIds !== []) {
            $this->users->syncRoles($user->id, $roleIds);
        }

        $this->activity->created('user', $user->id, ['email' => $data['email'] ?? '']);

        return $user;
    }

    public function update(int $id, array $data, ?array $roleIds = null): User
    {
        $user = $this->find($id);

        if (isset($data['password']) && $data['password'] !== '') {
            $data['password'] = password_hash((string) $data['password'], PASSWORD_BCRYPT, [
                'cost' => (int) config('security.bcrypt_cost', 12),
            ]);
        } else {
            unset($data['password']);
        }

        $this->users->update($id, $data);

        if ($roleIds !== null) {
            $this->users->syncRoles($id, $roleIds);
        }

        $this->activity->updated('user', $id);

        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $this->users->delete($id);
        $this->activity->deleted('user', $id);
    }

    public function paginate(array $filters = [], int $page = 1, int $perPage = 10): Paginator
    {
        return $this->users->paginate($filters, $page, $perPage);
    }

    public function find(int $id): User
    {
        $user = $this->users->find($id);
        if ($user === null) {
            throw new NotFoundException(trans('errors.user_not_found'));
        }

        return $user;
    }
}
