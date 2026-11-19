import React, { useState } from 'react';
import { request } from '../api.js';
import { Empty, ErrorNote, Loading } from '../ui.jsx';
import './ops.css';

function today() { return new Date().toISOString().slice(0, 10); }
function monthStart() { return `${today().slice(0, 8)}01`; }

export default function Report({ token }) {
  const [from, setFrom] = useState(monthStart());
  const [to, setTo] = useState(today());
  const [rows, setRows] = useState(null);
  const [range, setRange] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  async function run(event) {
    event.preventDefault();
    setLoading(true);
    setError(null);
    try {
      const params = new URLSearchParams({ from, to });
      const result = await request(`/reports/most-borrowed?${params}`, { token });
      setRows(Array.isArray(result?.rows) ? result.rows : []);
      setRange({ from: result?.from ?? from, to: result?.to ?? to });
    } catch (failure) {
      setError(failure);
      setRows(null);
    } finally { setLoading(false); }
  }

  return <section className="ops">
    <h2>Equipos más prestados</h2>
    <form className="toolbar" onSubmit={run}>
      <label>Desde<input type="date" required value={from} onChange={event => setFrom(event.target.value)} /></label>
      <label>Hasta<input type="date" required value={to} onChange={event => setTo(event.target.value)} /></label>
      <button type="submit" disabled={loading}>{loading ? 'Consultando…' : 'Generar reporte'}</button>
    </form>
    <ErrorNote error={error} />
    {loading && <Loading>Generando reporte…</Loading>}
    {!loading && rows === null && !error && <p className="state">Elige un rango de fechas y genera el reporte. Ambos días se incluyen completos.</p>}
    {!loading && rows && rows.length === 0 && <Empty>No hubo préstamos entre {range.from} y {range.to}.</Empty>}
    {!loading && rows && rows.length > 0 && <>
      <p className="state">Del {range.from} al {range.to}</p>
      <div className="table-scroll"><table className="data">
        <thead><tr><th>Equipo</th><th>Categoría</th><th>Veces prestado</th></tr></thead>
        <tbody>{rows.map(row => <tr key={row.equipment_id}><td>{row.equipment_name}</td><td>{row.category_name}</td><td>{row.times_borrowed}</td></tr>)}</tbody>
      </table></div>
    </>}
  </section>;
}
