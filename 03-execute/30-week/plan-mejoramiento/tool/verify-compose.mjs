import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { parseEnv } from 'node:util';
import { randomUUID } from 'node:crypto';
import { fileURLToPath } from 'node:url';
import assert from 'node:assert/strict';

const root = fileURLToPath(new URL('../', import.meta.url));
const environment = { ...parseEnv(readFileSync(new URL('../.env', import.meta.url), 'utf8')), ...process.env };
const project = 'lab-b13-check-' + randomUUID().slice(0, 8);
const args = ['compose', '--env-file', '.env', '-p', project, '-f', 'infra/compose.yaml', '-f', 'infra/compose.verify.yaml'];
const base = 'http://127.0.0.1:' + (environment.VERIFY_APP_PORT || '8082');
function compose(...command) {
  console.log('Etapa: ' + command.join(' '));
  execFileSync('docker', [...args, ...command], { cwd: root, env: environment, stdio: 'inherit', timeout: 300000 });
}
async function verify() {
  const response = await fetch(base, { signal: AbortSignal.timeout(10000) });
  assert.equal(response.status, 200);
  const html = await response.text();
  assert.match(html, /<div id="root"><\/div>/);
  const script = html.match(/src="([^"]+\.js)"/)[1];
  assert.equal((await fetch(base + script)).status, 200);
  assert.deepEqual(await (await fetch(base + '/api/v1/health')).json(), { status: 'ok' });
  const login = await fetch(base + '/api/v1/auth/login', { method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username: environment.ADMIN_USERNAME, password: environment.ADMIN_PASSWORD }) });
  assert.equal(login.status, 200);
  const session = await login.json();
  assert.equal(session.role, 'admin');
  const categories = await fetch(base + '/api/v1/categories', { headers: { Authorization: 'Bearer ' + session.access_token } });
  assert.equal(categories.status, 200);
  assert.equal((await categories.json()).items.length, 5);
  const denied = await fetch(base + '/api/v1/categories');
  assert.equal(denied.status, 401);
  assert.equal((await denied.json()).code, 'UNAUTHENTICATED');
}
try {
  console.log('Verificando Compose en un proyecto temporal con volúmenes nuevos…');
  compose('build', 'api');
  compose('build', 'app');
  compose('up', '--no-build', '-d', '--wait', '--wait-timeout', '120');
  await verify();
  compose('restart', '--no-deps', 'api', 'app');
  compose('up', '-d', '--wait', '--wait-timeout', '120');
  await verify();
  console.log('Correcto: tres servicios, frontend, proxy, login, categorías y reinicio.');
} catch {
  console.error('Falló la comprobación aislada. Revisa Docker, configuración y disponibilidad del puerto de verificación.');
  process.exitCode = 1;
} finally {
  // The random project name scopes removal to the resources created by this run.
  try { compose('down', '--volumes', '--remove-orphans'); }
  catch { console.error('No se pudo limpiar el proyecto temporal: ' + project); process.exitCode = 1; }
}
