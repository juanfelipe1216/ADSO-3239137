import React, { useEffect, useRef, useState } from 'react';
import { request } from '../api.js';
import { Empty, ErrorNote, Loading, Notice, Pager } from '../ui.jsx';
import { coverRequest } from './catalog-http.js';
import './catalog.css';

function Cover({ equipment, token, revision }) {
  const [url, setUrl] = useState(null);
  const [error, setError] = useState(null);
  const [retry, setRetry] = useState(0);
  useEffect(() => {
    const controller = new AbortController();
    let objectUrl;
    setUrl(null); setError(null);
    coverRequest(equipment.id, { token, signal: controller.signal }).then(blob => {
      if (controller.signal.aborted) return;
      objectUrl = URL.createObjectURL(blob);
      setUrl(objectUrl);
    }).catch(failure => { if (!controller.signal.aborted) setError(failure); });
    return () => { controller.abort(); if (objectUrl) URL.revokeObjectURL(objectUrl); };
  }, [equipment.id, equipment.cover_key, token, revision, retry]);
  if (error) return <><ErrorNote error={error} /><button type="button" onClick={() => setRetry(value => value + 1)}>Reintentar fotografía</button></>;
  return url ? <img className="catalog-cover" src={url} alt={`Fotografía de ${equipment.name}`} /> : <Loading>Cargando fotografía…</Loading>;
}

function EquipmentForm({ equipment, categories, busy, onSubmit, onCancel }) {
  const [values, setValues] = useState(() => ({ name: equipment?.name ?? '', manufacturer: equipment?.manufacturer ?? '',
    reference: equipment?.reference ?? '', category_id: equipment?.category.id ?? '', total_units: equipment?.total_units ?? 1 }));
  const prefix = equipment ? 'edit' : 'create';
  function change(event) { setValues(current => ({ ...current, [event.target.name]: event.target.value })); }
  function submit(event) {
    event.preventDefault();
    const body = { name: values.name, manufacturer: values.manufacturer, category_id: values.category_id, total_units: Number(values.total_units) };
    if (!equipment) body.reference = values.reference;
    onSubmit(body);
  }
  return <form onSubmit={submit}>
    <fieldset disabled={busy}>
      <legend>{equipment ? 'Editar equipo' : 'Crear equipo'}</legend>
      <div className="catalog-fields">
        <div><label htmlFor={`${prefix}-name`}>Nombre</label><input id={`${prefix}-name`} name="name" required maxLength={250} value={values.name} onChange={change} /></div>
        <div><label htmlFor={`${prefix}-manufacturer`}>Fabricante</label><input id={`${prefix}-manufacturer`} name="manufacturer" required maxLength={200} value={values.manufacturer} onChange={change} /></div>
        <div><label htmlFor={`${prefix}-reference`}>Referencia</label><input id={`${prefix}-reference`} name="reference" required maxLength={64} readOnly={!!equipment} value={values.reference} onChange={change} />
          {equipment && <p className="state">La referencia no se puede modificar.</p>}</div>
        <div><label htmlFor={`${prefix}-category`}>Categoría del equipo</label><select id={`${prefix}-category`} name="category_id" required value={values.category_id} onChange={change}>
          <option value="">Seleccionar categoría</option>{categories.map(category => <option key={category.id} value={category.id}>{category.name}</option>)}
        </select></div>
        <div><label htmlFor={`${prefix}-units`}>Unidades totales</label><input id={`${prefix}-units`} name="total_units" type="number" min="1" step="1" required value={values.total_units} onChange={change} /></div>
      </div>
      <div className="catalog-actions"><button type="submit" disabled={!categories.length}>{equipment ? 'Guardar cambios' : 'Crear equipo'}</button><button type="button" className="ghost" onClick={onCancel}>Cancelar</button></div>
    </fieldset>
  </form>;
}

