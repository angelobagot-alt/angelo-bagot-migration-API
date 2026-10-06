<?php

defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Product_model extends Model
{
    protected $table = 'products';
    protected $primary_key = 'id';
    protected $timestamps = true;
    protected $created_at_column = 'created_at';
    protected $updated_at_column = 'updated_at';

    public function getAll()
    {
        return $this->db->table($this->table)
            ->order_by('id', 'DESC')
            ->get_all() ?: [];
    }

    public function findById($id)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->limit(1)
            ->get();
    }
}
