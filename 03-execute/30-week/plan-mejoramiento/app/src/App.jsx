import React, { useEffect, useState } from 'react';
import { request } from './api.js';
import Workspace from './Workspace.jsx';

export default function App() {
  const [session, setSession] = useState(null);
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  useEffect(() => {
    if (!session) return;
    const timeout = setTimeout(() => {
      setSession(null);
      setError('Tu sesión ha vencido. Inicia sesión nuevamente.');
    }, Math.max(0, session.expiresAt - Date.now()));
    return () => clearTimeout(timeout);
  }, [session]);

  async function login(event) {
    event.preventDefault();
    setBusy(true);
    setError('');
    try {
      const result = await request('/auth/login', { method: 'POST', body: { username, password } });
      setSession({ token: result.access_token, role: result.role, expiresAt: Date.now() + result.expires_in * 1000 });
      setPassword('');
    } catch (failure) {
      setError(failure.message);
    } finally { setBusy(false); }
  }

  return <div className="shell">
    <header className="brand"><span className="brand-mark" aria-hidden="true">L</span><span>Laboratorio<small>Préstamo de equipos</small></span></header>
    {session ? <Workspace session={session} onLogout={() => { setSession(null); setPassword(''); setError(''); }} /> : (
    <main>
      <section className="intro"><p className="eyebrow">EQUIPOS A TU ALCANCE</p><h1>Todo listo para<br />tu próxima práctica.</h1><p>Consulta los equipos del laboratorio y lleva el control de cada préstamo y devolución.</p>
        <div className="rules"><div><strong>2 equipos</strong><span>por investigador</span></div><div><strong>5 días</strong><span>por préstamo</span></div></div>
        <p className="note">Una devolución a tiempo permite que más personas usen los equipos.</p>
      </section>
      <section className="panel" aria-labelledby="panel-title">
          <h2 id="panel-title">Ingresa a tu cuenta</h2><p>Acceso para administradores y laboratoristas.</p>
          <form onSubmit={login}>
            <label htmlFor="username">Usuario</label><input id="username" name="username" autoComplete="username" required maxLength={120} value={username} onChange={event => setUsername(event.target.value)} />
            <label htmlFor="password">Contraseña</label><input id="password" name="password" type="password" autoComplete="current-password" required maxLength={128} value={password} onChange={event => setPassword(event.target.value)} />
            {error && <p role="alert" className="error">{error}</p>}
            <button disabled={busy} type="submit">{busy ? 'Ingresando…' : 'Ingresar'}</button>
          </form>
          <p className="help">Si necesitas acceso, comunícate con el administrador del laboratorio.</p>
      </section>
    </main>
    )}
    <footer>Préstamo responsable · Equipos disponibles para aprender</footer>
  </div>;
}
