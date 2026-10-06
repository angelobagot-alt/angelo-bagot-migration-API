<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Welcome extends Controller {
	public function index() {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'name' => 'LavaLust Product API',
			'status' => 'ok',
			'endpoints' => [
				'POST /api/register',
				'POST /api/login',
				'POST /api/logout',
				'GET /api/products',
				'GET /api/products/{id}',
				'POST /api/products',
				'PUT /api/products/{id}',
				'DELETE /api/products/{id}',
			],
		], JSON_UNESCAPED_SLASHES);
	}
}
?>
