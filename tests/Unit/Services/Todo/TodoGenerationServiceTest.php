<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Todo;

use Polis\Services\Todo\TodoGenerationService;
use Polis\Tests\TestCase;

final class TodoGenerationServiceTest extends TestCase
{
    public function test_apply_copy_rules_reset(): void
    {
        $service = $this->createPartialService();

        $config = [
            'label' => 'Test',
            'items' => [
                ['id' => '1', 'text' => 'Item 1', 'tally' => 5, 'on_copy' => 'reset', 'completed' => true],
            ],
        ];

        $result = $service->applyCopyRules($config);

        $this->assertEquals(0, $result['items'][0]['tally']);
        $this->assertFalse($result['items'][0]['completed']);
    }

    public function test_apply_copy_rules_increment(): void
    {
        $service = $this->createPartialService();

        $config = [
            'items' => [
                ['id' => '1', 'text' => 'Streak', 'tally' => 31, 'on_copy' => 'increment'],
            ],
        ];

        $result = $service->applyCopyRules($config);

        $this->assertEquals(32, $result['items'][0]['tally']);
    }

    public function test_apply_copy_rules_preserve(): void
    {
        $service = $this->createPartialService();

        $config = [
            'items' => [
                ['id' => '1', 'text' => 'Keep', 'tally' => 42, 'on_copy' => 'preserve'],
            ],
        ];

        $result = $service->applyCopyRules($config);

        $this->assertEquals(42, $result['items'][0]['tally']);
    }

    public function test_apply_copy_rules_with_sub_items(): void
    {
        $service = $this->createPartialService();

        $config = [
            'items' => [
                [
                    'id' => '1',
                    'text' => 'Parent',
                    'tally' => 3,
                    'on_copy' => 'reset',
                    'sub_items' => [
                        ['id' => 's1', 'text' => 'Sub', 'tally' => 10, 'on_copy' => 'increment'],
                    ],
                ],
            ],
        ];

        $result = $service->applyCopyRules($config);

        $this->assertEquals(0, $result['items'][0]['tally']);
        $this->assertEquals(11, $result['items'][0]['sub_items'][0]['tally']);
    }

    public function test_apply_copy_rules_with_groups(): void
    {
        $service = $this->createPartialService();

        $config = [
            'groups' => [
                [
                    'group_number' => 1,
                    'count_this_group' => 5,
                    'on_copy' => 'reset',
                    'items' => [
                        ['id' => '1', 'text' => 'Item', 'tally' => 2, 'on_copy' => 'preserve'],
                    ],
                ],
            ],
        ];

        $result = $service->applyCopyRules($config);

        $this->assertEquals(0, $result['groups'][0]['count_this_group']);
        $this->assertEquals(2, $result['groups'][0]['items'][0]['tally']);
    }

    public function test_apply_copy_rules_with_projects_resets_logged_hours(): void
    {
        $service = $this->createPartialService();

        $config = [
            'projects' => [
                ['id' => 'p1', 'name' => 'Alpha', 'budgeted_hours' => 4, 'logged_hours' => 3, 'deficit' => 1],
            ],
        ];

        $result = $service->applyCopyRules($config);

        $this->assertEquals(0, $result['projects'][0]['logged_hours']);
    }

    public function test_apply_copy_rules_default_is_reset(): void
    {
        $service = $this->createPartialService();

        $config = [
            'items' => [
                ['id' => '1', 'text' => 'No on_copy', 'tally' => 10],
            ],
        ];

        $result = $service->applyCopyRules($config);

        $this->assertEquals(0, $result['items'][0]['tally']);
    }

    protected function createPartialService(): TodoGenerationService
    {
        $pageRepo = mock(\Polis\Contracts\Repositories\User\UserPageRepositoryContract::class);
        $componentRepo = mock(\Polis\Contracts\Repositories\User\UserPageComponentRepositoryContract::class);
        $settingRepo = mock(\Polis\Contracts\Repositories\User\TodoSettingRepositoryContract::class);
        $templateRepo = mock(\Polis\Contracts\Repositories\User\TodoTemplateRepositoryContract::class);

        return new TodoGenerationService($pageRepo, $componentRepo, $settingRepo, $templateRepo);
    }
}
