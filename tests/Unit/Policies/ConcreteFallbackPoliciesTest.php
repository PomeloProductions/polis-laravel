<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Policies;

use PHPUnit\Framework\Attributes\DataProvider;
use Polis\Providers\BaseAuthServiceProvider;
use Polis\Tests\TestCase;
use ReflectionClass;

/**
 * Guards the package's default concrete fallback policies.
 *
 * Every `Polis\Policies\...PolicyAbstract` (except the shared base classes)
 * ships alongside a concrete `Polis\Policies\...Policy` so consumers no longer
 * need an empty `App\Policies\...Policy` shim: when no consumer override
 * exists, {@see BaseAuthServiceProvider::guessPolicyName()}
 * resolves to the concrete here and Laravel's gate can instantiate it.
 *
 * This test asserts the invariant that keeps that fallback usable:
 *  - the concrete exists for each leaf abstract,
 *  - it is NOT abstract (so the gate can `new` it), and
 *  - it extends the corresponding abstract.
 */
final class ConcreteFallbackPoliciesTest extends TestCase
{
    /**
     * Leaf policy abstracts that a model can be guessed onto. The two shared
     * base classes (BasePolicyAbstract, BaseBelongsToOrganizationPolicyAbstract)
     * are intentionally excluded — no model resolves to them directly.
     *
     * @return array<int, array{0: class-string}>
     */
    public static function abstractPolicyProvider(): array
    {
        $srcDir = dirname(__DIR__, 3).'/src/Policies';
        $cases = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($srcDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (! preg_match('/^abstract\s+(?:final\s+)?class\s+(\w+PolicyAbstract)\b/m', $contents, $m)) {
                continue;
            }

            $short = $m[1];
            if (in_array($short, ['BasePolicyAbstract', 'BaseBelongsToOrganizationPolicyAbstract'], true)) {
                continue;
            }

            preg_match('/^namespace\s+([^;]+);/m', $contents, $nm);
            $fqcn = $nm[1].'\\'.$short;
            $cases[$short] = [$fqcn];
        }

        return $cases;
    }

    /**
     * @param  class-string  $abstractFqcn
     */
    #[DataProvider('abstractPolicyProvider')]
    public function test_each_leaf_abstract_has_an_instantiable_concrete_fallback(string $abstractFqcn): void
    {
        // Polis\Policies\X\YPolicyAbstract -> Polis\Policies\X\YPolicy
        $concreteFqcn = substr($abstractFqcn, 0, -strlen('Abstract'));

        $this->assertTrue(
            class_exists($concreteFqcn),
            "Missing concrete fallback policy {$concreteFqcn} for {$abstractFqcn}. "
            .'Consumers rely on this so they can drop empty App\\Policies shims.'
        );

        $reflection = new ReflectionClass($concreteFqcn);

        $this->assertFalse(
            $reflection->isAbstract(),
            "{$concreteFqcn} must be concrete so Laravel's gate can instantiate it."
        );

        $this->assertTrue(
            $reflection->isSubclassOf($abstractFqcn),
            "{$concreteFqcn} must extend {$abstractFqcn}."
        );
    }
}