function EquipmentDetail({ id, initiallyEditing, token, role, categories, onClose, onChanged }) {
  const [equipment, setEquipment] = useState(null);
  const [editing, setEditing] = useState(initiallyEditing);
  const [confirming, setConfirming] = useState(false);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState(null);
  const [revision, setRevision] = useState(0);
  const [retry, setRetry] = useState(0);
  const [file, setFile] = useState(null);
  const fileInput = useRef(null);
  const pending = useRef(null);
  useEffect(() => () => pending.current?.abort(), []);
  useEffect(() => {
    const controller = new AbortController();
    setLoading(true); setError(null);
    request(`/equipment/${id}`, { token, signal: controller.signal }).then(data => {
      if (!controller.signal.aborted) setEquipment(data);
    }).catch(failure => { if (!controller.signal.aborted) setError(failure); })
      .finally(() => { if (!controller.signal.aborted) setLoading(false); });
    return () => controller.abort();
  }, [id, token, retry]);

  async function mutate(action, message, { close = false, removeCover = false } = {}) {
    if (pending.current || role !== 'admin') return;
    const controller = new AbortController();
    pending.current = controller;
    setBusy(true); setError(null);
    try {
      const result = await action(controller.signal);
      if (controller.signal.aborted) return;
      if (result) setEquipment(result);
      if (removeCover) setEquipment(current => ({ ...current, cover_key: null }));
      setEditing(false); setConfirming(false); setFile(null);
      if (fileInput.current) fileInput.current.value = '';
      setRevision(value => value + 1);
      onChanged(message, close);
    } catch (failure) { if (!controller.signal.aborted) setError(failure); }
    finally { pending.current = null; if (!controller.signal.aborted) setBusy(false); }
  }

  function upload(event) {
    event.preventDefault();
    if (!file || !['image/jpeg', 'image/png'].includes(file.type) || file.size > 2097152) {
      setError({ message: 'Selecciona una fotografía JPG o PNG de hasta 2 MiB.' }); return;
    }
    mutate(signal => coverRequest(id, { token, file, signal }), 'Fotografía guardada.');
  }

  return <section className="panel" aria-label="Detalle del equipo">
    <h3>Detalle del equipo</h3>
    <button type="button" className="ghost" onClick={onClose}>Cerrar detalle</button>
    {loading && <Loading>Cargando equipo…</Loading>}
    <ErrorNote error={error} />
    {!loading && !equipment && <button type="button" onClick={() => setRetry(value => value + 1)}>Reintentar detalle</button>}
    {!loading && equipment && <>
      <h3>{equipment.name}</h3>
      <dl><dt>Fabricante</dt><dd>{equipment.manufacturer}</dd><dt>Referencia</dt><dd>{equipment.reference}</dd>
        <dt>Categoría</dt><dd>{equipment.category.name}</dd><dt>Disponibles / totales</dt><dd>{equipment.available_units} / {equipment.total_units}</dd></dl>
      {equipment.cover_key !== null && <Cover equipment={equipment} token={token} revision={revision} />}
      {role === 'admin' && <>
        {editing ? <EquipmentForm equipment={equipment} categories={categories} busy={busy}
          onCancel={() => setEditing(false)} onSubmit={body => mutate(signal => request(`/equipment/${id}`, { token, method: 'PUT', body, signal }), 'Equipo actualizado.')} />
          : <div className="catalog-actions"><button type="button" disabled={busy} onClick={() => { setEditing(true); setConfirming(false); }}>Editar equipo</button>
            <button type="button" disabled={busy} onClick={() => setConfirming(true)}>Desactivar equipo</button></div>}
        {confirming && <div role="group" aria-label="Confirmar desactivación"><p>¿Desactivar el equipo «{equipment.name}»? Dejará de aparecer en el catálogo.</p>
          <div className="catalog-actions"><button type="button" disabled={busy} onClick={() => mutate(signal => request(`/equipment/${id}`, { token, method: 'DELETE', signal }), 'Equipo desactivado.', { close: true })}>Confirmar desactivación</button>
            <button type="button" disabled={busy} onClick={() => setConfirming(false)}>Cancelar desactivación</button></div></div>}
        <form onSubmit={upload}><label htmlFor="equipment-cover">Fotografía (JPG o PNG, máximo 2 MiB)</label>
          <input id="equipment-cover" ref={fileInput} type="file" accept="image/jpeg,image/png" disabled={busy} onChange={event => { setFile(event.target.files[0] ?? null); setError(null); }} />
          <div className="catalog-actions"><button type="submit" disabled={busy || !file}>Subir fotografía</button>
            {equipment.cover_key !== null && <button type="button" disabled={busy} onClick={() => mutate(signal => request(`/equipment/${id}/cover`, { token, method: 'DELETE', signal }), 'Fotografía eliminada.', { removeCover: true })}>Quitar fotografía</button>}</div>
        </form>
      </>}
    </>}
    {busy && <Loading>Guardando cambios…</Loading>}
  </section>;
}

