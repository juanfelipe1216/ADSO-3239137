import { afterEach, expect, it, vi } from 'vitest';
import { request } from './api.js';
afterEach(() => vi.unstubAllGlobals());
it('sends bearer token and handles an empty successful response', async () => {
  const fetch = vi.fn().mockResolvedValue({ status: 204 });
  vi.stubGlobal('fetch', fetch);
  expect(await request('/equipment/id', { method: 'DELETE', token: 'test-token' })).toBeNull();
  expect(fetch.mock.calls[0][1].headers.Authorization).toBe('Bearer test-token');
});
it('preserves business error details', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ status: 422, ok: false, json: async () => ({ message: 'Límite excedido.', code: 'RESEARCHER_LIMIT_EXCEEDED', details: [{ field: 'active_loans', issue: '2' }] }) }));
  await expect(request('/loans')).rejects.toMatchObject({ status: 422, code: 'RESEARCHER_LIMIT_EXCEEDED', details: [{ field: 'active_loans', issue: '2' }] });
});
it('normalizes network and invalid response errors', async () => {
  vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('network')));
  await expect(request('/health')).rejects.toMatchObject({ code: 'NETWORK_ERROR' });
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ status: 502, json: async () => { throw new Error('html'); } }));
  await expect(request('/health')).rejects.toMatchObject({ code: 'INVALID_RESPONSE' });
});
