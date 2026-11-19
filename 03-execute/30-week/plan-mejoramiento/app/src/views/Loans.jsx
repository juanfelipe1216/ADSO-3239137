import React, { useEffect, useState } from 'react';
import { request } from '../api.js';
import { Empty, ErrorNote, Loading, Notice, Pager, formatDate } from '../ui.jsx';
import SearchPicker from './picker.jsx';
import './ops.css';

const SIZE = 10;
const MAX_ITEMS = 2;
const STATUS_LABEL = { open: 'Abierto', overdue: 'Vencido', closed: 'Cerrado' };

function NewLoan({ token, onCreated }) {
  const [researcher, setResearcher] = useState(null);
  const [equipment, setEquipment] = useState([]);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [notice, setNotice] = useState('');

  function addEquipment(item) {
    setNotice('');
    setEquipment(current => (current.length >= MAX_ITEMS || current.some(entry => entry.id === item.id) ? current : [...current, item]));
  }

  async function submit(event) {
    event.preventDefault();
    setSaving(true);
    setError(null);
    setNotice('');
    try {
      await request('/loans', { token, method: 'POST', body: { researcher_id: researcher.id, items: equipment.map(item => ({ equipment_id: item.id })) } });
      setNotice(`Préstamo registrado para ${researcher.full_name}.`);
      setResearcher(null);
      setEquipment([]);
      onCreated();
    } catch (failure) {
      setError(failure);
    } finally { setSaving(false); }
  }

  return <form onSubmit={submit} aria-label="Registrar préstamo">
    <h3>Registrar préstamo</h3>
    {researcher
      ? <p>Investigador: <strong>{researcher.full_name}</strong> ({researcher.document_number}) <button type="button" className="ghost" onClick={() => setResearcher(null)}>Cambiar</button></p>
      : <SearchPicker id="loan-researcher" label="Investigador" path="/researchers" token={token}
        describe={item => `${item.full_name} · ${item.document_number}`} onPick={item => { setNotice(''); setResearcher(item); }} />}
    <SearchPicker id="loan-equipment" label={`Equipos (máximo ${MAX_ITEMS})`} path="/equipment" token={token}
      describe={item => `${item.name} · ${item.reference} · ${item.available_units} disponibles`}
      isDisabled={item => item.available_units < 1 || equipment.length >= MAX_ITEMS || equipment.some(entry => entry.id === item.id)}
      onPick={addEquipment} />
    {equipment.length > 0 && <ul className="picked" aria-label="Seleccionados para el préstamo">
      {equipment.map(item => <li key={item.id}>{item.name}
        <button type="button" aria-label={`Quitar ${item.name}`} onClick={() => setEquipment(current => current.filter(entry => entry.id !== item.id))}>✕</button></li>)}
    </ul>}
    <ErrorNote error={error} />
    <Notice>{notice}</Notice>
    <button type="submit" disabled={saving || !researcher || equipment.length === 0}>{saving ? 'Registrando…' : 'Registrar préstamo'}</button>
  </form>;
}

function LoanCard({ loan, researcherName, busy, onReturn }) {
  return <article className="card">
    <header>
      <span><strong>{researcherName ?? 'Investigador'}</strong> · {formatDate(loan.loaned_at)}</span>
      <span className={`badge ${loan.status}`}>{STATUS_LABEL[loan.status] ?? loan.status}</span>
    </header>
    <ul className="lines">
      {(loan.items ?? []).map(line => <li key={line.id}>
        <span>{line.equipment_name} · {line.reference} · {line.category_name}<br />
          {line.returned_at ? `Devuelto ${formatDate(line.returned_at)}` : `Vence ${formatDate(line.due_at)}`}</span>
        {!line.returned_at && <button type="button" disabled={busy === line.id} onClick={() => onReturn(loan, line)}
          aria-label={`Devolver ${line.equipment_name}`}>Devolver</button>}
      </li>)}
    </ul>
  </article>;
}

export default function Loans({ token }) {
  const [status, setStatus] = useState('');
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const [page, setPage] = useState(1);
  const [data, setData] = useState(null);
  const [names, setNames] = useState({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [reload, setReload] = useState(0);
  const [busy, setBusy] = useState('');
  const [notice, setNotice] = useState('');
  const [actionError, setActionError] = useState(null);

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    setError(null);
    const params = new URLSearchParams({ page: String(page), size: String(SIZE) });
    if (status) params.set('status', status);
    if (from) params.set('from', from);
    if (to) params.set('to', to);
    request(`/loans?${params}`, { token, signal: controller.signal })
      .then(result => { setData(result); setLoading(false); })
      .catch(failure => { if (failure.name !== 'AbortError') { setError(failure); setData(null); setLoading(false); } });
    return () => controller.abort();
  }, [token, status, from, to, page, reload]);

  const loans = Array.isArray(data?.items) ? data.items : [];

  useEffect(() => {
    const missing = [...new Set(loans.map(loan => loan.researcher_id))].filter(id => id && !names[id]);
    if (missing.length === 0) return undefined;
    let cancelled = false;
    Promise.allSettled(missing.map(id => request(`/researchers/${id}`, { token }))).then(results => {
      if (cancelled) return;
      const found = {};
      results.forEach((result, index) => { if (result.status === 'fulfilled') found[missing[index]] = result.value.full_name; });
      setNames(current => ({ ...current, ...found }));
    });
    return () => { cancelled = true; };
  }, [data, token]);

  function changeFilter(setter) {
    return event => { setter(event.target.value); setPage(1); };
  }

  async function giveBack(loan, line) {
    setBusy(line.id);
    setNotice('');
    setActionError(null);
    try {
      await request(`/loans/${loan.id}/items/${line.id}/return`, { token, method: 'POST' });
      setNotice(`Devolución registrada: ${line.equipment_name}.`);
    } catch (failure) {
      setActionError(failure);
    } finally {
      setBusy('');
      setReload(value => value + 1);
    }
  }

  return <section className="ops">
    <h2>Préstamos y devoluciones</h2>
    <div className="grid-2">
      <div>
        <h3>Historial</h3>
        <div className="toolbar">
          <label>Estado<select value={status} onChange={changeFilter(setStatus)}>
            <option value="">Todos</option><option value="open">Abiertos</option><option value="overdue">Vencidos</option><option value="closed">Cerrados</option>
          </select></label>
          <label>Desde<input type="date" value={from} onChange={changeFilter(setFrom)} /></label>
          <label>Hasta<input type="date" value={to} onChange={changeFilter(setTo)} /></label>
        </div>
        <ErrorNote error={error} />
        <ErrorNote error={actionError} />
        <Notice>{notice}</Notice>
        {loading && <Loading />}
        {!loading && !error && loans.length === 0 && <Empty>No hay préstamos con estos filtros.</Empty>}
        {loans.map(loan => <LoanCard key={loan.id} loan={loan} researcherName={names[loan.researcher_id]} busy={busy} onReturn={giveBack} />)}
        {data && <Pager page={page} size={SIZE} total={data.total ?? 0} onPage={setPage} />}
      </div>
      <NewLoan token={token} onCreated={() => { setPage(1); setReload(value => value + 1); }} />
    </div>
  </section>;
}
