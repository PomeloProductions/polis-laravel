<?php

declare(strict_types=1);

namespace Polis\Mail;

/**
 * In-code default email templates.
 *
 * These act as the final fallback in EmailTemplateRenderingService's lookup
 * hierarchy: org-specific DB row -> global DB row -> the entry here -> throw.
 *
 * Why in-code defaults? Day-1 deploys of a new template-emitting feature
 * shouldn't require a DB seeding pass first; templates are still editable at
 * runtime via the admin UI (which writes EmailTemplate rows), but a working
 * default ships with the code.
 *
 * Copy was extracted from PolisOS's existing mailer blade templates and
 * listener subject strings (preserved as-is where possible). The inline PHP
 * expressions were converted to Blade-style {{ var.path }} placeholders;
 * see EmailTemplateRenderingService for the interpolation rules.
 *
 * To add a new template:
 *   1. Add an entry here keyed by the template's stable identifier (snake_case).
 *   2. Wire any new listener/job to call TemplatedMailable with that key.
 *   3. Optionally seed an EmailTemplate DB row to make it editable.
 */
final class DefaultEmailTemplates
{
    /**
     * @var array<string, array{subject: string, body_html: string}>
     */
    public const TEMPLATES = [
        'welcome' => [
            'subject' => 'Welcome to {{ app.name }}!',
            'body_html' => '<p>Hi {{ user.first_name }},</p>'
                .'<p>Thanks for taking a look at {{ app.name }}! We are pretty excited to get this into the hands of everyone. '
                .'We hope that through the input of users like you, we can help start a narrative of in what way we could '
                .'change the way that groups of people organize and discourse with each other.</p>'
                .'<p>Please take a look at everything in the system, and let us know what we can change or improve. '
                .'This project is meaningless without your input.</p>'
                .'<p>Also as an additional bit of information. We promise you that we will never share your contact information '
                .'to anyone outside of our team.</p>'
                .'<p>Thanks again,</p>'
                .'<p>{{ app.name }}</p>',
        ],
        'organization_manager_added' => [
            'subject' => 'You have been granted access to the organization {{ organization.name }}.',
            'body_html' => '<p>Hi {{ user.first_name }},</p>'
                .'<p>You have been added as a manager of {{ organization.name }} with the role of {{ organization_role }}.</p>'
                .'<p>Your temporary password is: <strong>{{ temp_password }}</strong></p>'
                .'<p>Please log in and change your password as soon as possible.</p>',
        ],
        'renewal_reminder' => [
            'subject' => 'Membership Renewal Reminder',
            'body_html' => '<p>Hi {{ user.first_name }},</p>'
                .'<p>This is a reminder that your subscription to {{ membership_name }} will expire in two weeks.</p>'
                .'<p>Your renewal cost will be {{ membership_cost }}.</p>'
                .'<p>{{ recurring_message }}</p>',
        ],
        'renewal_receipt' => [
            'subject' => '{{ app.name }} Membership Successfully Renewed',
            'body_html' => '<p>Hi {{ user.first_name }},</p>'
                .'<p>Your subscription to {{ membership_name }} has been renewed for {{ membership_cost }}.</p>'
                .'<p>Your new expiration date is {{ expiration_date }}.</p>'
                .'<p>Thank you for your continued support!</p>',
        ],
        'renewal_failure' => [
            'subject' => '{{ app.name }} Membership Renewal Failed',
            'body_html' => '<p>Hi {{ user.first_name }},</p>'
                .'<p>We were unable to renew your {{ membership_name }} subscription.</p>'
                .'<p>Reason: {{ failure_reason }}</p>'
                .'<p>Your membership is currently set to expire on {{ expiration_date }}. '
                .'To keep your access uninterrupted, please log in and update your payment method.</p>'
                .'<p>If you believe this is an error, please contact support.</p>'
                .'<p>Thanks,</p>'
                .'<p>{{ app.name }}</p>',
        ],
        'membership_expired' => [
            'subject' => '{{ app.name }} Membership Expired',
            'body_html' => '<p>Hi {{ user.first_name }},</p>'
                .'<p>Your {{ membership_name }} subscription expired on {{ expiration_date }} '
                .'and has not been renewed.</p>'
                .'<p>We would love to have you back. Please log in to renew your membership '
                .'and restore your access.</p>'
                .'<p>Thanks,</p>'
                .'<p>{{ app.name }}</p>',
        ],
    ];
}
