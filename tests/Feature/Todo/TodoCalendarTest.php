<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Todo;

use App\Models\Role;
use Polis\Models\User\TodoCalendar;

/**
 * HTTP feature coverage for the calendar + vacation + balances surface of
 * TodoControllerAbstract (calendarIndex/Store/Update/Destroy,
 * vacationShow/Update, balanceIndex).
 */
final class TodoCalendarTest extends TodoFeatureTestCase
{
    public function test_calendar_index_requires_authentication(): void
    {
        $other = $this->otherUser();
        $response = $this->json('GET', $this->base($other->id).'/calendars');
        $response->assertStatus(403);
    }

    public function test_calendar_index_denies_cross_user_access(): void
    {
        $this->actAs(Role::APP_USER);
        $other = $this->otherUser();
        $response = $this->json('GET', $this->base($other->id).'/calendars');
        $response->assertStatus(403);
    }

    public function test_calendar_store_creates_a_calendar(): void
    {
        $this->actAs(Role::APP_USER);

        $response = $this->json('POST', $this->base($this->actingAs->id).'/calendars', [
            'name' => 'Weekdays',
            'days_of_week' => [1, 2, 3, 4, 5],
            'is_exclusion' => false,
        ]);

        $response->assertStatus(201);
        $response->assertJson(['name' => 'Weekdays', 'user_id' => $this->actingAs->id]);
        $this->assertDatabaseHas('todo_calendars', [
            'user_id' => $this->actingAs->id,
            'name' => 'Weekdays',
        ]);
    }

    public function test_calendar_update_modifies_a_calendar(): void
    {
        $this->actAs(Role::APP_USER);
        $calendar = TodoCalendar::create([
            'user_id' => $this->actingAs->id,
            'name' => 'Old',
            'days_of_week' => [1],
        ]);

        $response = $this->json('PUT', $this->base($this->actingAs->id).'/calendars/'.$calendar->id, [
            'name' => 'New',
            'days_of_week' => [6, 0],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['id' => $calendar->id, 'name' => 'New']);
    }

    public function test_calendar_update_rejects_calendars_owned_by_others(): void
    {
        $this->actAs(Role::APP_USER);
        $foreignCalendar = TodoCalendar::create([
            'user_id' => $this->otherUser()->id,
            'name' => 'Theirs',
            'days_of_week' => [1],
        ]);

        $response = $this->json('PUT', $this->base($this->actingAs->id).'/calendars/'.$foreignCalendar->id, [
            'name' => 'Hijack',
            'days_of_week' => [1],
        ]);

        $response->assertStatus(403);
    }

    public function test_calendar_destroy_removes_a_calendar(): void
    {
        $this->actAs(Role::APP_USER);
        $calendar = TodoCalendar::create([
            'user_id' => $this->actingAs->id,
            'name' => 'Bye',
            'days_of_week' => [1],
        ]);

        $response = $this->json('DELETE', $this->base($this->actingAs->id).'/calendars/'.$calendar->id);

        $response->assertStatus(204);
        $this->assertSoftDeleted('todo_calendars', ['id' => $calendar->id]);
    }

    public function test_vacation_show_reports_no_open_period_by_default(): void
    {
        $this->actAs(Role::APP_USER);
        $response = $this->json('GET', $this->base($this->actingAs->id).'/vacation');
        $response->assertStatus(200);
        $response->assertJson(['on_vacation' => false, 'current_period' => null]);
    }

    public function test_vacation_update_toggles_vacation_on(): void
    {
        $this->actAs(Role::APP_USER);

        $response = $this->json('PUT', $this->base($this->actingAs->id).'/vacation', [
            'on_vacation' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['on_vacation' => true]);
        $this->assertDatabaseHas('todo_vacation_periods', [
            'user_id' => $this->actingAs->id,
            'end_date' => null,
        ]);
    }

    public function test_balance_index_returns_the_users_balances(): void
    {
        $this->actAs(Role::APP_USER);
        $response = $this->json('GET', $this->base($this->actingAs->id).'/balances');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }
}
