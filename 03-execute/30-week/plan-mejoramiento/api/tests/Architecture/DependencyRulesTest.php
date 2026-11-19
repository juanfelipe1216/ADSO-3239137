<?php
declare(strict_types=1);
namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class DependencyRulesTest extends TestCase
{
    private function violations(string $source, string $layer): array
    {
        $errors = [];
        $tokens = token_get_all($source);
        foreach ($tokens as $index => $token) {
            if (!is_array($token)) {
                continue;
            }
            if (in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                $name = ltrim($token[1], '\\');
                $allowed = $name === 'Lab\\Domain' || str_starts_with($name, 'Lab\\Domain\\')
                    || ($layer === 'Application' && ($name === 'Lab\\Application' || str_starts_with($name, 'Lab\\Application\\')));
                if (!$allowed && str_contains($name, '\\')) {
                    $errors[] = $name;
                }
            }
            if ($token[0] === T_STRING && in_array(strtolower($token[1]), ['app', 'config', 'env', 'response', 'resolve', 'db'], true)) {
                $next = $index + 1;
                while (isset($tokens[$next]) && is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) {
                    $next++;
                }
                if (($tokens[$next] ?? null) === '(' || ($tokens[$next][0] ?? null) === T_DOUBLE_COLON) {
                    $errors[] = $token[1];
                }
            }
        }
        return $errors;
    }

    private function files(string $directory): array
    {
        $paths = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $paths[] = $file->getPathname();
            }
        }
        return $paths;
    }

    public function test_core_has_no_framework_or_adapter_dependencies(): void
    {
        foreach (['Domain', 'Application'] as $layer) {
            foreach ($this->files(__DIR__ . '/../../src/' . $layer) as $path) {
                self::assertSame([], $this->violations(file_get_contents($path), $layer), $path);
            }
        }
    }

    public function test_detector_rejects_deliberately_forbidden_dependencies(): void
    {
        foreach (['<?php use Illuminate\\Http\\Request;', '<?php new \\Lab\\Adapters\\Time\\SystemClock();',
            '<?php app("db");', '<?php use Lab\\Application\\Ports\\Clock;'] as $source) {
            self::assertNotEmpty($this->violations($source, 'Domain'));
        }
        self::assertSame([], $this->violations('<?php use Lab\\Domain\\User;', 'Application'));
    }

    private function concreteConstruction(string $source): bool
    {
        $aliases = [];
        preg_match_all('/use\s+(Lab\\\\Adapters\\\\[A-Za-z0-9_\\\\]+)(?:\s+as\s+(\w+))?\s*;/', $source, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $aliases[] = $match[2] ?? substr($match[1], strrpos($match[1], '\\') + 1);
        }
        foreach (token_get_all($source) as $token) {
            if (is_array($token) && $token[0] === T_NEW) {
                $nextIsClass = true;
            } elseif (is_array($token) && ($nextIsClass ?? false) && $token[0] !== T_WHITESPACE) {
                if (in_array($token[1], $aliases, true) || str_starts_with(ltrim($token[1], '\\'), 'Lab\\Adapters\\')) {
                    return true;
                }
                $nextIsClass = false;
            }
        }
        return false;
    }

    public function test_only_provider_constructs_infrastructure(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['src', 'app', 'routes', 'bootstrap'] as $directory) {
            foreach ($this->files($root . '/' . $directory) as $path) {
                if (basename($path) !== 'LabServiceProvider.php') {
                    self::assertFalse($this->concreteConstruction(file_get_contents($path)), $path);
                }
            }
        }
        self::assertTrue($this->concreteConstruction('<?php use Lab\\Adapters\\Time\\SystemClock as Timer; new Timer();'));
        self::assertTrue($this->concreteConstruction('<?php new \\Lab\\Adapters\\Time\\SystemClock();'));
    }
}
