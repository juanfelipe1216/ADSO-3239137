import { execFileSync } from 'node:child_process';
import { readFileSync, mkdirSync, writeFileSync } from 'node:fs';
import { parseEnv } from 'node:util';
import { randomUUID } from 'node:crypto';
import { fileURLToPath } from 'node:url';
import assert from 'node:assert/strict';
import { withClock } from './clock-scope.mjs';

const mode = process.argv[2] || 'seed';
if (!['seed', 'performance'].includes(mode)) throw new Error('Usa seed o performance.');
const root = fileURLToPath(new URL('../', import.meta.url));
const project = 'lab-b14-' + randomUUID().slice(0, 8);
const directory = root + '/tool/results/local/' + project;
mkdirSync(directory, { recursive: true });
const clock = directory + '/clock.txt';
writeFileSync(clock, 'system');
const environment = { ...parseEnv(readFileSync(root + '/.env', 'utf8')), ...process.env, DEMO_CLOCK_DIR: directory };
const args = ['compose', '--env-file', '.env', '-p', project, '-f', 'infra/compose.yaml', '-f', 'infra/compose.demo.yaml'];
const base = 'http://127.0.0.1:' + (environment.DEMO_APP_PORT || '8083') + '/api/v1';
let token;
function compose(...command) {
  console.log('Compose: ' + command.join(' '));
  execFileSync('docker', [...args, ...command], { cwd: root, env: environment, stdio: 'inherit', timeout: 300000 });
}
async function api(path, body, method = body === undefined ? 'GET' : 'POST') {
  const response = await fetch(base + path, { method, signal: AbortSignal.timeout(15000),
    headers: { 'Content-Type': 'application/json', ...(token ? { Authorization: 'Bearer ' + token } : {}) },
    ...(body === undefined ? {} : { body: JSON.stringify(body) }) });
  const data = response.status === 204 ? null : await response.json();
  if (!response.ok) throw new Error('HTTP ' + response.status + ': ' + data.code);
  return data;
}
async function login() {
  token = (await api('/auth/login', { username: environment.ADMIN_USERNAME, password: environment.ADMIN_PASSWORD })).access_token;
}
async function equipment(count, categories) {
  const result = new Array(count);
  let next = 0;
  await Promise.all(Array.from({ length: 4 }, async () => {
    while (next < count) {
      const i = next++;
      result[i] = await api('/equipment', { name: 'Equipo demostración ' + String(i).padStart(5, '0'),
        manufacturer: 'Fabricante demostración ' + i % 10, reference: 'DEMO-' + String(i).padStart(5, '0'),
        category_id: categories[i % categories.length].id, total_units: 5 });
      if ((i + 1) % 500 === 0) console.log('Equipos creados: ' + (i + 1));
    }
  }));
  return result;
}
async function seed() {
  const past = new Date(Date.now() - 6 * 86400000).toISOString().replace(/\.\d{3}Z$/, 'Z');
  await assert.rejects(withClock(clock, past, async () => {
    await login();
    throw new Error('INJECTED_FAILURE');
  }), /INJECTED_FAILURE/);
  assert.equal(readFileSync(clock, 'utf8'), 'system');
  await login();
  const categories = (await api('/categories')).items;
  const items = await equipment(40, categories);
  const researchers = [];
  for (let i = 0; i < 10; i++) {
    researchers.push(await api('/researchers', { full_name: 'Investigador demostración ' + i, document_number: 'DEMO-' + i }));
  }
  await withClock(clock, past, async () => {
    await login();
    for (let i = 0; i < 3; i++) {
      await api('/loans', { researcher_id: researchers[i].id, items: [{ equipment_id: items[i].id }] });
    }
  });
  assert.equal(readFileSync(clock, 'utf8'), 'system');
  await login();
  for (let i = 0; i < 12; i++) {
    const loan = await api('/loans', { researcher_id: researchers[3 + i % 7].id, items: [{ equipment_id: items[3 + i].id }] });
    if (i < 10) await api('/loans/' + loan.id + '/items/' + loan.items[0].id + '/return', {});
  }
  const totals = { equipment: (await api('/equipment')).total, researchers: (await api('/researchers')).total,
    loans: (await api('/loans')).total, overdue: (await api('/loans/overdue')).items.length };
  assert.deepEqual(totals, { equipment: 40, researchers: 10, loans: 15, overdue: 3 });
  const report = await api('/reports/most-borrowed?from=' + past.slice(0, 10) + '&to=' + new Date().toISOString().slice(0, 10));
  assert.equal(report.rows.reduce((total, row) => total + row.times_borrowed, 0), 15);
  return { ...totals, report_rows: report.rows.length, clock_restored: true, injected_failure_restored: true };
}
async function performanceRun() {
  await login();
  const categories = (await api('/categories')).items;
  await equipment(5000, categories);
  assert.equal((await api('/equipment?size=1')).total, 5000);
  compose('restart', '--no-deps', 'api');
  compose('up', '-d', '--wait', '--wait-timeout', '120');
  const queries = ['', '?q=000', '?q=Fabricante', '?q=no-existe', '?category_id=' + categories[0].id,
    '?q=Equipo&category_id=' + categories[1].id, '?page=100&size=50', '?page=101&size=50'];
  const samples = [];
  for (const phase of ['cold', 'warm']) {
    for (let round = 0; round < (phase === 'cold' ? 1 : 20); round++) {
      for (const query of queries) {
        const start = performance.now();
        try {
          const data = await api('/equipment' + query);
          assert.equal(typeof data.total, 'number');
          samples.push({ phase, query, ms: performance.now() - start, ok: true });
        } catch {
          samples.push({ phase, query, ms: performance.now() - start, ok: false });
        }
      }
    }
  }
  const sorted = samples.map(row => row.ms).sort((a, b) => a - b);
  const percentile = p => sorted[Math.ceil(sorted.length * p) - 1];
  return { equipment: 5000, samples, count: samples.length, p50_ms: percentile(.5), p95_ms: percentile(.95),
    max_ms: sorted.at(-1), errors: samples.filter(row => !row.ok).length,
    passed: samples.every(row => row.ok && row.ms < 300) };
}
try {
  compose('build', 'api');
  compose('build', 'app');
  compose('up', '--no-build', '-d', '--wait', '--wait-timeout', '120');
  const result = await (mode === 'seed' ? seed() : performanceRun());
  const evidence = { mode, executed_at: new Date().toISOString(), platform: process.platform, node: process.version,
    transport: 'HTTP loopback through Nginx, four sequential data writers, serial search requests', ...result };
  writeFileSync(directory + '/result.json', JSON.stringify(evidence, null, 2));
  console.log(JSON.stringify({ ...evidence, samples: undefined }, null, 2));
  console.log('Evidencia local: ' + directory + '/result.json');
  if (result.passed === false) process.exitCode = 1;
} catch (error) {
  console.error('Falló la verificación: ' + (error.message?.startsWith('HTTP ') ? error.message : 'revisa entorno o aserciones; no se certifica el resultado.'));
  process.exitCode = 1;
} finally {
  writeFileSync(clock, 'system');
  token = undefined;
  try { compose('down', '--volumes', '--remove-orphans'); }
  catch { console.error('Limpieza pendiente del proyecto ' + project); process.exitCode = 1; }
}
