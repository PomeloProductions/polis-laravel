# Coverage Baseline Report

Captured against the `Unit` test suite in `phpunit.xml` (Orchestra
Testbench harness, no consumer-app classes available).

## Headline numbers

| Metric          | Before this PR | After this PR | Delta            |
| --------------- | -------------- | ------------- | ---------------- |
| Tests           | 70             | 167           | **+97**          |
| Assertions      | 126            | 303           | +177             |
| Classes covered | 15 / 379       | 38 / 379      | +23 classes      |
| Methods covered | 39 / 1153      | 86 / 1152     | +47 methods      |
| **Line %**      | **5.42%**      | **11.13%**    | **+5.71 pts**    |
| Lines covered   | 245 / 4519     | 503 / 4518    | +258 lines       |

(Class/method totals dropped by 1 because we collapsed one no-op class
along the way — net counts measured against the final source tree.)

## CI wiring

- `pcov` extension is now requested via `shivammathur/setup-php` in
  `.github/workflows/tests.yml`.
- Each job runs `phpunit --coverage-clover=coverage.xml --coverage-text`,
  uploads the clover XML as an artifact, and writes a summary line to
  `$GITHUB_STEP_SUMMARY`.
- The PHP 8.4 job additionally runs `tools/check-coverage-threshold.php`
  which fails the job if line coverage drops below the floor.

## Threshold

`tools/check-coverage-threshold.php` enforces a floor of **9.00%** —
roughly (final - 2pts), per the rule that PRs cannot regress the locked
floor. Raise it as more tests land.

## What got covered (now ≥80% line coverage)

| Class                                                    | Lines     |
| -------------------------------------------------------- | --------- |
| `Polis\Exceptions\Handler`                               | 95%+      |
| `Polis\Http\Middleware\SearchFilterParsingMiddleware`    | 100%      |
| `Polis\Http\Middleware\ExpandParsingMiddleware`          | 100%      |
| `Polis\Http\Middleware\Issue404IfPageAfterPaginationMiddleware` | 100% |
| `Polis\Http\Middleware\LogMiddleware`                    | 100%      |
| `Polis\Http\Middleware\TrimStrings`                      | 100%      |
| `Polis\Models\Traits\HasValidationRules`                 | 100%      |
| `Polis\Traits\CanGetAndUnset`                            | 100%      |
| `Polis\Mail\TemplatedMailable`                           | 100%      |
| `Polis\Mail\RenderedEmail` / `DefaultEmailTemplates`     | 100%      |
| `Polis\Push\RenderedPushNotification` / `DefaultPushTemplates` | 100% |
| `Polis\Services\Messaging\EmailTemplateRenderingService` | 100%      |
| `Polis\Services\Messaging\PushTemplateRenderingService`  | 100%      |
| `Polis\Listeners\User\SignUpListener`                    | 100%      |
| `Polis\Listeners\Organization\OrganizationManagerCreatedListener` | 100% |
| `Polis\Listeners\Statistic\StatisticCreatedListener`     | 100%      |
| `Polis\Listeners\Statistic\StatisticUpdatedListener`     | 100%      |
| All `Polis\Exceptions\*` exception classes               | 100%      |
| `Polis\Providers\BaseAuthServiceProvider::guessPolicyName` | covered |
| `Polis\Providers\BaseValidatorProvider::boot`            | covered   |
| `Polis\Validators\OwnedByValidator`                      | 100%      |
| `Polis\Validators\BaseValidatorAbstract`                 | 100%      |
| `Polis\Validators\NotPresentValidator`                   | 100%      |
| `Polis\Services\TokenGenerationService`                  | 100%      |
| `Polis\Services\StringHelperService`                     | 100%      |
| `Polis\Services\DirectoryCopyService`                    | 100%      |
| `Polis\Services\ArchiveHelperService`                    | 100%      |
| `Polis\Services\ProratingCalculationService::calculateRemainingYearlyCharge` | 100% |
| `Polis\Services\Wiki\ArticleVersionCalculationService`   | ~96%      |

Early-return branches covered for:

- `Polis\Validators\ArticleVersion\SelectedIterationBelongsToArticleValidator`
- `Polis\Validators\ForgotPassword\TokenIsNotExpiredValidator`
- `Polis\Validators\ForgotPassword\UserOwnsTokenValidator`
- `Polis\Validators\InvitationTokenIsValidValidator`

## Intentionally NOT covered in this PR — requires consumer-app fixtures

These files reference `App\Models\*` or `AdminUI\Laravel\EloquentJoin`
in a way that prevents standalone instantiation/mocking inside the
package's Testbench harness. They have rich coverage in the existing
`Consumer-Only` suite and will move to the standalone Unit suite as
each underlying contract is widened to accept package-side fixtures.

- `Polis\Models\BaseModelAbstract` (uses `AdminUI\Laravel\EloquentJoin\Traits\EloquentJoin`)
- All `Polis\Models\*` Eloquent models (extend `BaseModelAbstract`)
- `Polis\Repositories\BaseRepositoryAbstract` + concrete repositories
  (constructor parameters and `update()`/`create()` signatures require
  concrete `BaseModelAbstract` subclasses; mocks can't satisfy the type
  hint without the EloquentJoin trait)
- `Polis\Policies\*` (`use App\Models\Role`, `App\Models\User\User` etc.)
- `Polis\Http\Core\Controllers\*` (depend on Eloquent models + Laravel
  controller bus)
- `Polis\Services\StripeCustomerService`, `StripePaymentService` (need
  Cartalyst Stripe + Eloquent fixtures)
- `Polis\Services\Wiki\ArticleModificationApplicationService`
  (`App\Models\Wiki\*`)
- `Polis\Services\Statistic\*` (operate on `App\Models\Statistic\*`)
- `Polis\Listeners\Vote\VoteCreatedListener` and most Payment / Article
  / UserMerge listeners (their repository `update()` calls require
  BaseModelAbstract subclasses)
- `Polis\ThreadSecurity\PrivateThreadGate`, `GeneralThreadGate`
  (constructor params and method signatures use `App\Models\User\User`
  and `App\Models\Messaging\Thread`)
- `Polis\Console\Commands\ChargeRenewal::handle` body (full Stripe +
  Subscription fixtures needed; only constructor shape is verified by
  the standalone test)

## Real bugs surfaced

None. The new tests exercised previously-uncovered branches and they
all behaved as specified — no source changes were required.
