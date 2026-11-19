import { test } from 'node:test';
import assert from 'node:assert/strict';
import { mkdtemp, writeFile, readFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { withClock } from './clock-scope.mjs';

test('restores original clock bytes after success and an injected failure', async () => {
  const dir = await mkdtemp(join(tmpdir(), 'lab-clock-'));
  const path = join(dir, 'clock.txt');
  try {
    await writeFile(path, 'system\n');
    const result = await withClock(path, '2040-01-01T00:00:00Z', async () => {
      assert.equal(await readFile(path, 'utf8'), '2040-01-01T00:00:00Z');
      return 42;
    });
    assert.equal(result, 42);
    assert.equal(await readFile(path, 'utf8'), 'system\n');
    await assert.rejects(withClock(path, '2040-01-01T00:00:00Z', async () => { throw new Error('injected'); }), /injected/);
    assert.equal(await readFile(path, 'utf8'), 'system\n');
  } finally { await rm(dir, { recursive: true }); }
});
