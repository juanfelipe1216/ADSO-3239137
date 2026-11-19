import React from 'react';
import { afterEach, expect, it, vi } from 'vitest';
import { cleanup, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Researchers from './Researchers.jsx';
import Loans from './Loans.jsx';
import Overdue from './Overdue.jsx';
import Report from './Report.jsx';

afterEach(() => { cleanup(); vi.unstubAllGlobals(); });

function reply(status, body) { return { ok: status < 400, status, json: async () => body }; }

// routes: { 'GET /path?query-prefix': body | (call) => response }
function stubApi(routes) {
  const fetch = vi.fn(async (url, options = {}) => {
    const key = `${options.method ?? 'GET'} ${String(url).replace('/api/v1', '')}`;
    const route = Object.keys(routes).find(pattern => key === pattern || key.startsWith(`${pattern}?`));
    if (!route) return reply(404, { code: 'NOT_FOUND', message: `Sin ruta de prueba: ${key}`, details: [] });
    const value = routes[route];
    return typeof value === 'function' ? value(options) : reply(200, value);
  });
  vi.stubGlobal('fetch', fetch);
  return fetch;
}

const page = items => ({ items, page: 1, size: 10, total: items.length });
const researcher = { id: 'r1', full_name: 'Ana Pérez', document_number: '123' };
const line = { id: 'i1', equipment_id: 'e1', equipment_name: 'Multímetro', reference: 'LAB-1', category_name: 'electrónica', due_at: '2026-11-17T23:00:00Z', returned_at: null };
const loan = { id: 'l1', researcher_id: 'r1', loaned_at: '2026-11-12T23:00:00Z', status: 'open', items: [line] };

it('searches researchers with the typed term, shows pending loans and registers a new one', async () => {
  const fetch = stubApi({
    'GET /researchers': page([researcher]),
    'GET /researchers/r1': { ...researcher, active_loans: 2 },
    'POST /researchers': options => reply(201, { id: 'r2', ...JSON.parse(options.body) }),
  });
  const user = userEvent.setup();
  render(<Researchers token="t" />);
  expect(await screen.findByText('Ana Pérez')).toBeTruthy();
  await user.type(screen.getByLabelText('Buscar por nombre o documento'), 'ana');
  await user.click(screen.getByRole('button', { name: 'Buscar' }));
  await waitFor(() => expect(fetch.mock.calls.some(([url]) => String(url).includes('q=ana'))).toBe(true));
  await user.click(screen.getByRole('button', { name: 'Ver pendientes de Ana Pérez' }));
  expect(await screen.findByText('Equipos pendientes por devolver: 2.')).toBeTruthy();
  await user.type(screen.getByLabelText('Nombre completo'), '  Luis Gómez ');
  await user.type(screen.getByLabelText('Documento'), '999');
  await user.click(screen.getByRole('button', { name: 'Registrar' }));
  expect(await screen.findByText('Investigador registrado: Luis Gómez.')).toBeTruthy();
  const post = fetch.mock.calls.find(([, options]) => options?.method === 'POST');
  expect(JSON.parse(post[1].body)).toEqual({ full_name: 'Luis Gómez', document_number: '999' });
  expect(post[1].headers.Authorization).toBe('Bearer t');
});

it('shows the duplicate-document conflict from the server', async () => {
  stubApi({
    'GET /researchers': page([]),
    'POST /researchers': () => reply(409, { code: 'DOCUMENT_TAKEN', message: 'El documento ya está registrado.', details: [] }),
  });
  const user = userEvent.setup();
  render(<Researchers token="t" />);
  expect(await screen.findByText('Aún no hay investigadores registrados.')).toBeTruthy();
  await user.type(screen.getByLabelText('Nombre completo'), 'Ana');
  await user.type(screen.getByLabelText('Documento'), '123');
  await user.click(screen.getByRole('button', { name: 'Registrar' }));
  expect((await screen.findByRole('alert')).textContent).toContain('El documento ya está registrado.');
});

it('registers a loan with at most two available items and refreshes the history', async () => {
  const equipment = [
    { id: 'e1', name: 'Multímetro', reference: 'LAB-1', available_units: 2 },
    { id: 'e2', name: 'Osciloscopio', reference: 'LAB-2', available_units: 0 },
    { id: 'e3', name: 'Fuente', reference: 'LAB-3', available_units: 1 },
    { id: 'e4', name: 'Pinza', reference: 'LAB-4', available_units: 1 },
  ];
  const fetch = stubApi({
    'GET /loans': page([]),
    'GET /researchers': page([researcher]),
    'GET /equipment': page(equipment),
    'POST /loans': () => reply(201, loan),
  });
  const user = userEvent.setup();
  render(<Loans token="t" />);
  expect(await screen.findByText('No hay préstamos con estos filtros.')).toBeTruthy();
  const submit = screen.getByRole('button', { name: 'Registrar préstamo' });
  expect(submit.disabled).toBe(true);

  await user.type(screen.getByLabelText('Investigador'), 'ana');
  await user.click(await screen.findByRole('button', { name: /Ana Pérez · 123/ }));
  await user.type(screen.getByLabelText(/Equipos/), 'a');
  const sinStock = await screen.findByRole('button', { name: /Osciloscopio/ });
  expect(sinStock.disabled).toBe(true);
  await user.click(screen.getByRole('button', { name: /Multímetro/ }));
  await user.type(screen.getByLabelText(/Equipos/), 'a');
  await user.click(await screen.findByRole('button', { name: /Fuente/ }));
  await user.type(screen.getByLabelText(/Equipos/), 'a');
  expect((await screen.findByRole('button', { name: /Pinza/ })).disabled).toBe(true);
  await user.click(screen.getByRole('button', { name: 'Registrar préstamo' }));

  expect(await screen.findByText('Préstamo registrado para Ana Pérez.')).toBeTruthy();
  // The success message must not linger once a new loan is started.
  await user.type(screen.getByLabelText('Investigador'), 'ana');
  await user.click(await screen.findByRole('button', { name: /Ana Pérez · 123/ }));
  expect(screen.queryByText('Préstamo registrado para Ana Pérez.')).toBeNull();
  const post = fetch.mock.calls.find(([url, options]) => String(url).endsWith('/loans') && options?.method === 'POST');
  expect(JSON.parse(post[1].body)).toEqual({ researcher_id: 'r1', items: [{ equipment_id: 'e1' }, { equipment_id: 'e3' }] });
});

it('shows the business error when the loan is rejected', async () => {
  stubApi({
    'GET /loans': page([]),
    'GET /researchers': page([researcher]),
    'GET /equipment': page([{ id: 'e1', name: 'Multímetro', reference: 'LAB-1', available_units: 1 }]),
    'POST /loans': () => reply(409, { code: 'RESEARCHER_HAS_OVERDUE', message: 'El investigador tiene equipos vencidos.', details: [] }),
  });
  const user = userEvent.setup();
  render(<Loans token="t" />);
  await screen.findByText('No hay préstamos con estos filtros.');
  await user.type(screen.getByLabelText('Investigador'), 'ana');
  await user.click(await screen.findByRole('button', { name: /Ana Pérez/ }));
  await user.type(screen.getByLabelText(/Equipos/), 'm');
  await user.click(await screen.findByRole('button', { name: /Multímetro/ }));
  await user.click(screen.getByRole('button', { name: 'Registrar préstamo' }));
  expect((await screen.findByRole('alert')).textContent).toContain('El investigador tiene equipos vencidos.');
});

it('returns a line and reports the duplicate-return conflict', async () => {
  let returned = false;
  const fetch = stubApi({
    'GET /loans': () => reply(200, page([returned ? { ...loan, status: 'closed', items: [{ ...line, returned_at: '2026-11-18T10:00:00Z' }] } : loan])),
    'GET /researchers/r1': researcher,
    'POST /loans/l1/items/i1/return': () => { returned = true; return reply(200, { ...line, returned_at: '2026-11-18T10:00:00Z' }); },
  });
  const user = userEvent.setup();
  render(<Loans token="t" />);
  expect(await screen.findByText('Ana Pérez')).toBeTruthy();
  await user.click(screen.getByRole('button', { name: 'Devolver Multímetro' }));
  expect(await screen.findByText('Devolución registrada: Multímetro.')).toBeTruthy();
  await waitFor(() => expect(screen.queryByRole('button', { name: 'Devolver Multímetro' })).toBeNull());
  expect(screen.getByText('Cerrado')).toBeTruthy();
  expect(fetch.mock.calls.some(([url, options]) => String(url).endsWith('/return') && options.method === 'POST')).toBe(true);
});

it('explains a second return attempt (409) and reloads the list', async () => {
  stubApi({
    'GET /loans': page([loan]),
    'GET /researchers/r1': researcher,
    'POST /loans/l1/items/i1/return': () => reply(409, { code: 'ITEM_ALREADY_RETURNED', message: 'La línea ya fue devuelta.', details: [] }),
  });
  const user = userEvent.setup();
  render(<Loans token="t" />);
  await user.click(await screen.findByRole('button', { name: 'Devolver Multímetro' }));
  expect((await screen.findByRole('alert')).textContent).toContain('La línea ya fue devuelta.');
});

it('lists overdue lines with days late and allows returning them', async () => {
  let rows = [{ loan_id: 'l1', item_id: 'i1', equipment_name: 'Multímetro', researcher_name: 'Ana Pérez', due_at: '2026-11-10T23:00:00Z', days_late: 9 }];
  stubApi({
    'GET /loans/overdue': () => reply(200, { items: rows }),
    'POST /loans/l1/items/i1/return': () => { rows = []; return reply(200, {}); },
  });
  const user = userEvent.setup();
  render(<Overdue token="t" />);
  const row = (await screen.findByText('Ana Pérez')).closest('tr');
  expect(within(row).getByText('9')).toBeTruthy();
  await user.click(screen.getByRole('button', { name: 'Registrar devolución de Multímetro' }));
  expect(await screen.findByText('No hay equipos vencidos. Todo está al día.')).toBeTruthy();
});

it('shows an explicit empty state and errors for overdue', async () => {
  stubApi({ 'GET /loans/overdue': () => reply(500, { code: 'INTERNAL_ERROR', message: 'Error interno.', details: [] }) });
  render(<Overdue token="t" />);
  expect((await screen.findByRole('alert')).textContent).toContain('Error interno.');
});

it('generates the report for the chosen range and shows frozen rows', async () => {
  const fetch = stubApi({
    'GET /reports/most-borrowed': { from: '2026-11-01', to: '2026-11-19', rows: [{ equipment_id: 'e1', equipment_name: 'Multímetro', category_name: 'electrónica', times_borrowed: 7 }] },
  });
  const user = userEvent.setup();
  render(<Report token="t" />);
  await user.clear(screen.getByLabelText('Desde'));
  await user.type(screen.getByLabelText('Desde'), '2026-11-01');
  await user.clear(screen.getByLabelText('Hasta'));
  await user.type(screen.getByLabelText('Hasta'), '2026-11-19');
  await user.click(screen.getByRole('button', { name: 'Generar reporte' }));
  expect(await screen.findByText('Multímetro')).toBeTruthy();
  expect(screen.getByText('7')).toBeTruthy();
  expect(String(fetch.mock.calls[0][0])).toContain('from=2026-11-01&to=2026-11-19');
});

it('shows an inverted-range error from the server for the report', async () => {
  stubApi({ 'GET /reports/most-borrowed': () => reply(422, { code: 'INVALID_DATE_RANGE', message: 'La fecha inicial no puede ser posterior a la final.', details: [] }) });
  const user = userEvent.setup();
  render(<Report token="t" />);
  await user.click(screen.getByRole('button', { name: 'Generar reporte' }));
  expect((await screen.findByRole('alert')).textContent).toContain('La fecha inicial no puede ser posterior');
});
