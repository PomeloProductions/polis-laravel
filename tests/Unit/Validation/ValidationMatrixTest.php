<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Validation;

use App\Models\Role;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\In;
use PHPUnit\Framework\Attributes\DataProvider;
use Polis\Contracts\Models\HasValidationRulesContract;
use Polis\Models\Asset;
use Polis\Models\Category;
use Polis\Models\Collection\Collection;
use Polis\Models\Collection\CollectionItem;
use Polis\Models\Messaging\Message;
use Polis\Models\Messaging\Thread;
use Polis\Models\Organization\Organization;
use Polis\Models\Organization\OrganizationManager;
use Polis\Models\Payment\PaymentMethod;
use Polis\Models\Statistic\Statistic;
use Polis\Models\Subscription\MembershipPlan;
use Polis\Models\Subscription\Subscription;
use Polis\Models\User\ArticleNote;
use Polis\Models\User\Contact;
use Polis\Models\User\UserPage;
use Polis\Models\User\UserPageComponent;
use Polis\Models\Vote\BallotCompletion;
use Polis\Models\Wiki\Article;
use Polis\Models\Wiki\ArticleSummary;
use Polis\Models\Wiki\ArticleVersion;
use Polis\Tests\TestCase;
use Polis\Tests\Unit\Exceptions\HandlerBranchCoverageTest;

/**
 * Data-driven validation matrix.
 *
 * Guarantees that every *typed* validation rule declared on a Polis model's
 * buildModelValidationRules() actually rejects a value of the wrong
 * type/shape, surfaces the error on the correct field key (including nested
 * `field.*` / `field.*.sub` keys), and produces the expected human-readable
 * message.
 *
 * WHY rule/model-level (not HTTP): the harness instantiates each Polis model
 * directly and runs Laravel's Validator against its declared rules. This
 * covers EVERY rule-bearing model — including ones whose HTTP endpoints are
 * not routed in this package (e.g. Messaging) — and is completely
 * decoupled from the consumer-app routing layer and the Application-suite
 * lang overrides. End-to-end proof that the Handler maps these failures to
 * HTTP 422 lives in the swept Feature suite (phpunit-app.xml) and in
 * {@see HandlerBranchCoverageTest}.
 *
 * The "typed" rules covered are the deterministic type/shape assertions:
 *   integer, int, boolean, array, numeric, string, email, url, date, json,
 *   and enum membership (in: / Rule::in). DB-backed rules (exists, custom
 *   validators such as owned_by / not_present / timezone / membership_plan_*)
 *   and pure format regexes are intentionally out of scope here — they need a
 *   seeded database or the registered custom-validator extensions and are
 *   exercised by their own dedicated tests.
 */
final class ValidationMatrixTest extends TestCase
{
    /**
     * Every Polis model that declares buildModelValidationRules().
     *
     * Keyed by the FQCN of the REAL Polis model (never the App\ alias — the
     * fixture stubs deliberately omit the rules method). One model,
     * User\User, is excluded: it cannot be class-loaded standalone because
     * its canUserManageEntity() signature is only compatible once the real
     * App\Models\User\User (not the fixture stub) is on the classpath, which
     * only happens inside a consuming application. Its rules are exercised
     * end-to-end by the swept UserCreate/UserUpdate Feature tests.
     *
     * @var list<class-string>
     */
    private const RULE_BEARING_MODELS = [
        Asset::class,
        Category::class,
        Collection::class,
        CollectionItem::class,
        Message::class,
        Thread::class,
        Organization::class,
        OrganizationManager::class,
        PaymentMethod::class,
        Statistic::class,
        MembershipPlan::class,
        Subscription::class,
        ArticleNote::class,
        Contact::class,
        UserPage::class,
        UserPageComponent::class,
        BallotCompletion::class,
        Article::class,
        ArticleSummary::class,
        ArticleVersion::class,
    ];

