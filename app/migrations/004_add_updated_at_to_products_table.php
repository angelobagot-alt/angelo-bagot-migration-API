<?php

class Add_updated_at_to_products_table
{
    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->database();
    }

    public function up()
    {
        $table = $this->_lava->db->raw("SHOW TABLES LIKE 'products'");
        if (!$table || !$table->fetch(PDO::FETCH_NUM)) {
            return;
        }

        $column = $this->_lava->db->raw("SHOW COLUMNS FROM `products` LIKE 'updated_at'");
        if (!$column || !$column->fetch(PDO::FETCH_ASSOC)) {
            $this->_lava->db->raw(
                'ALTER TABLE `products` ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL'
            );
        }
    }

    public function down()
    {
        $column = $this->_lava->db->raw("SHOW COLUMNS FROM `products` LIKE 'updated_at'");
        if ($column && $column->fetch(PDO::FETCH_ASSOC)) {
            $this->_lava->db->raw('ALTER TABLE `products` DROP COLUMN `updated_at`');
        }
    }
}