export default function Catalog({ token, role }) {
  const [filters, setFilters] = useState({ q: '', category_id: '', page: 1, size: 20 });
  const [categories, setCategories] = useState([]);
  const [categoriesLoading, setCategoriesLoading] = useState(true);
  const [categoriesError, setCategoriesError] = useState(null);
  const [categoryRetry, setCategoryRetry] = useState(0);
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [revision, setRevision] = useState(0);
  const [selected, setSelected] = useState(null);
  const [creating, setCreating] = useState(false);
  const [busy, setBusy] = useState(false);
  const [createError, setCreateError] = useState(null);
  const [notice, setNotice] = useState('');
  const pending = useRef(null);
  useEffect(() => () => pending.current?.abort(), [token, role]);
  useEffect(() => {
    const controller = new AbortController();
    setCategoriesLoading(true); setCategoriesError(null); setCategories([]);
    request('/categories', { token, signal: controller.signal }).then(result => {
      if (!controller.signal.aborted) setCategories(result.items);
    }).catch(failure => { if (!controller.signal.aborted) setCategoriesError(failure); })
      .finally(() => { if (!controller.signal.aborted) setCategoriesLoading(false); });
    return () => controller.abort();
  }, [token, categoryRetry]);
  useEffect(() => {
    const controller = new AbortController();
    const query = new URLSearchParams({ page: String(filters.page), size: String(filters.size) });
    if (filters.q) query.set('q', filters.q);
    if (filters.category_id) query.set('category_id', filters.category_id);
    setLoading(true); setError(null); setData(null);
    request(`/equipment?${query}`, { token, signal: controller.signal }).then(result => {
      if (controller.signal.aborted) return;
      if (!result.items.length && filters.page > 1) {
        setFilters(current => ({ ...current, page: Math.max(1, Math.ceil(result.total / current.size)) }));
      } else setData(result);
    }).catch(failure => { if (!controller.signal.aborted) setError(failure); })
      .finally(() => { if (!controller.signal.aborted) setLoading(false); });
    return () => controller.abort();
  }, [token, filters, revision]);

  function changed(message, close) {
    setNotice(message); setRevision(value => value + 1);
    if (close) setSelected(null);
  }
  async function create(body) {
    if (pending.current || role !== 'admin') return;
    const controller = new AbortController(); pending.current = controller;
    setBusy(true); setCreateError(null); setNotice('');
    try {
      await request('/equipment', { token, method: 'POST', body, signal: controller.signal });
      if (!controller.signal.aborted) { setCreating(false); changed('Equipo creado.'); }
    } catch (failure) { if (!controller.signal.aborted) setCreateError(failure); }
    finally { pending.current = null; if (!controller.signal.aborted) setBusy(false); }
  }
  function filter(name, value) { setFilters(current => ({ ...current, [name]: value, page: 1 })); }

  return <section className="catalog">
    <h2>Catálogo</h2><Notice>{notice}</Notice>
    <div className="catalog-filters">
      <div><label htmlFor="catalog-search">Buscar por nombre o fabricante</label><input id="catalog-search" type="search" value={filters.q} onChange={event => filter('q', event.target.value)} /></div>
      <div><label htmlFor="catalog-category">Filtrar por categoría</label><select id="catalog-category" disabled={categoriesLoading || !!categoriesError} value={filters.category_id} onChange={event => filter('category_id', event.target.value)}>
        <option value="">Todas las categorías</option>{categories.map(category => <option key={category.id} value={category.id}>{category.name}</option>)}
      </select></div>
      <div><label htmlFor="catalog-size">Equipos por página</label><select id="catalog-size" value={filters.size} onChange={event => filter('size', Number(event.target.value))}>
        {[10, 20, 50].map(size => <option key={size} value={size}>{size}</option>)}
      </select></div>
    </div>
    {categoriesLoading && <Loading>Cargando categorías…</Loading>}
    <ErrorNote error={categoriesError} />
    {categoriesError && <button type="button" onClick={() => setCategoryRetry(value => value + 1)}>Reintentar categorías</button>}
    {!categoriesLoading && !categoriesError && !categories.length && <Empty>No hay categorías disponibles.</Empty>}
    {role === 'admin' && <>
      <button type="button" disabled={creating} onClick={() => { setCreating(true); setSelected(null); setCreateError(null); setNotice(''); }}>Nuevo equipo</button>
      {creating && <section className="panel"><ErrorNote error={createError} /><EquipmentForm categories={categories} busy={busy} onSubmit={create} onCancel={() => setCreating(false)} />{busy && <Loading>Creando equipo…</Loading>}</section>}
    </>}
    {selected && <EquipmentDetail key={`${selected.id}-${selected.editing}-${token}-${role}`} {...selected} initiallyEditing={selected.editing}
      token={token} role={role} categories={categories} onClose={() => setSelected(null)} onChanged={changed} />}
    {loading && <Loading>Cargando equipos…</Loading>}
    <ErrorNote error={error} />
    {error && <button type="button" onClick={() => setRevision(value => value + 1)}>Reintentar catálogo</button>}
    {!loading && data && <>
      {!data.items.length ? <Empty>No hay equipos que coincidan con la búsqueda.</Empty> : <div className="table-scroll"><table className="data">
        <thead><tr><th>Fotografía</th><th>Nombre</th><th>Fabricante</th><th>Referencia</th><th>Categoría</th><th>Disponibles / totales</th><th>Acciones</th></tr></thead>
        <tbody>{data.items.map(equipment => <tr key={equipment.id}>
          <td>{equipment.cover_key !== null ? <Cover equipment={equipment} token={token} revision={revision} /> : 'Sin fotografía'}</td>
          <td>{equipment.name}</td><td>{equipment.manufacturer}</td><td>{equipment.reference}</td><td>{equipment.category.name}</td><td>{equipment.available_units} / {equipment.total_units}</td>
          <td><div className="catalog-actions"><button type="button" onClick={() => { setSelected({ id: equipment.id, editing: false }); setCreating(false); setNotice(''); }}>Ver detalle: {equipment.name}</button>
            {role === 'admin' && <button type="button" onClick={() => { setSelected({ id: equipment.id, editing: true }); setCreating(false); setNotice(''); }}>Editar: {equipment.name}</button>}</div></td>
        </tr>)}</tbody>
      </table></div>}
      <Pager page={data.page} size={data.size} total={data.total} onPage={page => setFilters(current => ({ ...current, page }))} />
    </>}
  </section>;
}
