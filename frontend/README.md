# Stockroom React client

This React/Vite client uses the LavaLust API for authentication and product data. It never connects directly to MySQL. The API root returns its name, status, and available endpoints. Set `VITE_API_URL` on the React deployment to the API's base URL.

## Run locally

1. Copy `.env.example` to `.env`. Leave `VITE_API_URL` blank locally; Vite proxies `/api` requests to `http://127.0.0.1:3002` to avoid browser cross-origin errors.
2. Install dependencies with `npm install`.
3. Start the client with `npm run dev`.

For deployment, set `VITE_API_URL` to the deployed LavaLust API base URL and build with `npm run build`. Publish the generated `dist` directory as a static site. Set `allow_origin` in `app/config/api.php` to the exact frontend origin before production deployment.

Create your own account from the sign-in screen. Passwords are hashed by the API before they are stored.
