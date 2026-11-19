import React, { useEffect, useState } from 'react';
import { request } from '../api.js';
import { Empty, ErrorNote, Loading, Notice, Pager } from '../ui.jsx';
import './ops.css';

const SIZE = 10;

export default function Researchers({ token }) {
  const [term, setTerm] = useState('');
  const [query, setQuery] = useState('');
  const [page, setPage] = useState(1);
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [reload, setReload] = useState(0);
  const [detail, setDetail] = useState(null);
  const [detailError, setDetailError] = useState(null);
  const [fullName, setFullName] = useState('');
  const [documentNumber, setDocumentNumber] = useState('');
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState(null);
  const [notice, setNotice] = useState('');

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    setError(null);
    const params = new URLSearchParams({ page: String(page), size: String(SIZE) });
    if (query) params.set('q', query);
    request(`/researchers?${params}`, { token, signal: controller.signal })
      .then(result => { setData(result); setLoading(false); })
      .catch(failure => { if (failure.name !== 'AbortError') { setError(failure); setLoading(false); } });
    return () => controller.abort();
  }, [token, query, page, reload]);

  function search(event) {
    event.preventDefault();
    setPage(1);
    setQuery(term.trim());
  }

  async function create(event) {
    event.preventDefault();
    setSaving(true);
    setFormError(null);
    setNotice('');
    try {
      const created = await request('/researchers', { token, method: 'POST', body: { full_name: fullName.trim(), document_number: documentNumber.trim() } });
      setNotice(`Investigador registrado: ${created.full_name}.`);
      setFullName('');
      setDocumentNumber('');
      setReload(value => value + 1);
    } catch (failure) {
      setFormError(failure);
    } finally { setSaving(false); }
  }

  async function open(id) {
    setDetailError(null);
    setDetail(null);
    try { setDetail(await request(`/researchers/${id}`, { token })); }
    catch (failure) { setDetailError(failure); }
  }

  const items = Array.isArray(data?.items) ? data.items : [];

  return <section className="ops">
    <h2>Investigadores</h2>
    <div className="grid-2">
      <div>
        <form className="toolbar" onSubmit={search} role="search">
          <label>Buscar por nombre o documento<input value={term} onChange={event => setTerm(event.target.value)} maxLength={200} /></label>
          <button type="submit">Buscar</button>
        </form>
        <ErrorNote error={error} />
        {loading && <Loading />}
        {!loading && !error && items.length === 0 && <Empty>{query ? 'No hay investigadores que coincidan con la búsqueda.' : 'Aún no hay investigadores registrados.'}</Empty>}
        {items.length > 0 && <div className="table-scroll"><table className="data">
          <thead><tr><th>Nombre</th><th>Documento</th><th><span className="sr-only">Acciones</span></th></tr></thead>
          <tbody>{items.map(item => <tr key={item.id}>
            <td>{item.full_name}</td><td>{item.document_number}</td>
            <td><button type="button" onClick={() => open(item.id)} aria-label={`Ver pendientes de ${item.full_name}`}>Ver pendientes</button></td>
          </tr>)}</tbody>
        </table></div>}
        {data && <Pager page={page} size={SIZE} total={data.total ?? 0} onPage={setPage} />}
        <ErrorNote error={detailError} />
        {detail && <div className="card" role="status" aria-label="Detalle del investigador">
          <strong>{detail.full_name}</strong> · {detail.document_number}
          <p>{detail.active_loans === 0 ? 'No tiene equipos pendientes por devolver.' : `Equipos pendientes por devolver: ${detail.active_loans}.`}</p>
        </div>}
      </div>
      <div>
        <h3>Registrar investigador</h3>
        <form onSubmit={create}>
          <label htmlFor="researcher-name">Nombre completo</label>
          <input id="researcher-name" required maxLength={200} value={fullName} onChange={event => setFullName(event.target.value)} />
          <label htmlFor="researcher-document">Documento</label>
          <input id="researcher-document" required maxLength={30} value={documentNumber} onChange={event => setDocumentNumber(event.target.value)} />
          <ErrorNote error={formError} />
          <Notice>{notice}</Notice>
          <button type="submit" disabled={saving}>{saving ? 'Guardando…' : 'Registrar'}</button>
        </form>
      </div>
    </div>
  </section>;
}
