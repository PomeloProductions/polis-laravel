<?php

declare(strict_types=1);

namespace Polis\Events\Organization;

use App\Models\Organization\OrganizationManager;

/**
 * Class OrganizationManagerCreatedEvent
 */
class OrganizationManagerCreatedEvent
{
    /**
     * @var OrganizationManager
     */
    private $organizationManager;

    /**
     * @var string|null
     */
    private $tempPassword;

    /**
     * OrganizationManagerCreatedEvent constructor.
     */
    public function __construct(OrganizationManager $organizationManager, ?string $tempPassword = null)
    {
        $this->organizationManager = $organizationManager;
        $this->tempPassword = $tempPassword;
    }

    public function getOrganizationManager(): OrganizationManager
    {
        return $this->organizationManager;
    }

    public function getTempPassword(): ?string
    {
        return $this->tempPassword;
    }
}
