import { ApiError } from '../api.js';

// Binary downloads and multipart uploads must not use the JSON request helper.
export async function coverRequest(id, { token, file, signal }) {
  const body = file ? new FormData() : undefined;
  if (file) body.append('file', file);
  let response;
  try {
    response = await fetch(`/api/v1/equipment/${id}/cover`, {
      method: file ? 'PUT' : 'GET', headers: { Authorization: `Bearer ${token}` }, body, signal,
    });
  } catch (error) {
    if (error.name === 'AbortError') throw error;
    throw new ApiError('No se pudo conectar. Revisa tu conexión e inténtalo de nuevo.', 0, 'NETWORK_ERROR');
  }
  if (!response.ok) {
    let data;
    try { data = await response.json(); }
    catch { throw new ApiError('El servidor no pudo completar la solicitud.', response.status, 'INVALID_RESPONSE'); }
    throw new ApiError(data.message || 'No se pudo completar la solicitud.', response.status, data.code, data.details);
  }
  return file ? response.json() : response.blob();
}
