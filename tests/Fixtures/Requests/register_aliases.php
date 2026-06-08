<?php

declare(strict_types=1);

/**
 * Bulk-register class_aliases that map every consumer-side
 * App\Http\Core\Requests\* FQCN referenced by the abstract controllers
 * back to the single Polis\Tests\Fixtures\Requests\StubRequest class.
 *
 * Why: the abstract controllers type-hint these App\* classes on their
 * action methods. Without these aliases Mockery cannot proxy them
 * (because PHP can't resolve the class FQCN), and the controllers cannot
 * be called with a mock request in standalone tests.
 *
 * See StubRequest.php for the rationale.
 */

use Polis\Tests\Fixtures\Requests\StubRequest;

$requestAliases = [
    // Article
    'App\\Http\\Core\\Requests\\Article\\IndexRequest',
    'App\\Http\\Core\\Requests\\Article\\StoreRequest',
    'App\\Http\\Core\\Requests\\Article\\UpdateRequest',
    'App\\Http\\Core\\Requests\\Article\\ViewRequest',
    'App\\Http\\Core\\Requests\\Article\\ArticleVersion\\IndexRequest',
    'App\\Http\\Core\\Requests\\Article\\ArticleVersion\\StoreRequest',
    'App\\Http\\Core\\Requests\\Article\\Iteration\\IndexRequest',
    // Authentication
    'App\\Http\\Core\\Requests\\Authentication\\LoginRequest',
    'App\\Http\\Core\\Requests\\Authentication\\SignUpRequest',
    // Ballot
    'App\\Http\\Core\\Requests\\Ballot\\ViewRequest',
    'App\\Http\\Core\\Requests\\Ballot\\BallotCompletion\\StoreRequest',
    // Category
    'App\\Http\\Core\\Requests\\Category\\IndexRequest',
    'App\\Http\\Core\\Requests\\Category\\StoreRequest',
    'App\\Http\\Core\\Requests\\Category\\UpdateRequest',
    'App\\Http\\Core\\Requests\\Category\\ViewRequest',
    'App\\Http\\Core\\Requests\\Category\\DeleteRequest',
    // Collection
    'App\\Http\\Core\\Requests\\Collection\\ViewRequest',
    'App\\Http\\Core\\Requests\\Collection\\UpdateRequest',
    'App\\Http\\Core\\Requests\\Collection\\DeleteRequest',
    'App\\Http\\Core\\Requests\\Collection\\CollectionItem\\IndexRequest',
    'App\\Http\\Core\\Requests\\Collection\\CollectionItem\\StoreRequest',
    // CollectionItem (top-level)
    'App\\Http\\Core\\Requests\\CollectionItem\\ViewRequest',
    'App\\Http\\Core\\Requests\\CollectionItem\\DeleteRequest',
    // Entity\Asset
    'App\\Http\\Core\\Requests\\Entity\\Asset\\IndexRequest',
    'App\\Http\\Core\\Requests\\Entity\\Asset\\StoreRequest',
    'App\\Http\\Core\\Requests\\Entity\\Asset\\UpdateRequest',
    'App\\Http\\Core\\Requests\\Entity\\Asset\\DeleteRequest',
    // Entity\Collection
    'App\\Http\\Core\\Requests\\Entity\\Collection\\IndexRequest',
    'App\\Http\\Core\\Requests\\Entity\\Collection\\StoreRequest',
    // Entity\Payment
    'App\\Http\\Core\\Requests\\Entity\\Payment\\IndexRequest',
    // Entity\PaymentMethod
    'App\\Http\\Core\\Requests\\Entity\\PaymentMethod\\StoreRequest',
    'App\\Http\\Core\\Requests\\Entity\\PaymentMethod\\UpdateRequest',
    'App\\Http\\Core\\Requests\\Entity\\PaymentMethod\\DeleteRequest',
    // Entity\ProfileImage
    'App\\Http\\Core\\Requests\\Entity\\ProfileImage\\StoreRequest',
    // Entity\Subscription
    'App\\Http\\Core\\Requests\\Entity\\Subscription\\IndexRequest',
    'App\\Http\\Core\\Requests\\Entity\\Subscription\\StoreRequest',
    'App\\Http\\Core\\Requests\\Entity\\Subscription\\UpdateRequest',
    // Feature
    'App\\Http\\Core\\Requests\\Feature\\IndexRequest',
    'App\\Http\\Core\\Requests\\Feature\\ViewRequest',
    // ForgotPassword
    'App\\Http\\Core\\Requests\\ForgotPassword\\ForgotPasswordRequest',
    'App\\Http\\Core\\Requests\\ForgotPassword\\ResetPasswordRequest',
    // InvitationToken
    'App\\Http\\Core\\Requests\\InvitationToken\\IndexRequest',
    'App\\Http\\Core\\Requests\\InvitationToken\\StoreRequest',
    'App\\Http\\Core\\Requests\\InvitationToken\\ViewRequest',
    'App\\Http\\Core\\Requests\\InvitationToken\\UpdateRequest',
    'App\\Http\\Core\\Requests\\InvitationToken\\DeleteRequest',
    // MembershipPlan
    'App\\Http\\Core\\Requests\\MembershipPlan\\IndexRequest',
    'App\\Http\\Core\\Requests\\MembershipPlan\\StoreRequest',
    'App\\Http\\Core\\Requests\\MembershipPlan\\UpdateRequest',
    'App\\Http\\Core\\Requests\\MembershipPlan\\ViewRequest',
    'App\\Http\\Core\\Requests\\MembershipPlan\\DeleteRequest',
    'App\\Http\\Core\\Requests\\MembershipPlan\\MembershipPlanRate\\IndexRequest',
    // Message
    'App\\Http\\Core\\Requests\\Message\\StoreRequest',
    // Organization
    'App\\Http\\Core\\Requests\\Organization\\IndexRequest',
    'App\\Http\\Core\\Requests\\Organization\\StoreRequest',
    'App\\Http\\Core\\Requests\\Organization\\UpdateRequest',
    'App\\Http\\Core\\Requests\\Organization\\ViewRequest',
    'App\\Http\\Core\\Requests\\Organization\\DeleteRequest',
    'App\\Http\\Core\\Requests\\Organization\\OrganizationManager\\IndexRequest',
    'App\\Http\\Core\\Requests\\Organization\\OrganizationManager\\StoreRequest',
    'App\\Http\\Core\\Requests\\Organization\\OrganizationManager\\UpdateRequest',
    'App\\Http\\Core\\Requests\\Organization\\OrganizationManager\\DeleteRequest',
    // Resource
    'App\\Http\\Core\\Requests\\Resource\\IndexRequest',
    // Role
    'App\\Http\\Core\\Requests\\Role\\IndexRequest',
    // Statistic
    'App\\Http\\Core\\Requests\\Statistic\\IndexRequest',
    'App\\Http\\Core\\Requests\\Statistic\\StoreRequest',
    'App\\Http\\Core\\Requests\\Statistic\\UpdateRequest',
    'App\\Http\\Core\\Requests\\Statistic\\ViewRequest',
    'App\\Http\\Core\\Requests\\Statistic\\DeleteRequest',
    // User
    'App\\Http\\Core\\Requests\\User\\IndexRequest',
    'App\\Http\\Core\\Requests\\User\\StoreRequest',
    'App\\Http\\Core\\Requests\\User\\UpdateRequest',
    'App\\Http\\Core\\Requests\\User\\ViewRequest',
    'App\\Http\\Core\\Requests\\User\\DeleteRequest',
    'App\\Http\\Core\\Requests\\User\\MeRequest',
    'App\\Http\\Core\\Requests\\User\\ArticleNote\\IndexRequest',
    'App\\Http\\Core\\Requests\\User\\ArticleNote\\StoreRequest',
    'App\\Http\\Core\\Requests\\User\\ArticleNote\\UpdateRequest',
    'App\\Http\\Core\\Requests\\User\\ArticleNote\\ViewRequest',
    'App\\Http\\Core\\Requests\\User\\ArticleNote\\DeleteRequest',
    'App\\Http\\Core\\Requests\\User\\ArticleNote\\RandomArticleRequest',
    'App\\Http\\Core\\Requests\\User\\BallotCompletion\\IndexRequest',
    'App\\Http\\Core\\Requests\\User\\Contact\\IndexRequest',
    'App\\Http\\Core\\Requests\\User\\Contact\\StoreRequest',
    'App\\Http\\Core\\Requests\\User\\Contact\\UpdateRequest',
    'App\\Http\\Core\\Requests\\User\\Thread\\IndexRequest',
    'App\\Http\\Core\\Requests\\User\\Thread\\StoreRequest',
    'App\\Http\\Core\\Requests\\User\\Thread\\Message\\IndexRequest',
    'App\\Http\\Core\\Requests\\User\\Thread\\Message\\StoreRequest',
    'App\\Http\\Core\\Requests\\User\\Thread\\Message\\UpdateRequest',
    'App\\Http\\Core\\Requests\\User\\UserPage\\IndexRequest',
    'App\\Http\\Core\\Requests\\User\\UserPage\\StoreRequest',
    'App\\Http\\Core\\Requests\\User\\UserPage\\UpdateRequest',
    'App\\Http\\Core\\Requests\\User\\UserPage\\DeleteRequest',
    'App\\Http\\Core\\Requests\\User\\UserPageComponent\\IndexRequest',
    'App\\Http\\Core\\Requests\\User\\UserPageComponent\\StoreRequest',
    'App\\Http\\Core\\Requests\\User\\UserPageComponent\\UpdateRequest',
    'App\\Http\\Core\\Requests\\User\\UserPageComponent\\DeleteRequest',
    // Wiki\ArticleSummary
    'App\\Http\\Core\\Requests\\Wiki\\ArticleSummary\\ViewRequest',
    'App\\Http\\Core\\Requests\\Wiki\\ArticleSummary\\StoreRequest',
    'App\\Http\\Core\\Requests\\Wiki\\ArticleSummary\\UpdateRequest',
    'App\\Http\\Core\\Requests\\Wiki\\ArticleSummary\\DeleteRequest',
];

foreach ($requestAliases as $alias) {
    if (! class_exists($alias, false)) {
        class_alias(StubRequest::class, $alias);
    }
}