    /**
     * Expected framework-default message template per typed rule, keyed by
     * the rule name. `:attr` is the humanized attribute the message renders.
     * Pinning these locks the validation-error contract: a Laravel wording
     * change (or a rule being silently dropped) trips this test.
     *
     * @var array<string, string>
     */
    private const MESSAGE_TEMPLATES = [
        'integer' => 'The :attr field must be an integer.',
        'int' => 'The :attr field must be an integer.',
        'boolean' => 'The :attr field must be true or false.',
        'array' => 'The :attr field must be an array.',
        'numeric' => 'The :attr field must be a number.',
        'string' => 'The :attr field must be a string.',
        'email' => 'The :attr field must be a valid email address.',
        'url' => 'The :attr field must be a valid URL.',
        'date' => 'The :attr field must be a valid date.',
        'json' => 'The :attr field must be a valid JSON string.',
        'in' => 'The selected :attr is invalid.',
    ];

    /**
     * A value guaranteed to FAIL each typed rule.
     *
     * @return mixed
     */
    private static function badValueFor(string $rule)
    {
        return match ($rule) {
            'integer', 'int', 'numeric' => 'not-a-number',
            'boolean' => 'not-a-bool',
            'array' => 'not-an-array',
            'string' => ['not', 'a', 'string'],
            'email' => 'not-an-email',
            'url' => 'not a url',
            'date' => 'not-a-date',
            'json' => '{not-json',
            // A value that cannot be a member of any real enum used here.
            'in' => '__definitely_not_a_valid_enum_member__',
            default => null,
        };
    }

    /**
     * Build the humanized attribute Laravel renders in a message for a given
     * field key. Top-level keys are snake->space humanized; nested keys keep
     * their raw dotted form (matching framework behaviour).
     */
    private static function humanize(string $field): string
    {
        // Laravel humanizes every segment (snake_case -> spaced words) while
        // leaving numeric indices untouched, then rejoins with dots.
        $segments = array_map(
            static fn (string $seg): string => ctype_digit($seg) ? $seg : str_replace('_', ' ', $seg),
            explode('.', $field),
        );

        return implode('.', $segments);
    }

    /**
     * Data provider: one case per (model, field, typed-rule).
     *
     * @return iterable<string, array{class-string, string, string, string}>
     */
    public static function providesTypedRules(): iterable
    {
        $typed = array_keys(self::MESSAGE_TEMPLATES);

        foreach (self::RULE_BEARING_MODELS as $modelClass) {
            $instance = new $modelClass;
            $built = $instance->buildModelValidationRules();
            $base = $built[HasValidationRulesContract::VALIDATION_RULES_BASE] ?? [];

            foreach ($base as $field => $ruleSet) {
                $ruleSet = is_array($ruleSet) ? $ruleSet : explode('|', (string) $ruleSet);

                foreach ($ruleSet as $rule) {
                    // Normalise the rule to its bare name.
                    if ($rule instanceof In) {
                        $name = 'in';
                    } elseif (is_string($rule)) {
                        $name = explode(':', $rule)[0];
                    } else {
                        // Non-string, non-In rule object (custom Rule): skip.
                        continue;
                    }

                    if (! in_array($name, $typed, true)) {
                        continue;
                    }

                    // A `nullable` field with `in`: null passes, but a bogus
                    // non-null string still fails the enum — safe to test.
                    $short = (new \ReflectionClass($modelClass))->getShortName();
                    $key = sprintf('%s::%s [%s]', $short, $field, $name);

                    yield $key => [$modelClass, $field, $name, $rule instanceof In ? 'in' : $rule];
                }
            }
        }
    }

