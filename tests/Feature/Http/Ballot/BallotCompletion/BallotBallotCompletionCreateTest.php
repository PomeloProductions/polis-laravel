<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Ballot\BallotCompletion;

use App\Models\Vote\Ballot;
use App\Models\Vote\BallotItem;
use App\Models\Vote\BallotItemOption;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class OrganizationOrganizationManagerCreateTest
 */
final class BallotBallotCompletionCreateTest extends ApplicationTestCase
{
    use MocksApplicationLog, RolesTesting;

    /**
     * @var string
     */
    private $route;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    /**
     * Sets up the proper route for the request
     */
    private function setupRoute(int $ballotId)
    {
        $this->route = '/v1/ballots/'.$ballotId.'/ballot-completions';
    }

    public function test_organization_not_found(): void
    {
        $this->setupRoute(4523);
        $response = $this->json('POST', $this->route);
        $response->assertStatus(404);
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $ballot = Ballot::factory()->create();
        $this->setupRoute($ballot->id);
        $response = $this->json('POST', $this->route);
        $response->assertStatus(403);
    }

    public function test_create_successful(): void
    {
        $this->actAsUser();
        $ballot = Ballot::factory()->create();
        $this->setupRoute($ballot->id);

        $ballotItemOptions = BallotItemOption::factory()->count(2)->create([
            'ballot_item_id' => BallotItem::factory()->create([
                'ballot_id' => $ballot->id,
            ])->id,
        ]);

        $properties = [
            'votes' => [
                [
                    'result' => 1,
                    'ballot_item_option_id' => $ballotItemOptions[0]->id,
                ],
                [
                    'result' => 0,
                    'ballot_item_option_id' => $ballotItemOptions[0]->id,
                ],
            ],
        ];

        $response = $this->json('POST', $this->route, $properties);

        $response->assertStatus(201);

        $response->assertJson($properties);
    }

    public function test_create_fails_missing_required_fields(): void
    {
        $this->actAsUser();
        $ballot = Ballot::factory()->create();
        $this->setupRoute($ballot->id);

        $response = $this->json('POST', $this->route);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'votes' => ['The votes field is required.'],
            ],
        ]);

        $response = $this->json('POST', $this->route, [
            'votes' => [
                [
                    'hi' => 'hi',
                ],
            ],
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'votes.0.result' => ['The votes.0.result field is required.'],
                'votes.0.ballot_item_option_id' => ['The votes.0.ballot_item_option_id field is required.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_array_fields(): void
    {
        $this->actAsUser();
        $ballot = Ballot::factory()->create();
        $this->setupRoute($ballot->id);

        $data = [
            'votes' => 5435,
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'votes' => ['The votes must be an array.'],
            ],
        ]);

        $response = $this->json('POST', $this->route, [
            'votes' => [
                'hi',
            ],
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'votes.0' => ['The votes.0 must be an array.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_numerical_fields(): void
    {
        $this->actAsUser();
        $ballot = Ballot::factory()->create();
        $this->setupRoute($ballot->id);

        $data = [
            'votes' => [
                [
                    'result' => 'hi',
                    'ballot_item_option_id' => 'hi',
                ],
            ],
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'votes.0.result' => ['The votes.0.result must be an integer.'],
                'votes.0.ballot_item_option_id' => ['The votes.0.ballot_item_option_id must be an integer.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_role_id(): void
    {
        $this->actAsUser();
        $ballot = Ballot::factory()->create();
        $this->setupRoute($ballot->id);

        $data = [
            'votes' => [
                [
                    'result' => 'hi',
                    'ballot_item_option_id' => 345,
                ],
            ],
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'votes.0.ballot_item_option_id' => ['The selected votes.0.ballot_item_option_id is invalid.'],
            ],
        ]);
    }
}
