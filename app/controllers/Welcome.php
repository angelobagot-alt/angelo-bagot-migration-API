<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Welcome extends Controller {
	public function index() {
		$frontend_url = getenv('FRONTEND_URL') ?: 'http://127.0.0.1:5173/';
		header('Location: ' . $frontend_url, TRUE, 302);
		exit;
	}
}
?>
