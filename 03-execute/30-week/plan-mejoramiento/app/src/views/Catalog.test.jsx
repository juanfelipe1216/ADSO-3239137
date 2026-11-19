import React from 'react';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { act, cleanup, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Catalog from './Catalog.jsx';

const category = { id: '33333333-3333-4333-8333-333333333333', name: 'Electrónica' };
const equipment = { id: 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', name: 'Multímetro digital', manufacturer: 'Fabricante demo',
  reference: 'LAB-001', category, total_units: 3, available_units: 2, cover_key: null };
const response = (data, status = 200) => ({ ok: status < 400, status, json: async () => data });
const failure = (message, code, issue) => response({ message, code, details: issue ? [{ field: 'equipment', issue }] : [] }, 409);
let createObjectURL, revokeObjectURL;

beforeEach(() => {
  createObjectURL = vi.fn(() => 'blob:test-cover');
  revokeObjectURL = vi.fn();
  vi.stubGlobal('URL', class extends URL {
    static createObjectURL = createObjectURL;
    static revokeObjectURL = revokeObjectURL;
  });
});
afterEach(() => { cleanup(); vi.unstubAllGlobals(); });

function mockApi({ items = [{ ...equipment }], total, intercept } = {}) {
  const state = { items };
  const fetch = vi.fn(async (path, options = {}) => {
    const method = options.method ?? 'GET';
    const custom = intercept?.(path, options);
    if (custom !== undefined) return custom;
    if (path === '/api/v1/categories') return response({ items: [category] });
    if (path.startsWith('/api/v1/equipment?')) {
      const query = new URL(path, 'http://localhost').searchParams;
      return response({ items: state.items, page: Number(query.get('page')), size: Number(query.get('size')), total: total ?? state.items.length });
    }
    if (path === '/api/v1/equipment' && method === 'POST') {
      const body = JSON.parse(options.body);
      const created = { ...equipment, ...body, reference: body.reference.trim().toUpperCase(), category };
      state.items = [created];
      return response(created, 201);
    }
    if (path.endsWith('/cover')) {
      if (method === 'GET') return { ok: true, status: 200, blob: async () => new Blob(['photo'], { type: 'image/png' }) };
      state.items[0] = { ...state.items[0], cover_key: method === 'DELETE' ? null : 'private/storage/key' };
      return response(method === 'DELETE' ? null : state.items[0], method === 'DELETE' ? 204 : 200);
    }
    if (path === `/api/v1/equipment/${equipment.id}`) {
      if (method === 'DELETE') { state.items = []; return response(null, 204); }
      if (method === 'PUT') state.items[0] = { ...state.items[0], ...JSON.parse(options.body) };
      return response(state.items[0]);
    }
    throw new Error(`Unexpected request: ${method} ${path}`);
  });
  vi.stubGlobal('fetch', fetch);
  return fetch;
}

async function openDetail(user) {
  await user.click(await screen.findByRole('button', { name: `Ver detalle: ${equipment.name}` }));
  const detail = screen.getByRole('region', { name: 'Detalle del equipo' });
  await within(detail).findByRole('heading', { name: equipment.name });
  return detail;
}

async function fillCreate(user) {
  await user.click(screen.getByRole('button', { name: 'Nuevo equipo' }));
  await user.type(screen.getByLabelText('Nombre'), 'Osciloscopio');
  await user.type(screen.getByLabelText('Fabricante'), 'Fabricante nuevo');
  await user.type(screen.getByLabelText('Referencia'), ' lab-002 ');
  await user.selectOptions(screen.getByLabelText('Categoría del equipo'), category.id);
  await user.clear(screen.getByLabelText('Unidades totales'));
  await user.type(screen.getByLabelText('Unidades totales'), '4');
}

it.each(['admin', 'lab_technician'])('searches, filters and paginates as %s with Bearer authentication', async role => {
  const fetch = mockApi({ total: 60 });
  const user = userEvent.setup();
  render(<Catalog token="test-token" role={role} />);
  expect(await screen.findByText('2 / 3')).toBeTruthy();
  await user.click(screen.getByRole('button', { name: 'Siguiente' }));
  expect(await screen.findByText('Página 2 de 3 · 60 en total')).toBeTruthy();
  await user.type(screen.getByLabelText('Buscar por nombre o fabricante'), 'multi & demo');
  await user.selectOptions(screen.getByLabelText('Filtrar por categoría'), category.id);
  await user.selectOptions(screen.getByLabelText('Equipos por página'), '50');
  await waitFor(() => expect(screen.getByText('Página 1 de 2 · 60 en total')).toBeTruthy());
  const calls = fetch.mock.calls.filter(([path]) => path.includes('equipment?'));
  const query = new URL(calls.at(-1)[0], 'http://localhost').searchParams;
  expect(Object.fromEntries(query)).toEqual({ page: '1', size: '50', q: 'multi & demo', category_id: category.id });
  expect(fetch.mock.calls.some(([path]) => path === '/api/v1/categories')).toBe(true);
  for (const [path, options] of fetch.mock.calls) {
    expect(options.headers.Authorization).toBe('Bearer test-token');
    expect(path).not.toContain('test-token');
  }
});

it('hides all administrative controls from lab technicians, including in the detail', async () => {
  mockApi();
  const user = userEvent.setup();
  render(<Catalog token="test-token" role="lab_technician" />);
  await openDetail(user);
  expect(screen.queryByRole('button', { name: /Nuevo equipo|Editar|Desactivar|Subir|Quitar/ })).toBeNull();
  expect(screen.queryByLabelText(/Fotografía \(/)).toBeNull();
});

it('creates equipment and shows the returned normalized reference', async () => {
  const fetch = mockApi({ items: [] });
  const user = userEvent.setup();
  render(<Catalog token="test-token" role="admin" />);
  await screen.findByText('No hay equipos que coincidan con la búsqueda.');
  await fillCreate(user);
  await user.click(screen.getByRole('button', { name: 'Crear equipo' }));
  expect(await screen.findByText('Equipo creado.')).toBeTruthy();
  expect(await screen.findByText('LAB-002')).toBeTruthy();
  const call = fetch.mock.calls.find(([, options]) => options.method === 'POST');
  expect(JSON.parse(call[1].body)).toEqual({ name: 'Osciloscopio', manufacturer: 'Fabricante nuevo', reference: ' lab-002 ', category_id: category.id, total_units: 4 });
});

it('edits all mutable fields without sending the immutable reference', async () => {
  const fetch = mockApi();
  const user = userEvent.setup();
  render(<Catalog token="test-token" role="admin" />);
  await user.click(await screen.findByRole('button', { name: `Editar: ${equipment.name}` }));
  const input = await screen.findByLabelText('Nombre');
  expect(screen.getByLabelText('Referencia').readOnly).toBe(true);
  await user.clear(input); await user.type(input, 'Multímetro actualizado');
  await user.click(screen.getByRole('button', { name: 'Guardar cambios' }));
  expect(await screen.findByText('Equipo actualizado.')).toBeTruthy();
  const call = fetch.mock.calls.find(([, options]) => options.method === 'PUT');
  expect(call[0]).toBe(`/api/v1/equipment/${equipment.id}`);
  expect(JSON.parse(call[1].body)).toEqual({ name: 'Multímetro actualizado', manufacturer: equipment.manufacturer, category_id: category.id, total_units: 3 });
});

it('requires confirmation for deactivation, supports cancellation, and refreshes the list', async () => {
  const fetch = mockApi();
  const user = userEvent.setup();
  render(<Catalog token="test-token" role="admin" />);
  await openDetail(user);
  await user.click(screen.getByRole('button', { name: 'Desactivar equipo' }));
  expect(fetch.mock.calls.some(([, options]) => options.method === 'DELETE')).toBe(false);
  await user.click(screen.getByRole('button', { name: 'Cancelar desactivación' }));
  expect(screen.queryByRole('button', { name: 'Confirmar desactivación' })).toBeNull();
  await user.click(screen.getByRole('button', { name: 'Desactivar equipo' }));
  await user.click(screen.getByRole('button', { name: 'Confirmar desactivación' }));
  expect(await screen.findByText('Equipo desactivado.')).toBeTruthy();
  expect(await screen.findByText('No hay equipos que coincidan con la búsqueda.')).toBeTruthy();
});

it.each([
  ['EQUIPMENT_HAS_OPEN_LOANS', 'El equipo tiene préstamos pendientes.'],
  ['EQUIPMENT_NOT_AVAILABLE', 'El equipo no tiene unidades disponibles.'],
])('renders deactivation conflict %s and its details', async (code, message) => {
  mockApi({ intercept: (path, options) => options.method === 'DELETE' ? failure(message, code, 'Revisa los préstamos abiertos.') : undefined });
  const user = userEvent.setup();
  render(<Catalog token="test-token" role="admin" />);
  await openDetail(user);
  await user.click(screen.getByRole('button', { name: 'Desactivar equipo' }));
  await user.click(screen.getByRole('button', { name: 'Confirmar desactivación' }));
  expect((await screen.findByRole('alert')).textContent).toContain(message);
  expect(screen.getByRole('alert').textContent).toContain('Revisa los préstamos abiertos.');
  expect(screen.queryByText('Equipo desactivado.')).toBeNull();
});

it('renders reference conflicts and preserves equipment form values', async () => {
  mockApi({ intercept: (path, options) => options.method === 'POST' ? failure('La referencia ya está registrada.', 'REFERENCE_TAKEN', 'Usa otra referencia.') : undefined });
  const user = userEvent.setup();
  render(<Catalog token="test-token" role="admin" />);
  await screen.findByText('2 / 3');
  await fillCreate(user);
  await user.click(screen.getByRole('button', { name: 'Crear equipo' }));
  expect((await screen.findByRole('alert')).textContent).toContain('La referencia ya está registrada.');
  expect(screen.getByRole('alert').textContent).toContain('Usa otra referencia.');
  expect(screen.getByLabelText('Nombre').value).toBe('Osciloscopio');
});

it('uploads a multipart cover, fetches authenticated blobs and removes the cover with 204', async () => {
  const fetch = mockApi();
  const user = userEvent.setup();
  const view = render(<Catalog token="test-token" role="admin" />);
  await openDetail(user);
  const file = new File(['photo'], 'photo.png', { type: 'image/png' });
  await user.upload(screen.getByLabelText(/Fotografía \(/), file);
  await user.click(screen.getByRole('button', { name: 'Subir fotografía' }));
  expect(await screen.findByText('Fotografía guardada.')).toBeTruthy();
  const upload = fetch.mock.calls.find(([path, options]) => path.endsWith('/cover') && options.method === 'PUT');
  expect(upload[1].body).toBeInstanceOf(FormData);
  expect(upload[1].body.get('file')).toBe(file);
  expect(upload[1].headers).toEqual({ Authorization: 'Bearer test-token' });
  await waitFor(() => expect(screen.getAllByRole('img').length).toBe(2));
  for (const img of screen.getAllByRole('img')) expect(img.getAttribute('src')).toBe('blob:test-cover');
  expect(fetch.mock.calls.some(([path]) => path.includes('private/storage/key'))).toBe(false);
  await user.click(screen.getByRole('button', { name: 'Quitar fotografía' }));
  expect(await screen.findByText('Fotografía eliminada.')).toBeTruthy();
  await waitFor(() => expect(screen.queryByRole('img')).toBeNull());
  expect(screen.getByLabelText(/Fotografía \(/).value).toBe('');
  view.unmount();
  expect(revokeObjectURL.mock.calls.length).toBe(createObjectURL.mock.calls.length);
});

it.each([
  ['tipo no admitido', () => new File(['gif'], 'photo.gif', { type: 'image/gif' })],
  ['más de 2 MiB', () => new File([new Uint8Array(2097153)], 'large.jpg', { type: 'image/jpeg' })],
])('rejects cover files with %s before sending', async (_name, makeFile) => {
  const fetch = mockApi();
  const user = userEvent.setup({ applyAccept: false });
  render(<Catalog token="test-token" role="admin" />);
  await openDetail(user);
  await user.upload(screen.getByLabelText(/Fotografía \(/), makeFile());
  await user.click(screen.getByRole('button', { name: 'Subir fotografía' }));
  expect((await screen.findByRole('alert')).textContent).toContain('JPG o PNG de hasta 2 MiB');
  expect(fetch.mock.calls.some(([, options]) => options.method === 'PUT')).toBe(false);
});

it('renders cover upload errors from the API', async () => {
  mockApi({ intercept: (path, options) => path.endsWith('/cover') && options.method === 'PUT'
    ? failure('Fotografía inválida.', 'VALIDATION_ERROR', 'El contenido debe ser JPG o PNG.') : undefined });
  const user = userEvent.setup();
  render(<Catalog token="test-token" role="admin" />);
  await openDetail(user);
  await user.upload(screen.getByLabelText(/Fotografía \(/), new File(['bad'], 'bad.png', { type: 'image/png' }));
  await user.click(screen.getByRole('button', { name: 'Subir fotografía' }));
  expect((await screen.findByRole('alert')).textContent).toContain('Fotografía inválida.El contenido debe ser JPG o PNG.');
});

it('shows list and category errors and can retry both requests', async () => {
  let broken = true;
  mockApi({ items: [], intercept: () => broken ? failure('No se pudo cargar.', 'INTERNAL_ERROR', 'Intenta de nuevo.') : undefined });
  const user = userEvent.setup();
  render(<Catalog token="test-token" role="admin" />);
  await waitFor(() => expect(screen.getAllByRole('alert').length).toBe(2));
  broken = false;
  await user.click(screen.getByRole('button', { name: 'Reintentar categorías' }));
  await user.click(screen.getByRole('button', { name: 'Reintentar catálogo' }));
  expect(await screen.findByText('No hay equipos que coincidan con la búsqueda.')).toBeTruthy();
  expect(screen.queryByRole('alert')).toBeNull();
});

it('aborts obsolete searches and ignores late responses even if fetch ignores abort', async () => {
  const pending = [];
  mockApi({ intercept: (path, options) => path.includes('equipment?') ? new Promise(resolve => pending.push({ path, signal: options.signal, resolve })) : undefined });
  const view = render(<Catalog token="test-token" role="lab_technician" />);
  expect(screen.getByText('Cargando equipos…')).toBeTruthy();
  fireEvent.change(screen.getByLabelText('Buscar por nombre o fabricante'), { target: { value: 'nuevo' } });
  expect(pending[0].signal.aborted).toBe(true);
  await act(async () => pending[1].resolve(response({ items: [{ ...equipment, name: 'Resultado nuevo' }], page: 1, size: 20, total: 1 })));
  await act(async () => pending[0].resolve(response({ items: [equipment], page: 1, size: 20, total: 1 })));
  expect(screen.getByText('Resultado nuevo')).toBeTruthy();
  expect(screen.queryByText(equipment.name)).toBeNull();
  view.unmount();
  expect(pending[1].signal.aborted).toBe(true);
});

it('revokes cover URLs on unmount and never creates a URL for a late download', async () => {
  const pending = [];
  mockApi({ items: [{ ...equipment, cover_key: 'private-key' }], intercept: (path, options) => path.endsWith('/cover')
    ? new Promise(resolve => pending.push({ signal: options.signal, resolve })) : undefined });
  const view = render(<Catalog token="test-token" role="lab_technician" />);
  await screen.findByText('Cargando fotografía…');
  view.unmount();
  expect(pending[0].signal.aborted).toBe(true);
  await act(async () => pending[0].resolve({ ok: true, blob: async () => new Blob(['photo']) }));
  expect(createObjectURL).not.toHaveBeenCalled();
});
