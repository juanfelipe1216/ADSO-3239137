import React, { useEffect, useState } from 'react';
import { request } from '../api.js';
import { Empty, ErrorNote, Loading, Notice, formatDate } from '../ui.jsx';
import './ops.css';

export default function Overdue({ token }) {
  const [items, setItems] = useState(null);
  const [error, setError] = useState(null);
  const [notice, setNotice] = useState('');
  const [reload, setReload] = useState(0);
  const [busy, setBusy] = useState('');

  useEffect(() => {
    const controller = new AbortController();
    request('/loans/overdue', { token, signal: controller.signal })
      .then(result => setItems(Array.isArray(result?.items) ? result.items : []))
      .catch(failure => { if (failure.name !== 'AbortError') { setError(failure); setItems([]); } });
    return () => controller.abort();
  }, [token, reload]);

  async function giveBack(row) {
    setBusy(row.item_id);
    setNotice('');
    setError(null);
    try {
      await request(`/loans/${row.loan_id}/items/${row.item_id}/return`, { token, method: 'POST' });
      setNotice(`Devolución registrada: ${row.equipment_name}.`);
    } catch (failure) {
      setError(failure);
    } finally {
      setBusy('');
      setReload(value => value + 1);
    }
  }

  function refresh() {
    setError(null);
    setNotice('');
    setReload(value => value + 1);
  }

  return <section className="ops">
    <h2>Equipos vencidos</h2>
    <div className="toolbar"><button type="button" onClick={refresh}>Actualizar</button></div>
    <ErrorNote error={error} />
    <Notice>{notice}</Notice>
    {items === null && <Loading />}
    {items && items.length === 0 && !error && <Empty>No hay equipos vencidos. Todo está al día.</Empty>}
    {items && items.length > 0 && <div className="table-scroll"><table className="data">
      <thead><tr><th>Equipo</th><th>Investigador</th><th>Vencía</th><th>Días de retraso</th><th><span className="sr-only">Acción</span></th></tr></thead>
      <tbody>{items.map(row => <tr key={row.item_id}>
        <td>{row.equipment_name}</td><td>{row.researcher_name}</td><td>{formatDate(row.due_at)}</td><td>{row.days_late}</td>
        <td><button type="button" disabled={busy === row.item_id} onClick={() => giveBack(row)} aria-label={`Registrar devolución de ${row.equipment_name}`}>Devolver</button></td>
      </tr>)}</tbody>
    </table></div>}
  </section>;
}
