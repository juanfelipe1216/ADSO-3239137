import React, { useState } from 'react';
import { VIEWS } from './views/index.js';
import './workspace.css';

const ROLE_LABEL = { admin: 'administrador', lab_technician: 'laboratorista' };

export default function Workspace({ session, onLogout }) {
  const views = VIEWS.filter(view => view.roles.includes(session.role));
  const [current, setCurrent] = useState(views[0].id);
  const view = views.find(item => item.id === current) ?? views[0];
  const { Component } = view;
  return <>
    <div className="session-bar">
      <span>Sesión iniciada como <strong>{ROLE_LABEL[session.role] ?? session.role}</strong></span>
      <button type="button" className="ghost" onClick={onLogout}>Cerrar sesión</button>
    </div>
    <nav className="tabs" aria-label="Secciones">
      {views.map(item => <button key={item.id} type="button" className={item.id === view.id ? 'tab active' : 'tab'}
        aria-current={item.id === view.id ? 'page' : undefined} onClick={() => setCurrent(item.id)}>{item.label}</button>)}
    </nav>
    <main className="workspace-main"><Component token={session.token} role={session.role} /></main>
  </>;
}
