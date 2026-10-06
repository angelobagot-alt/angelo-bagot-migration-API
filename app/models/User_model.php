<?php

defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class User_model extends Model
{
    protected $table = 'users';

    public function findByUsernameOrEmail($identifier)
    {
        $identifier = trim((string) $identifier);
        if ($identifier === '') {
            return null;
        }

        $stmt = $this->db->raw(
            'SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$identifier, $identifier]
        );

        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        return $row ?: null;
    }

}
