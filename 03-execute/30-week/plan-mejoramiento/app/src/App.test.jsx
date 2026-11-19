import React from 'react';
import { afterEach, expect, it, vi } from 'vitest';
import { cleanup, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import App from './App.jsx';

afterEach(() => { cleanup(); vi.unstubAllGlobals(); });

it('logs in with entered credentials and clears the session on logout', async () => {
  const fetch = vi.fn().mockResolvedValue({ ok: true, status: 200, json: async () => ({ access_token: 'test-token', role: 'admin', expires_in: 28800 }) });
  vi.stubGlobal('fetch', fetch);
  const user = userEvent.setup();
  render(<App />);
  await user.type(screen.getByLabelText('Usuario'), 'admin');
  await user.type(screen.getByLabelText('Contraseña'), 'test-password');
  await user.click(screen.getByRole('button', { name: 'Ingresar' }));
  expect(await screen.findByText('administrador')).toBeTruthy();
  expect(JSON.parse(fetch.mock.calls[0][1].body)).toEqual({ username: 'admin', password: 'test-password' });
  expect(localStorage.length).toBe(0);
  await user.click(screen.getByRole('button', { name: 'Cerrar sesión' }));
  expect(screen.getByLabelText('Contraseña').value).toBe('');
});

it('shows the server error without leaving the login form', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false, status: 401, json: async () => ({ message: 'Credenciales incorrectas.', code: 'INVALID_CREDENTIALS', details: [] }) }));
  const user = userEvent.setup();
  render(<App />);
  await user.type(screen.getByLabelText('Usuario'), 'admin');
  await user.type(screen.getByLabelText('Contraseña'), 'wrong');
  await user.click(screen.getByRole('button', { name: 'Ingresar' }));
  expect((await screen.findByRole('alert')).textContent).toBe('Credenciales incorrectas.');
  expect(screen.getByRole('button', { name: 'Ingresar' }).disabled).toBe(false);
});
