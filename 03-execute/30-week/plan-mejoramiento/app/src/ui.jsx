import React from 'react';

// Piezas compartidas por todas las vistas (carga, vacío, error y paginación).

export function Loading({ children = 'Cargando…' }) {
  return <p className="state" role="status">{children}</p>;
}

export function Empty({ children = 'No hay resultados.' }) {
  return <p className="state empty">{children}</p>;
}

export function ErrorNote({ error }) {
  if (!error) return null;
  const details = Array.isArray(error.details) ? error.details : [];
  return <div role="alert" className="error">
    <span>{error.message}</span>
    {details.length > 0 && <ul>{details.map((detail, index) => <li key={index}>{detail.issue || detail.field}</li>)}</ul>}
  </div>;
}

export function Notice({ children }) {
  if (!children) return null;
  return <p role="status" className="notice">{children}</p>;
}

export function Pager({ page, size, total, onPage }) {
  const pages = Math.max(1, Math.ceil(total / size));
  return <nav className="pager" aria-label="Paginación">
    <button type="button" disabled={page <= 1} onClick={() => onPage(page - 1)}>Anterior</button>
    <span>Página {page} de {pages} · {total} en total</span>
    <button type="button" disabled={page >= pages} onClick={() => onPage(page + 1)}>Siguiente</button>
  </nav>;
}

export function formatDate(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleString('es-CO', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'America/Bogota' });
}
