<?php namespace Drafterbit\Extensions\User\Models;

class User extends \Drafterbit\Framework\Model
{

    public function all($filters)
    {
        $query = $this->withQueryBuilder()->select('*')            ->from('#_users', 'u');

        $status = $filters['status'];

        if($status == 'banned') {
            $query->where('u.status = 0');
        } else if($status == 'active') {
            $query->where('u.status = 1');
        }

        return $query->getResult();
    }

    public function getBy($key, $value = null, $singleRequested=false)
    {
        $queryBuilder = $this->withQueryBuilder()->select('*')->from('#_users', 'u');

        if (is_array($key)) {
        
            foreach ($key as $k => $v) {
                $holder = ":$k";
                $queryBuilder->where("$k = $holder")
                    ->setParameter($holder, $v);
            }
        
        } else {
            
            $queryBuilder->where("$key = :$key")
                ->setParameter(":$key", $value);
        }

        $users = $queryBuilder->getResult();

        if($singleRequested) {
            return reset($users);
        }

        return $users;
    }

    public function getByEmail($email)
    {
        return $this->getBy('email', $email, true);
    }

    public function getByUserName($username)
    {
        return $this->getBy('username', $username, true);
    }

    public function getSingleBy($key, $value = null)
    {
        return $this->getBy($key, $value, true);
    }
    
    public function update($data, $where)
    {
        return $this->get('db')->update('#_users', $data, $where);
    }

    public function insert($data)
    {
        $this->get('db')->insert('#_users', $data);
        return $this->get('db')->lastInsertId();
    }

    public function delete($ids = array())
    {
        // @todo optimize this and add extension realated data
        foreach ($ids as $id) {
            $this->get('db')->delete('#_users', ['id' => $id]);
            $this->get('db')->delete('#_users_roles', ['user_id'=> $id]);
        }
    }

    public function clearRoles($id)
    {
        return $this->get('db')
            ->delete('#_users_roles', array('user_id' => $id));
    }

    public function insertRole($roleId, $userId) 
    {
        
        $data['role_id'] = $roleId;
        $data['user_id'] = $userId;

        return $this->get('db')
            ->insert('#_users_roles', $data);
    }

    public function getRoleIds($id)
    {
        $queryBuilder = $this->withQueryBuilder()
            ->select('r.id')
            ->from('#_roles', 'r')
            ->join('r', '#_users_roles', 'ur', 'ur.role_id = r.id')
            ->where('ur.user_id = :user_id')
            ->setParameter(':user_id', $id);

        $roles = $queryBuilder->getResult();

        $ids = array();
        foreach ($roles as $role) {
            $role = (object) $role;
            $ids[] = $role->id;
        }

        return $ids;
    }

    /**
     * Get user Permission by given user id
     *
     * @param  int $userId
     * @return array
     */
    public function getPermissions($userId)
    {
        $queryBuilder = $this->withQueryBuilder()
            ->select('r.permissions')
            ->from('#_users_roles', 'ur')
            ->join('ur', '#_roles', 'r', 'ur.role_id = r.id')
            ->where('ur.user_id = :user_id')
            ->setParameter(':user_id', $userId);

        $roles = $queryBuilder->getResult();

        $permissions = array();

        foreach($roles as $role) {
            if($role['permissions']) {
                $permissions = array_merge($permissions, json_decode($role['permissions'], true));
            }
        }

        return $permissions;
    }
}