import React, { useEffect, useState } from 'react';
import { request } from '../api.js';
import { ErrorNote } from '../ui.jsx';

// Selector con búsqueda en el servidor: sirve con más de 50 registros porque nunca carga todo el catálogo.
export default function SearchPicker({ id, label, path, token, describe, isDisabled, onPick }) {
  const [text, setText] = useState('');
  const [results, setResults] = useState([]);
  const [state, setState] = useState('idle');
  const [error, setError] = useState(null);

  useEffect(() => {
    const term = text.trim();
    if (!term) { setResults([]); setState('idle'); setError(null); return undefined; }
    const controller = new AbortController();
    const timer = setTimeout(async () => {
      setState('loading');
      setError(null);
      try {
        const data = await request(`${path}?q=${encodeURIComponent(term)}&page=1&size=8`, { token, signal: controller.signal });
        setResults(Array.isArray(data?.items) ? data.items : []);
        setState('done');
      } catch (failure) {
        if (failure.name === 'AbortError') return;
        setError(failure);
        setState('error');
      }
    }, 300);
    return () => { clearTimeout(timer); controller.abort(); };
  }, [text, path, token]);

  return <div>
    <label htmlFor={id}>{label}</label>
    <input id={id} type="search" autoComplete="off" value={text} onChange={event => setText(event.target.value)} placeholder="Escribe para buscar" />
    <ErrorNote error={error} />
    {state === 'loading' && <p className="state" role="status">Buscando…</p>}
    {state === 'done' && results.length === 0 && <p className="state">Sin coincidencias.</p>}
    {results.length > 0 && <ul className="picker-results">
      {results.map(item => <li key={item.id}>
        <button type="button" disabled={isDisabled?.(item)} onClick={() => { onPick(item); setText(''); }}>{describe(item)}</button>
      </li>)}
    </ul>}
  </div>;
}
