<?php

defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Product extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->call->library('api');
        $this->call->database();
    }

    public function index()
    {
        $this->require_auth();

        $this->call->model('Product_model');
        $products = $this->Product_model->getAll();

        $this->api->respond([
            'products' => $products,
            'count' => count($products),
        ], 200);
    }

    public function show($id)
    {
        $this->require_auth();

        $this->call->model('Product_model');
        $product = $this->Product_model->findById($id);

        if (!$product) {
            $this->api->respond_error('Product not found.', 404);
        }

        $this->api->respond(['product' => $product], 200);
    }

    public function store()
    {
        $this->require_auth();
        $this->api->require_method('POST');

        $input = $this->api->body();
        $product_name = trim((string) ($input['product_name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $price = $input['price'] ?? 0;
        $quantity = $input['quantity'] ?? 0;

        if ($product_name === '') {
            $this->api->respond_error('Product name is required.', 400);
        }

        if (!is_numeric($price) || (float) $price < 0) {
            $this->api->respond_error('Price must be a valid number.', 400);
        }

        if (!is_numeric($quantity) || (int) $quantity < 0) {
            $this->api->respond_error('Quantity must be a valid number.', 400);
        }

        $now = date('Y-m-d H:i:s');
        $product_id = $this->db->table('products')->insert([
            'product_name' => $product_name,
            'description' => $description,
            'price' => number_format((float) $price, 2, '.', ''),
            'quantity' => (int) $quantity,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $product = $this->db->table('products')->where('id', $product_id)->limit(1)->get();

        $this->api->respond([
            'message' => 'Product created successfully.',
            'product' => $product,
        ], 201);
    }

    public function update($id)
    {
        $this->require_auth();
        $this->api->require_method('PUT');

        $input = $this->api->body();
        $product_name = trim((string) ($input['product_name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $price = $input['price'] ?? null;
        $quantity = $input['quantity'] ?? null;

        $existing = $this->db->table('products')->where('id', $id)->limit(1)->get();
        if (!$existing) {
            $this->api->respond_error('Product not found.', 404);
        }

        $data = [];
        if ($product_name !== '') {
            $data['product_name'] = $product_name;
        }
        if (array_key_exists('description', $input)) {
            $data['description'] = $description;
        }
        if ($price !== null && (!is_numeric($price) || (float) $price < 0)) {
            $this->api->respond_error('Price must be a valid number.', 400);
        }
        if ($price !== null) {
            $data['price'] = number_format((float) $price, 2, '.', '');
        }
        if ($quantity !== null && (!is_numeric($quantity) || (int) $quantity < 0)) {
            $this->api->respond_error('Quantity must be a valid number.', 400);
        }
        if ($quantity !== null) {
            $data['quantity'] = (int) $quantity;
        }

        if (empty($data)) {
            $this->api->respond_error('No valid fields were provided for update.', 400);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        $updated = $this->db->table('products')->where('id', $id)->update($data);

        if ($updated === false || $updated === 0) {
            $this->api->respond_error('Unable to update product.', 500);
        }

        $product = $this->db->table('products')->where('id', $id)->limit(1)->get();

        $this->api->respond([
            'message' => 'Product updated successfully.',
            'product' => $product,
        ], 200);
    }

    public function destroy($id)
    {
        $this->require_auth();
        $this->api->require_method('DELETE');

        $existing = $this->db->table('products')->where('id', $id)->limit(1)->get();
        if (!$existing) {
            $this->api->respond_error('Product not found.', 404);
        }

        $deleted = $this->db->table('products')->where('id', $id)->delete();
        if ($deleted === false || $deleted === 0) {
            $this->api->respond_error('Unable to delete product.', 500);
        }

        $this->api->respond([
            'message' => 'Product deleted successfully.',
            'id' => (int) $id,
        ], 200);
    }

    private function require_auth()
    {
        $token = $this->api->get_bearer_token();
        if (!$token) {
            $this->api->respond_error('Unauthorized. Please log in first.', 401);
        }

        $payload = $this->api->validate_jwt($token, 'access');
        if (!$payload) {
            $this->api->respond_error('Unauthorized. Invalid or expired token.', 401);
        }

        return $payload;
    }
}
