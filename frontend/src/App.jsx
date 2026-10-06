import { useEffect, useMemo, useState } from 'react'

const API = (import.meta.env.VITE_API_URL || '').replace(/\/$/, '')
const read = (key, fallback = '') => { const value = localStorage.getItem(key); if (value === null) return fallback; try { return JSON.parse(value) } catch { return value } }

async function request(path, { token, ...options } = {}) {
  const response = await fetch(`${API}${path}`, { ...options, headers: { 'Content-Type': 'application/json', ...(token ? { Authorization: `Bearer ${token}` } : {}), ...options.headers }, body: options.body ? JSON.stringify(options.body) : undefined })
  const responseText = await response.text()
  let data
  try { data = responseText ? JSON.parse(responseText) : {} } catch {
    throw new Error(`The API returned an HTML error (HTTP ${response.status}). Check the Render API logs and its DB_* and JWT_SECRET/REFRESH_TOKEN_KEY environment variables.`)
  }
  if (!response.ok) {
    const fallback = response.status >= 500
      ? 'LavaLust could not complete the request. Check the API server and database connection.'
      : `Request failed (${response.status})`
    throw new Error(data.error || data.message || fallback)
  }
  return data
}

const emptyForm = { product_name: '', description: '', price: '', quantity: '' }

export default function App() {
  const [token, setToken] = useState(() => read('ll_token'))
  const [user, setUser] = useState(() => read('ll_user', null))
  const [products, setProducts] = useState([])
  const [form, setForm] = useState(emptyForm)
  const [editing, setEditing] = useState(null)
  const [login, setLogin] = useState({ username: '', password: '' })
  const [authMode, setAuthMode] = useState('login')
  const [signup, setSignup] = useState({ username: '', email: '', password: '', confirmPassword: '' })
  const [query, setQuery] = useState('')
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')
  const [visiblePasswords, setVisiblePasswords] = useState({})
  const visible = useMemo(() => products.filter(p => `${p.product_name} ${p.description || ''}`.toLowerCase().includes(query.toLowerCase())), [products, query])

  async function loadProducts(activeToken = token) { const data = await request('/api/products', { token: activeToken }); setProducts(data.products || []) }
  useEffect(() => { if (token) loadProducts().catch(() => { localStorage.removeItem('ll_token'); setToken(''); setUser(null) }) }, [])
  function signOut() {
    const refreshToken = read('ll_refresh')
    if (token) request('/api/logout', { token, method: 'POST', body: { refresh_token: refreshToken } }).catch(() => {})
    ;['ll_token', 'll_refresh', 'll_user'].forEach(k => localStorage.removeItem(k))
    setToken(''); setUser(null); setProducts([]); setForm(emptyForm); setEditing(null)
    setLogin({ username: '', password: '' }); setError(''); setNotice('You have been signed out.')
  }
  async function submitLogin(event) {
    event.preventDefault(); setBusy(true); setError(''); setNotice('')
    try {
      const data = await request('/api/login', { method: 'POST', body: { identifier: login.username, password: login.password } })
      const access = data.token || data.access_token
      localStorage.setItem('ll_token', access); localStorage.setItem('ll_refresh', data.refresh_token || ''); localStorage.setItem('ll_user', JSON.stringify(data.user))
      setToken(access); setUser(data.user); await loadProducts(access)
    } catch (e) { setError(e.message) } finally { setBusy(false) }
  }
  async function submitSignup(event) {
    event.preventDefault(); setBusy(true); setError(''); setNotice('')
    if (signup.password !== signup.confirmPassword) { setError('Passwords do not match.'); setBusy(false); return }
    try {
      const data = await request('/api/register', { method: 'POST', body: { username: signup.username, email: signup.email, password: signup.password } })
      setLogin({ username: signup.email, password: '' })
      setSignup({ username: '', email: '', password: '', confirmPassword: '' })
      setAuthMode('login')
      setNotice(data.message || 'Account created. Sign in with your new account.')
    } catch (e) {
      if (e.message.toLowerCase().includes('already registered')) {
        setLogin({ username: signup.email, password: '' })
        setAuthMode('login')
        setNotice('An account already uses these details. Sign in with your email or username.')
      } else setError(e.message)
    } finally { setBusy(false) }
  }
  async function submitProduct(event) {
    event.preventDefault(); setBusy(true); setError(''); setNotice('')
    const payload = { ...form, price: Number(form.price), quantity: Number(form.quantity) }
    try {
      await request(editing ? `/api/products/${editing}` : '/api/products', { token, method: editing ? 'PUT' : 'POST', body: payload })
      setNotice(editing ? 'Product updated.' : 'Product added.'); setForm(emptyForm); setEditing(null); await loadProducts()
    } catch (e) { setError(e.message) } finally { setBusy(false) }
  }
  function editProduct(p) { setEditing(p.id); setForm({ product_name: p.product_name || '', description: p.description || '', price: p.price ?? '', quantity: p.quantity ?? '' }); window.scrollTo({ top: 0, behavior: 'smooth' }) }
  async function deleteProduct(p) {
    if (!window.confirm(`Delete “${p.product_name}”? This cannot be undone.`)) return
    setError(''); setNotice('')
    try { await request(`/api/products/${p.id}`, { token, method: 'DELETE' }); setNotice('Product deleted.'); await loadProducts() } catch (e) { setError(e.message) }
  }

  if (!token) return <main className="login-wrap"><section className="login-card">
    <div className="brand"><span className="brand-mark">S</span><span>stockroom</span></div><p className="eyebrow">PRODUCT MANAGEMENT</p><h1>{authMode === 'login' ? 'Welcome back.' : 'Create your account.'}</h1><p className="muted">{authMode === 'login' ? 'Sign in to manage your inventory.' : 'Sign up to start managing your inventory.'}</p>
    {error && <div className="alert error">{error}</div>}
    {notice && <div className="alert success">{notice}</div>}
    {authMode === 'login' ? <form onSubmit={submitLogin} className="stack"><label>Email or username<input autoComplete="username" value={login.username} onChange={e => setLogin({ ...login, username: e.target.value })} required /></label><label>Password<div className="password-field"><input type={visiblePasswords.login ? 'text' : 'password'} autoComplete="current-password" value={login.password} onChange={e => setLogin({ ...login, password: e.target.value })} required /><button className="password-toggle" type="button" aria-label={visiblePasswords.login ? 'Hide password' : 'Show password'} aria-pressed={!!visiblePasswords.login} onClick={() => setVisiblePasswords({ ...visiblePasswords, login: !visiblePasswords.login })}>{visiblePasswords.login ? 'Hide' : 'Show'}</button></div></label><button className="primary full" disabled={busy}>{busy ? 'Signing in…' : 'Sign in'} <span>→</span></button></form> : <form onSubmit={submitSignup} className="stack"><label>Username<input autoComplete="username" minLength="3" maxLength="100" value={signup.username} onChange={e => setSignup({ ...signup, username: e.target.value })} required /></label><label>Email<input type="email" autoComplete="email" maxLength="255" value={signup.email} onChange={e => setSignup({ ...signup, email: e.target.value })} required /></label><label>Password<div className="password-field"><input type={visiblePasswords.signup ? 'text' : 'password'} autoComplete="new-password" minLength="8" maxLength="72" value={signup.password} onChange={e => setSignup({ ...signup, password: e.target.value })} required /><button className="password-toggle" type="button" aria-label={visiblePasswords.signup ? 'Hide password' : 'Show password'} aria-pressed={!!visiblePasswords.signup} onClick={() => setVisiblePasswords({ ...visiblePasswords, signup: !visiblePasswords.signup })}>{visiblePasswords.signup ? 'Hide' : 'Show'}</button></div><span className="field-hint">Use at least 8 characters.</span></label><label>Confirm password<div className="password-field"><input type={visiblePasswords.confirm ? 'text' : 'password'} autoComplete="new-password" minLength="8" maxLength="72" value={signup.confirmPassword} onChange={e => setSignup({ ...signup, confirmPassword: e.target.value })} required /><button className="password-toggle" type="button" aria-label={visiblePasswords.confirm ? 'Hide password' : 'Show password'} aria-pressed={!!visiblePasswords.confirm} onClick={() => setVisiblePasswords({ ...visiblePasswords, confirm: !visiblePasswords.confirm })}>{visiblePasswords.confirm ? 'Hide' : 'Show'}</button></div></label><button className="primary full" disabled={busy}>{busy ? 'Creating account…' : 'Create account'} <span>→</span></button></form>}
    <p className="auth-switch">{authMode === 'login' ? 'Don’t have an account?' : 'Already have an account?'} <button type="button" onClick={() => { setAuthMode(authMode === 'login' ? 'signup' : 'login'); setError('') }}>{authMode === 'login' ? 'Create account' : 'Sign in'}</button></p>
  </section></main>

  return <div className="app-shell"><aside className="sidebar"><div className="brand"><span className="brand-mark">S</span><span>stockroom</span></div><div className="side-label">WORKSPACE</div><div className="nav-item active"><span>▦</span> Products</div>
    <div className="sidebar-bottom"><div className="avatar">{(user?.username || 'U').slice(0, 1).toUpperCase()}</div><div className="user-label"><strong>{user?.username || 'User'}</strong><span>{user?.role || 'Account'}</span></div><button className="icon-button" title="Log out" onClick={signOut}>↗</button></div></aside>
    <main className="main"><header className="page-header"><div><p className="eyebrow">INVENTORY</p><h1>Products</h1><p className="muted">Keep track of what you have in stock.</p></div><div className="header-count"><strong>{products.length}</strong><span>total items</span></div></header>
      {(error || notice) && <div className={`alert ${error ? 'error' : 'success'}`}>{error || notice}<button onClick={() => { setError(''); setNotice('') }}>×</button></div>}
      <section className="content-grid"><article className="panel form-panel"><div className="panel-heading"><div><p className="eyebrow">{editing ? 'UPDATE ITEM' : 'NEW ITEM'}</p><h2>{editing ? 'Edit product' : 'Add a product'}</h2></div>{editing && <button className="text-button" onClick={() => { setEditing(null); setForm(emptyForm) }}>Cancel</button>}</div>
        <form className="stack" onSubmit={submitProduct}><label>Product name<input maxLength="100" placeholder="e.g. Ceramic mug" value={form.product_name} onChange={e => setForm({ ...form, product_name: e.target.value })} required /></label><label>Description <span className="optional">Optional</span><textarea rows="3" placeholder="A short description" value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} /></label><div className="two-col"><label>Price <span className="input-prefix">₱</span><input type="number" min="0" step="0.01" placeholder="0.00" value={form.price} onChange={e => setForm({ ...form, price: e.target.value })} required /></label><label>Quantity<input type="number" min="0" step="1" placeholder="0" value={form.quantity} onChange={e => setForm({ ...form, quantity: e.target.value })} required /></label></div><button className="primary" disabled={busy}>{busy ? 'Saving…' : editing ? 'Save changes' : 'Add product'} <span>→</span></button></form></article>
        <article className="panel table-panel"><div className="panel-heading list-heading"><div><p className="eyebrow">CATALOG</p><h2>All products <span className="count-pill">{visible.length}</span></h2></div><div className="search"><span>⌕</span><input aria-label="Search products" placeholder="Search products" value={query} onChange={e => setQuery(e.target.value)} /></div></div>
          <div className="table-scroll"><table><thead><tr><th>PRODUCT</th><th>PRICE</th><th>IN STOCK</th><th></th></tr></thead><tbody>{visible.map(p => <tr key={p.id}><td><div className="product-title">{p.product_name}</div><div className="product-description">{p.description || 'No description'}</div></td><td className="price">₱{Number(p.price || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td><td><span className={`stock ${Number(p.quantity) === 0 ? 'out' : Number(p.quantity) < 5 ? 'low' : ''}`}><i />{p.quantity} units</span></td><td><div className="row-actions"><button onClick={() => editProduct(p)}>Edit</button><button className="delete" onClick={() => deleteProduct(p)}>Delete</button></div></td></tr>)}{!visible.length && <tr><td colSpan="4"><div className="empty"><span>▦</span><strong>{query ? 'No matching products' : 'Your catalog is empty'}</strong><p>{query ? 'Try a different search.' : 'Add your first product using the form.'}</p></div></td></tr>}</tbody></table></div></article></section><footer>Stockroom <span>•</span> Powered by LavaLust API</footer>
    </main></div>
}