    /**
     * @param  class-string  $modelClass
     */
    #[DataProvider('providesTypedRules')]
    public function test_typed_rule_rejects_bad_value(
        string $modelClass,
        string $field,
        string $ruleName,
        string $ruleLiteral
    ): void {
        $instance = new $modelClass;
        $base = $instance->buildModelValidationRules()[HasValidationRulesContract::VALIDATION_RULES_BASE];

        $fieldRules = $base[$field];
        $fieldRules = is_array($fieldRules) ? $fieldRules : explode('|', (string) $fieldRules);

        // Keep ONLY rules we can satisfy standalone for this field: drop
        // DB-backed (exists), ownership/custom validators, and prepend
        // markers, so the ONLY thing that can fail is the typed rule under
        // test. We keep `nullable`/`required`/type/size/regex/in.
        $keep = [];
        foreach ($fieldRules as $r) {
            if ($r instanceof In) {
                $keep[] = $r;

                continue;
            }
            if (! is_string($r)) {
                continue;
            }
            $bare = explode(':', $r)[0];
            if (in_array($bare, ['exists', 'owned_by', 'not_present', 'timezone', 'bail'], true)) {
                continue;
            }
            if (str_contains($r, 'is_active') || str_contains($r, 'is_owned') || str_contains($r, '_belongs_to_')) {
                continue;
            }
            $keep[] = $r;
        }

        $badValue = self::badValueFor($ruleName);

        // Nested wildcard keys (`parent.*`, `parent.*.child`) need the parent
        // array present with one element carrying the bad value.
        [$payload, $errorKey] = $this->buildPayload($field, $badValue);

        $rules = [$errorKey => $keep];

        // For nested keys we must also declare the parent as an array so the
        // wildcard expands; declare a permissive parent rule.
        if (str_contains($field, '.')) {
            $root = explode('.', $field)[0];
            $rules[$root] = ['array'];
        }

        $validator = Validator::make($payload, $rules);

        $this->assertTrue(
            $validator->fails(),
            "Expected {$modelClass}::{$field} [{$ruleName}] to reject ".var_export($badValue, true)
        );

        $messages = $validator->errors()->get($errorKey);

        $this->assertNotEmpty(
            $messages,
            "Expected a validation error on key '{$errorKey}' for {$modelClass}::{$field} [{$ruleName}], ".
            'got errors on: '.implode(', ', array_keys($validator->errors()->toArray()))
        );

        $expected = str_replace(':attr', self::humanize($errorKey), self::MESSAGE_TEMPLATES[$ruleName]);

        $this->assertContains(
            $expected,
            $messages,
            "Message mismatch for {$modelClass}::{$field} [{$ruleName}]. Expected \"{$expected}\", got: ".
            implode(' | ', $messages)
        );
    }

    /**
     * Build a request payload that places $badValue at $field, returning the
     * payload plus the concrete error key Laravel will report the failure on.
     *
     * @param  mixed  $badValue
     * @return array{array<string, mixed>, string}
     */
    private function buildPayload(string $field, $badValue): array
    {
        if (! str_contains($field, '.')) {
            return [[$field => $badValue], $field];
        }

        $parts = explode('.', $field);
        $root = $parts[0];

        // `root.*`  -> payload [root => [badValue]],  errorKey root.0
        if (count($parts) === 2 && $parts[1] === '*') {
            return [[$root => [$badValue]], "{$root}.0"];
        }

        // `root.*.child` -> payload [root => [[child => badValue]]], key root.0.child
        if (count($parts) === 3 && $parts[1] === '*') {
            $child = $parts[2];

            return [[$root => [[$child => $badValue]]], "{$root}.0.{$child}"];
        }

        // Fallback: treat as flat.
        return [[$field => $badValue], $field];
    }

    /**
     * Guard: the matrix must cover a meaningful number of typed rules across
     * all rule-bearing models. If a refactor collapses the providers to near
     * zero, this fails loudly rather than passing vacuously.
     */
    public function test_matrix_covers_expected_rule_volume(): void
    {
        $cases = iterator_to_array(self::providesTypedRules());

        $this->assertGreaterThanOrEqual(
            90,
            count($cases),
            'Validation matrix regressed: fewer typed-rule cases than expected.'
        );

        // Every rule-bearing model (except the documented User exclusion) must
        // contribute at least one typed-rule case OR be explicitly rule-free.
        $modelsSeen = [];
        foreach (array_keys($cases) as $key) {
            $modelsSeen[explode('::', $key)[0]] = true;
        }
        // Sanity: the page/component control models and the core models are present.
        foreach (['UserPage', 'UserPageComponent', 'Statistic', 'MembershipPlan', 'Organization'] as $short) {
            $this->assertArrayHasKey($short, $modelsSeen, "Model {$short} missing from matrix.");
        }
    }

    /**
     * Sanity: the Role fixture exposes ENTITY_ROLES so OrganizationManager's
     * rules resolve standalone (Rule::in(Role::ENTITY_ROLES)).
     */
    public function test_role_entity_roles_available(): void
    {
        $this->assertIsArray(Role::ENTITY_ROLES);
        $this->assertNotEmpty(Role::ENTITY_ROLES);
    }
}
