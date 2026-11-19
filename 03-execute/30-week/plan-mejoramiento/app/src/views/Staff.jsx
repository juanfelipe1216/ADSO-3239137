import React, { useEffect, useRef, useState } from 'react';
import { request } from '../api.js';
import { ErrorNote, Loading, Notice } from '../ui.jsx';

export default function Staff({ token, role }) {
  return role === 'admin' ? <StaffForm key={token} token={token} />
    : <ErrorNote error={{ message: 'Solo el administrador puede crear laboratoristas.' }} />;
}

function StaffForm({ token }) {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState(null);
  const [created, setCreated] = useState('');
  const pending = useRef(null);
  useEffect(() => () => pending.current?.abort(), []);

  async function submit(event) {
    event.preventDefault();
    if (pending.current) return;
    const controller = new AbortController();
    pending.current = controller;
    setBusy(true); setError(null); setCreated('');
    const body = { username, password, role: 'lab_technician' };
    setPassword('');
    try {
      const user = await request('/users', { token, method: 'POST', body, signal: controller.signal });
      if (!controller.signal.aborted) { setCreated(user.username); setUsername(''); }
    } catch (failure) {
      if (!controller.signal.aborted) setError(failure);
    } finally {
      pending.current = null;
      if (!controller.signal.aborted) setBusy(false);
    }
  }

  return <section className="panel">
    <h2>Laboratoristas</h2>
    <p>Crear una cuenta con acceso de laboratorista.</p>
    <Notice>{created && `Laboratorista creado: ${created}.`}</Notice>
    <ErrorNote error={error} />
    <form onSubmit={submit}>
      <label htmlFor="staff-username">Usuario</label>
      <input id="staff-username" autoComplete="off" required minLength={1} maxLength={120}
        value={username} disabled={busy} onChange={event => setUsername(event.target.value)} />
      <label htmlFor="staff-password">Contraseña</label>
      <input id="staff-password" type="password" autoComplete="new-password" required minLength={12}
        aria-describedby="staff-password-help" value={password} disabled={busy}
        onChange={event => setPassword(event.target.value)} />
      <p id="staff-password-help" className="state">Entre 12 y 128 caracteres. Se conservan los espacios.</p>
      <button type="submit" disabled={busy || password.length < 12 || password.length > 128}>Crear laboratorista</button>
      {busy && <Loading>Creando laboratorista…</Loading>}
    </form>
  </section>;
}
