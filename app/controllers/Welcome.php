<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Welcome extends Controller {
	public function index() {
		header('Content-Type: text/html; charset=utf-8');
		$frontend_url = getenv('FRONTEND_URL') ?: '';
		$frontend_link = $frontend_url !== ''
			? '<a class="button" href="' . htmlspecialchars($frontend_url, ENT_QUOTES, 'UTF-8') . '">Open the React app</a>'
			: '<p class="hint">Set <code>FRONTEND_URL</code> in Render to show a link to your React app here.</p>';
		echo '<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LavaLust Product API</title>
  <style>
    *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#f5f7f5;color:#17231d;font:16px/1.6 system-ui,-apple-system,"Segoe UI",sans-serif}.card{width:min(100%,620px);padding:40px;background:#fff;border:1px solid #e2e8e3;border-radius:18px;box-shadow:0 18px 55px #183d2910}.tag{color:#64756b;font-size:12px;font-weight:700;letter-spacing:.13em;text-transform:uppercase}h1{margin:8px 0;font-size:clamp(28px,6vw,40px);line-height:1.15}.muted{color:#64756b}.status{display:inline-flex;align-items:center;gap:8px;margin:16px 0;padding:6px 12px;border-radius:999px;background:#eaf5ed;color:#20583c;font-size:14px;font-weight:650}.dot{width:8px;height:8px;border-radius:50%;background:#26945a}.button{display:inline-block;margin:12px 0 24px;padding:11px 17px;border-radius:8px;background:#24543d;color:white;text-decoration:none;font-weight:650}.button:hover{background:#193f2d}h2{margin:18px 0 8px;font-size:16px}.routes{display:grid;gap:8px;margin:0;padding:0;list-style:none}.routes li{display:flex;gap:12px;align-items:center;padding:9px 12px;border:1px solid #edf0ed;border-radius:8px}.method{min-width:62px;color:#24543d;font:700 12px ui-monospace,monospace}.path{font:13px ui-monospace,monospace}.hint{color:#64756b;font-size:14px}code{padding:2px 5px;border-radius:4px;background:#f0f3f0}@media(max-width:480px){.card{padding:26px 20px}}
  </style>
</head>
<body>
  <main class="card">
    <div class="tag">LavaLust · Backend service</div>
    <h1>Product API</h1>
    <p class="muted">The API is online and ready to serve the Stockroom React application.</p>
    <div class="status"><span class="dot"></span> API is running</div><br>
    ' . $frontend_link . '
    <h2>Available endpoints</h2>
    <ul class="routes">
      <li><span class="method">POST</span><span class="path">/api/register</span></li>
      <li><span class="method">POST</span><span class="path">/api/login</span></li>
      <li><span class="method">POST</span><span class="path">/api/logout</span></li>
      <li><span class="method">GET</span><span class="path">/api/products</span></li>
      <li><span class="method">GET</span><span class="path">/api/products/{id}</span></li>
      <li><span class="method">POST</span><span class="path">/api/products</span></li>
      <li><span class="method">PUT</span><span class="path">/api/products/{id}</span></li>
      <li><span class="method">DELETE</span><span class="path">/api/products/{id}</span></li>
    </ul>
  </main>
</body>
</html>';
	}
}
?>
