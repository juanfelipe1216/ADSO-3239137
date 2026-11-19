import Catalog from './Catalog.jsx';
import Researchers from './Researchers.jsx';
import Loans from './Loans.jsx';
import Overdue from './Overdue.jsx';
import Report from './Report.jsx';
import Staff from './Staff.jsx';

// Cada vista recibe { token, role } y es dueña de sus llamadas a /api/v1.
const BOTH = ['admin', 'lab_technician'];
export const VIEWS = [
  { id: 'catalog', label: 'Catálogo', roles: BOTH, Component: Catalog },
  { id: 'researchers', label: 'Investigadores', roles: BOTH, Component: Researchers },
  { id: 'loans', label: 'Préstamos', roles: BOTH, Component: Loans },
  { id: 'overdue', label: 'Vencidos', roles: BOTH, Component: Overdue },
  { id: 'report', label: 'Reporte', roles: ['admin'], Component: Report },
  { id: 'staff', label: 'Laboratoristas', roles: ['admin'], Component: Staff },
];
