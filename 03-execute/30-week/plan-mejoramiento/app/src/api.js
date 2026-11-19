export class ApiError extends Error {
  constructor(message, status, code, details = []) {
    super(message);
    this.status = status;
    this.code = code;
    this.details = details;
  }
}

export async function request(path, { token, body, method = 'GET', signal } = {}) {
  const headers = { Accept: 'application/json' };
  if (token) headers.Authorization = `Bearer ${token}`;
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  let response;
  try {
    response = await fetch('/api/v1' + path, { method, headers, signal,
      ...(body === undefined ? {} : { body: JSON.stringify(body) }) });
  } catch (error) {
    if (error.name === 'AbortError') throw error;
    throw new ApiError('No se pudo conectar. Revisa tu conexión e inténtalo de nuevo.', 0, 'NETWORK_ERROR');
  }
  if (response.status === 204) return null;
  let data;
  try { data = await response.json(); }
  catch { throw new ApiError('El servidor no pudo completar la solicitud.', response.status, 'INVALID_RESPONSE'); }
  if (!response.ok) throw new ApiError(data.message || 'No se pudo completar la solicitud.', response.status, data.code, data.details);
  return data;
}
