import { readFile, writeFile, rename } from 'node:fs/promises';

export async function setClock(path, value) {
  await writeFile(path + '.next', value, { mode: 0o600 });
  await rename(path + '.next', path);
}
export async function withClock(path, value, action) {
  const original = await readFile(path, 'utf8');
  try {
    await setClock(path, value);
    return await action();
  } finally {
    await setClock(path, original);
  }
}
