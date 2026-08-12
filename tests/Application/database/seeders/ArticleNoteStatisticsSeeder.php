<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Statistic\Statistic;
use App\Models\Statistic\StatisticFilter;
use Illuminate\Database\Seeder;

/**
 * Class ArticleNoteStatisticsSeeder
 */
class ArticleNoteStatisticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Create the total_notes statistic if it doesn't exist
        $totalNotesStatistic = Statistic::firstOrCreate(
            ['name' => 'total_notes', 'model' => 'article'],
            [
                'relation' => 'articleNotes',
                'public' => true,
            ]
        );

        // Create the total_completed_notes statistic if it doesn't exist
        $totalCompletedNotesStatistic = Statistic::firstOrCreate(
            ['name' => 'total_completed_notes', 'model' => 'article'],
            [
                'relation' => 'articleNotes',
                'public' => true,
            ]
        );

        // Add filter to only count notes where completed_at is not null
        StatisticFilter::firstOrCreate(
            [
                'statistic_id' => $totalCompletedNotesStatistic->id,
                'field' => 'completed_at',
            ],
            [
                'operator' => '!=',
                'value' => null,
            ]
        );
    }
}
