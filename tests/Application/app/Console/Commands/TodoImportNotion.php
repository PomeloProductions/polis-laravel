<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Contracts\Repositories\User\TodoSettingRepositoryContract;
use Polis\Contracts\Repositories\User\UserPageComponentRepositoryContract;
use Polis\Contracts\Repositories\User\UserPageRepositoryContract;
use Polis\Models\User\UserPage;

class TodoImportNotion extends Command
{
    protected $signature = 'todo:import-notion {path} {--email=bryce@polisapp.com} {--password=password}';

    protected $description = 'Import todo data from a Notion export directory';

    public function __construct(
        protected UserPageRepositoryContract $pageRepository,
        protected UserPageComponentRepositoryContract $componentRepository,
        protected TodoSettingRepositoryContract $settingRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->argument('path');
        if (!is_dir($path)) {
            $this->error("Directory not found: {$path}");
            return self::FAILURE;
        }

        // Find or create user
        $email = $this->option('email');
        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'email' => $email,
                'first_name' => 'Bryce',
                'password' => Hash::make($this->option('password')),
            ]);
            $this->info("Created user: {$user->email} (id: {$user->id})");
        } else {
            $this->info("Using existing user: {$user->email} (id: {$user->id})");
        }

        // Create todo settings (week starts Sunday)
        $existingSetting = $this->settingRepository->findAll([
            ['user_id', '=', $user->id],
        ])->first();
        if (!$existingSetting) {
            $this->settingRepository->create([
                'user_id' => $user->id,
                'week_start_day' => 0, // Sunday
            ]);
        }

        // Create dashboard page
        $dashboard = $this->findOrCreatePage($user, [
            'slug' => 'home',
            'name' => 'Dashboard',
            'icon' => 'IconHome',
            'route_path' => 'home',
            'page_type' => 'dashboard',
            'display_order' => 0,
            'is_visible' => true,
            'is_required' => true,
            'is_nav_item' => true,
            'config_json' => [],
        ]);

        // Create todo root page
        $rootPage = $this->findOrCreatePage($user, [
            'slug' => 'todos',
            'name' => 'Todos',
            'icon' => 'IconChecklist',
            'route_path' => 'todos',
            'page_type' => 'todo',
            'display_order' => 1,
            'is_visible' => true,
            'is_required' => false,
            'is_nav_item' => true,
            'parent_page_id' => $dashboard->id,
            'config_json' => [
                'todo_level' => 'root',
                'week_start_day' => 0,
            ],
        ]);

        // Find the year markdown file (e.g., "2026 2dbda57446e780a08d54f236adcc2f52.md")
        $yearMdFiles = glob($path . '/2026 *.md');
        if (empty($yearMdFiles)) {
            $this->error("No year markdown file found");
            return self::FAILURE;
        }

        $yearContent = file_get_contents($yearMdFiles[0]);
        $yearGoals = $this->parseGoals($yearContent);

        // Create year page
        $yearPage = $this->findOrCreatePage($user, [
            'slug' => 'todo-2026',
            'name' => '2026',
            'icon' => 'IconCalendar',
            'route_path' => 'todo-2026',
            'page_type' => 'todo',
            'display_order' => 2026,
            'is_visible' => false,
            'is_required' => false,
            'is_nav_item' => false,
            'parent_page_id' => $rootPage->id,
            'config_json' => [
                'todo_level' => 'year',
                'todo_year' => 2026,
            ],
        ]);

        // Add year goals component
        if (!empty($yearGoals)) {
            $this->createComponent($yearPage, 'todo_bullet_list', 0, [
                'label' => 'Goals',
                'items' => $this->goalsToItems($yearGoals),
            ]);
        }

        $this->info("Created year page: 2026");

        // Process months
        $yearDir = $path . '/2026';
        $months = [
            'January' => 1,
            'February' => 2,
            'March' => 3,
        ];

        foreach ($months as $monthName => $monthNum) {
            $this->processMonth($user, $yearPage, $yearDir, $monthName, $monthNum);
        }

        $this->info("Import complete!");
        return self::SUCCESS;
    }

    protected function processMonth(User $user, UserPage $yearPage, string $yearDir, string $monthName, int $monthNum): void
    {
        // Find month markdown file
        $monthMdFiles = glob($yearDir . "/{$monthName} *.md");
        if (empty($monthMdFiles)) {
            $this->warn("No markdown for month: {$monthName}");
            return;
        }

        $monthContent = file_get_contents($monthMdFiles[0]);
        $monthGoals = $this->parseGoals($monthContent);

        $monthSlug = "todo-2026-" . str_pad((string) $monthNum, 2, '0', STR_PAD_LEFT);
        $monthPage = $this->findOrCreatePage($user, [
            'slug' => $monthSlug,
            'name' => $monthName,
            'icon' => 'IconCalendar',
            'route_path' => $monthSlug,
            'page_type' => 'todo',
            'display_order' => $monthNum,
            'is_visible' => false,
            'is_required' => false,
            'is_nav_item' => false,
            'parent_page_id' => $yearPage->id,
            'config_json' => [
                'todo_level' => 'month',
                'todo_month' => $monthNum,
                'todo_year' => 2026,
            ],
        ]);

        if (!empty($monthGoals)) {
            $this->createComponent($monthPage, 'todo_bullet_list', 0, [
                'label' => 'Goals',
                'items' => $this->goalsToItems($monthGoals),
            ]);
        }

        $this->info("  Created month page: {$monthName}");

        // Process weeks in this month directory
        $monthDir = $yearDir . '/' . $monthName;
        if (!is_dir($monthDir)) {
            return;
        }

        $weekDirs = glob($monthDir . '/Week *');
        // Filter to directories only
        $weekDirs = array_filter($weekDirs, 'is_dir');

        foreach ($weekDirs as $weekDir) {
            $weekDirName = basename($weekDir);
            // Also find the week markdown file
            $weekMdFiles = glob($monthDir . '/' . $weekDirName . ' *.md');
            // Try alternate: the md file might be in monthDir
            if (empty($weekMdFiles)) {
                $weekMdFiles = glob($monthDir . '/' . str_replace('/', ' ', $weekDirName) . '*.md');
            }

            $weekContent = !empty($weekMdFiles) ? file_get_contents($weekMdFiles[0]) : '';
            $this->processWeek($user, $monthPage, $weekDir, $weekDirName, $weekContent, $monthNum);
        }
    }

    protected function processWeek(User $user, UserPage $monthPage, string $weekDir, string $weekDirName, string $weekContent, int $monthNum): void
    {
        // Parse week number and dates from directory name like "Week 4 (3 22 - 3 28)"
        if (!preg_match('/Week\s+(\d+)\s+\((\d+)\s+(\d+)\s*-\s*(\d+)\s+(\d+)\)?/', $weekDirName, $matches)) {
            $this->warn("  Could not parse week dir: {$weekDirName}");
            return;
        }

        $weekNum = (int) $matches[1];
        $startMonth = (int) $matches[2];
        $startDay = (int) $matches[3];
        $endMonth = (int) $matches[4];
        $endDay = (int) $matches[5];

        $weekStartDate = Carbon::create(2026, $startMonth, $startDay);
        $weekEndDate = Carbon::create(2026, $endMonth, $endDay);

        $weekSlug = "todo-week-{$weekStartDate->format('Y-m-d')}";
        $weekName = "Week {$weekNum} ({$startMonth}/{$startDay} - {$endMonth}/{$endDay})";

        $weekGoals = $this->parseGoals($weekContent);

        $weekPage = $this->findOrCreatePage($user, [
            'slug' => $weekSlug,
            'name' => $weekName,
            'icon' => 'IconCalendar',
            'route_path' => $weekSlug,
            'page_type' => 'todo',
            'display_order' => $weekNum,
            'is_visible' => false,
            'is_required' => false,
            'is_nav_item' => false,
            'parent_page_id' => $monthPage->id,
            'config_json' => [
                'todo_level' => 'week',
                'todo_week_start' => $weekStartDate->toDateString(),
                'todo_week_end' => $weekEndDate->toDateString(),
                'todo_year' => 2026,
                'todo_month' => $monthNum,
            ],
        ]);

        if (!empty($weekGoals)) {
            $this->createComponent($weekPage, 'todo_bullet_list', 0, [
                'label' => 'Goals',
                'items' => $this->goalsToItems($weekGoals),
            ]);
        }

        $this->info("    Created week page: {$weekName}");

        // Process day files
        $dayFiles = glob($weekDir . '/*.md');
        foreach ($dayFiles as $dayFile) {
            $this->processDay($user, $weekPage, $dayFile);
        }
    }

    protected function processDay(User $user, UserPage $weekPage, string $dayFile): void
    {
        $filename = basename($dayFile);
        // Parse day name and date from filename like "Saturday 3 28 331da57446e780ecadd1c03f4320fb8b.md"
        if (!preg_match('/^(\w+)\s+(\d+)\s+(\d+)\s/', $filename, $matches)) {
            return;
        }

        $dayName = $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];
        $date = Carbon::create(2026, $month, $day);
        $dateStr = $date->format('Y-m-d');

        $daySlug = "todo-{$dateStr}";
        $dayDisplayName = "{$dayName} {$month}/{$day}";

        $dayPage = $this->findOrCreatePage($user, [
            'slug' => $daySlug,
            'name' => $dayDisplayName,
            'icon' => 'IconCalendar',
            'route_path' => $daySlug,
            'page_type' => 'todo',
            'display_order' => $day,
            'is_visible' => false,
            'is_required' => false,
            'is_nav_item' => false,
            'parent_page_id' => $weekPage->id,
            'config_json' => [
                'todo_level' => 'day',
                'todo_date' => $dateStr,
                'todo_year' => 2026,
                'todo_month' => $month,
            ],
        ]);

        $content = file_get_contents($dayFile);
        $this->parseDayContent($dayPage, $content);

        $this->info("      Created day page: {$dayDisplayName}");
    }

    protected function parseDayContent(UserPage $dayPage, string $content): void
    {
        $displayOrder = 0;

        // 1. Current Work Priority List
        $priorities = $this->extractSection($content, 'Current Work Priority List');
        if ($priorities) {
            $items = $this->parsePriorityList($priorities);
            $this->createComponent($dayPage, 'todo_ordered_list', $displayOrder++, [
                'label' => 'Current Work Priority List',
                'items' => $items,
            ]);
        }

        // 2. Work Backlog
        $backlog = $this->extractSection($content, 'Work Backlog');
        if ($backlog) {
            $categories = $this->parseBacklog($backlog);
            $this->createComponent($dayPage, 'todo_categorized_list', $displayOrder++, [
                'label' => 'Work Backlog',
                'categories' => $categories,
            ]);
        }

        // 3. Work Hours
        $workHours = $this->extractSection($content, 'Work Hours');
        if ($workHours) {
            $projects = $this->parseWorkHours($workHours, $content);
            $this->createComponent($dayPage, 'todo_time_tracker', $displayOrder++, [
                'label' => 'Work Hours',
                'projects' => $projects,
            ]);
        }

        // 4. Life Management
        $lifeManagement = $this->extractSection($content, 'Life Management');
        if ($lifeManagement) {
            $categories = $this->parseLifeManagement($lifeManagement);
            $this->createComponent($dayPage, 'todo_categorized_list', $displayOrder++, [
                'label' => 'Life Management',
                'time_budget' => ['hours' => 1, 'schedule' => 'everyday, 2.5 hours workdays'],
                'categories' => $categories,
            ]);
        }

        // 5. Active Hobbies - Language Study
        $activeHobbies = $this->extractSection($content, 'Active Hobbies');
        if ($activeHobbies) {
            $this->parseActiveHobbies($dayPage, $activeHobbies, $displayOrder);
            $displayOrder += 4; // language, read, write, game
        }

        // 6. Passive Hobbies
        $passiveHobbies = $this->extractSection($content, 'Passive Hobbies');
        if ($passiveHobbies) {
            $this->parsePassiveHobbies($dayPage, $passiveHobbies, $displayOrder);
        }
    }

    protected function parseActiveHobbies(UserPage $dayPage, string $content, int &$displayOrder): void
    {
        // Language Study
        $langSection = $this->extractSubSection($content, 'Language Study');
        if ($langSection) {
            $groups = $this->parsePriorityGroups($langSection['body']);
            $tally = $langSection['tally'];
            $this->createComponent($dayPage, 'todo_priority_groups', $displayOrder++, [
                'label' => 'Language Study',
                'time_budget' => ['hours' => 0.5, 'schedule' => 'everyday'],
                'description' => '1 lesson of duolingo + flash cards',
                'tally' => $tally,
                'on_copy' => 'reset',
                'groups' => $groups,
            ]);
        }

        // Read
        $readSection = $this->extractSubSection($content, 'Read');
        if ($readSection) {
            $groups = $this->parsePriorityGroups($readSection['body']);
            $this->createComponent($dayPage, 'todo_priority_groups', $displayOrder++, [
                'label' => 'Read',
                'time_budget' => ['hours' => 0.25, 'schedule' => 'everyday'],
                'tally' => $readSection['tally'],
                'on_copy' => 'reset',
                'groups' => $groups,
            ]);
        }

        // Write
        $writeSection = $this->extractSubSection($content, 'Write');
        if ($writeSection) {
            $groups = $this->parsePriorityGroups($writeSection['body']);
            $this->createComponent($dayPage, 'todo_priority_groups', $displayOrder++, [
                'label' => 'Write',
                'time_budget' => ['hours' => 0.25, 'schedule' => 'everyday'],
                'tally' => $writeSection['tally'],
                'on_copy' => 'reset',
                'groups' => $groups,
            ]);
        }

        // Game
        $gameSection = $this->extractSubSection($content, 'Game');
        if ($gameSection) {
            $this->createComponent($dayPage, 'todo_tally_list', $displayOrder++, [
                'label' => 'Game',
                'tally' => $gameSection['tally'],
                'on_copy' => 'reset',
                'items' => $this->parseGameItems($gameSection['body']),
            ]);
        }
    }

    protected function parsePassiveHobbies(UserPage $dayPage, string $content, int &$displayOrder): void
    {
        // Watch Serials
        $serialsSection = $this->extractSubSection($content, 'Watch.*?Serials');
        if ($serialsSection) {
            $groups = $this->parsePercentileGroups($serialsSection['body']);
            $this->createComponent($dayPage, 'todo_priority_groups', $displayOrder++, [
                'label' => 'Watch Serials',
                'time_budget' => ['hours' => 2, 'schedule' => 'everyday'],
                'tally' => $serialsSection['tally'],
                'on_copy' => 'reset',
                'groups' => $groups,
            ]);
        }

        // Watch a movie
        $movieSection = $this->extractSubSection($content, 'Watch a movie');
        if ($movieSection) {
            $groups = $this->parsePriorityGroups($movieSection['body']);
            $this->createComponent($dayPage, 'todo_priority_groups', $displayOrder++, [
                'label' => 'Watch a Movie',
                'tally' => $movieSection['tally'],
                'on_copy' => 'reset',
                'groups' => $groups,
            ]);
        }

        // Listen to a composition
        $musicSection = $this->extractSubSection($content, 'Listen to a composition');
        if ($musicSection) {
            $groups = $this->parsePriorityGroups($musicSection['body']);
            $this->createComponent($dayPage, 'todo_priority_groups', $displayOrder++, [
                'label' => 'Listen to a Composition',
                'tally' => $musicSection['tally'],
                'on_copy' => 'reset',
                'groups' => $groups,
            ]);
        }
    }

    // ─── Parsing helpers ────────────────────────────────────────────────

    protected function parseGoals(string $content): array
    {
        $goals = [];
        if (preg_match('/# Goals\s*\n((?:- .+\n?)+)/m', $content, $match)) {
            preg_match_all('/- (~~)?(.+?)(~~)?\s*$/m', $match[1], $items);
            foreach ($items[2] as $i => $text) {
                $goals[] = [
                    'text' => trim($text),
                    'completed' => !empty($items[1][$i]),
                ];
            }
        }
        return $goals;
    }

    protected function goalsToItems(array $goals): array
    {
        return array_map(fn ($goal, $i) => [
            'id' => 'goal-' . ($i + 1),
            'text' => $goal['text'],
            'completed' => $goal['completed'],
            'on_copy' => 'reset',
        ], $goals, array_keys($goals));
    }

    protected function extractSection(string $content, string $heading): ?string
    {
        // Match ### heading or ## heading or # heading
        $pattern = '/#{1,3}\s+' . preg_quote($heading, '/') . '.*?\n(.*?)(?=\n#{1,3}\s|\n## |\n# |$)/s';
        if (preg_match($pattern, $content, $match)) {
            return trim($match[1]);
        }
        return null;
    }

    protected function extractSubSection(string $content, string $label): ?array
    {
        // Match "- N. Label (time) X count" or "- Label X count"
        $pattern = '/- (?:\d+\.\s+)?' . $label . '(?:\s+\([^)]*\))*(?:\s+\([^)]*\))*\s+X\s+([\d.]+)\s*\n((?:\s+.+\n)*)/m';
        if (preg_match($pattern, $content, $match)) {
            return [
                'tally' => (float) $match[1],
                'body' => $match[2],
            ];
        }
        return null;
    }

    protected function parsePriorityList(string $content): array
    {
        $items = [];
        $lines = explode("\n", $content);
        $currentItem = null;

        foreach ($lines as $line) {
            if (preg_match('/^\d+\.\s+(.+)$/', $line, $match)) {
                if ($currentItem) {
                    $items[] = $currentItem;
                }
                $currentItem = [
                    'id' => 'wp-' . (count($items) + 1),
                    'text' => trim($match[1]),
                    'on_copy' => 'preserve',
                    'sub_items' => [],
                ];
            } elseif ($currentItem && preg_match('/^\s+\d+\.\s+(.+)$/', $line, $match)) {
                $currentItem['sub_items'][] = [
                    'id' => 'wp-' . (count($items) + 1) . '-sub-' . (count($currentItem['sub_items']) + 1),
                    'text' => trim($match[1]),
                    'on_copy' => 'preserve',
                ];
            }
        }
        if ($currentItem) {
            $items[] = $currentItem;
        }

        return $items;
    }

    protected function parseBacklog(string $content): array
    {
        $categories = [];
        $lines = explode("\n", $content);
        $currentCategory = null;

        foreach ($lines as $line) {
            if (preg_match('/^- (.+)$/', $line, $match)) {
                if ($currentCategory) {
                    $categories[] = $currentCategory;
                }
                $currentCategory = [
                    'id' => 'bl-' . (count($categories) + 1),
                    'name' => trim($match[1]),
                    'items' => [],
                ];
            } elseif ($currentCategory && preg_match('/^\s+(?:\d+\.|-)\s+(.+)$/', $line, $match)) {
                $text = trim($match[1]);
                // Skip sub-sub-items for now
                if (preg_match('/^\s{8,}/', $line)) {
                    continue;
                }
                $currentCategory['items'][] = [
                    'id' => 'bl-' . count($categories) . '-' . count($currentCategory['items']),
                    'text' => $text,
                    'on_copy' => 'preserve',
                ];
            }
        }
        if ($currentCategory) {
            $categories[] = $currentCategory;
        }

        return $categories;
    }

    protected function parseWorkHours(string $workHoursHeader, string $fullContent): array
    {
        $projects = [];
        // Extract the work hours section content
        $section = $this->extractSection($fullContent, 'Work Hours');
        if (!$section) {
            return $projects;
        }

        // Parse header for total budget and deficit
        $totalBudgeted = 6;
        $totalDeficit = 0;
        if (preg_match('/\((\d+)\s+hours?\)/', $workHoursHeader, $m)) {
            $totalBudgeted = (int) $m[1];
        }

        $lines = explode("\n", $section);
        foreach ($lines as $line) {
            if (preg_match('/^- (.+?)\s+(?:\((\d+)\s+hours?\)\s+)?(-?[\d.]+)\s+hours?$/', $line, $match)) {
                $name = trim($match[1]);
                $budgeted = !empty($match[2]) ? (float) $match[2] : 0;
                $deficit = (float) $match[3];

                $projects[] = [
                    'id' => 'wh-' . (count($projects) + 1),
                    'name' => $name,
                    'budgeted_hours' => $budgeted,
                    'logged_hours' => 0,
                    'deficit' => $deficit,
                ];
            }
        }

        return $projects;
    }

    protected function parseLifeManagement(string $content): array
    {
        $categories = [];
        $lines = explode("\n", $content);
        $currentCategory = null;
        $currentItem = null;

        foreach ($lines as $line) {
            // Category: "- 1. Life Admin (1 hour everyday)" or "- 2. Work Admin..."
            if (preg_match('/^- \d+\.\s+(.+?)(?:\s+\(([^)]+)\))?\s*(?:-\s*(.+))?\s*$/', $line, $match)) {
                if ($currentCategory) {
                    if ($currentItem) {
                        $currentCategory['items'][] = $currentItem;
                        $currentItem = null;
                    }
                    $categories[] = $currentCategory;
                }
                $currentCategory = [
                    'id' => 'lm-' . (count($categories) + 1),
                    'name' => trim($match[1]),
                    'time_budget' => isset($match[2]) ? trim($match[2]) : null,
                    'items' => [],
                ];
            }
            // Item with tally: "    1. Work Out (.25 hours) X 0" or "    - Manage Stock..."
            elseif ($currentCategory && preg_match('/^\s{4}(?:\d+\.|-)\s+(.+?)\s+(?:\(([^)]*)\)\s+)?X\s+([\d.]+)\s*$/', $line, $match)) {
                if ($currentItem) {
                    $currentCategory['items'][] = $currentItem;
                }
                $currentItem = [
                    'id' => 'lm-' . count($categories) . '-' . count($currentCategory['items']),
                    'text' => trim($match[1]),
                    'time_budget' => !empty($match[2]) ? trim($match[2]) : null,
                    'tally' => (float) $match[3],
                    'on_copy' => $this->inferOnCopy(trim($match[1]), (float) $match[3]),
                    'sub_items' => [],
                ];
            }
            // Sub-item
            elseif ($currentItem && preg_match('/^\s{8,}(?:\d+\.|-)\s+(.+)$/', $line, $match)) {
                $currentItem['sub_items'][] = [
                    'id' => 'lm-sub-' . count($currentItem['sub_items']),
                    'text' => trim($match[1]),
                    'on_copy' => 'preserve',
                ];
            }
        }

        if ($currentItem && $currentCategory) {
            $currentCategory['items'][] = $currentItem;
        }
        if ($currentCategory) {
            $categories[] = $currentCategory;
        }

        return $categories;
    }

    protected function parsePriorityGroups(string $content): array
    {
        $groups = [];
        $currentGroup = null;
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            // "#1 Priority (3 this group)" or "#1 Priority"
            if (preg_match('/^\s+- #(\d+)\s+Priority\s*(?:\((\d+)\s+this group\))?\s*$/', $line, $match)) {
                if ($currentGroup) {
                    $groups[] = $currentGroup;
                }
                $currentGroup = [
                    'group_number' => (int) $match[1],
                    'count_this_group' => isset($match[2]) ? (int) $match[2] : 0,
                    'on_copy' => 'reset',
                    'items' => [],
                ];
            }
            // Rewatch group (for serials)
            elseif (preg_match('/^\s+- Rewatch\s*$/', $line)) {
                if ($currentGroup) {
                    $groups[] = $currentGroup;
                }
                $currentGroup = [
                    'group_number' => count($groups) + 1,
                    'label' => 'Rewatch',
                    'count_this_group' => 0,
                    'on_copy' => 'reset',
                    'items' => [],
                ];
            }
            // Item within group: "        1. Greek - 3-24"
            elseif ($currentGroup && preg_match('/^\s+\d+\.\s+(.+)$/', $line, $match)) {
                $text = trim($match[1]);
                $lastDate = null;
                // Try to extract date like "3-24" or "12-30" at end
                if (preg_match('/^(.+?)\s*-\s*(\d{1,2})-(\d{1,2})$/', $text, $dateMatch)) {
                    $text = trim($dateMatch[1]);
                    $lastDate = "2026-" . str_pad($dateMatch[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($dateMatch[3], 2, '0', STR_PAD_LEFT);
                    // Handle dates that might be from previous year
                    if ((int) $dateMatch[2] > 6 && (int) $dateMatch[2] <= 12) {
                        // Could be 2025 for months like 7-23, 10-19, etc.
                        // Only if the month is significantly ahead, assume previous year
                    }
                }

                $currentGroup['items'][] = [
                    'id' => 'pg-' . count($groups) . '-' . count($currentGroup['items']),
                    'text' => $text,
                    'last_date' => $lastDate,
                    'on_copy' => 'preserve',
                ];
            }
        }

        if ($currentGroup) {
            $groups[] = $currentGroup;
        }

        return $groups;
    }

    protected function parsePercentileGroups(string $content): array
    {
        $groups = [];
        $currentGroup = null;
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            // "Percentile 1 - (5 this group)" or "Rewatch"
            if (preg_match('/^\s+- Percentile (\d+)\s*-?\s*\((\d+)\s+this group\)\s*$/', $line, $match)) {
                if ($currentGroup) {
                    $groups[] = $currentGroup;
                }
                $currentGroup = [
                    'group_number' => (int) $match[1],
                    'label' => 'Percentile ' . $match[1],
                    'count_this_group' => (int) $match[2],
                    'on_copy' => 'reset',
                    'items' => [],
                ];
            } elseif (preg_match('/^\s+- Rewatch\s*$/', $line)) {
                if ($currentGroup) {
                    $groups[] = $currentGroup;
                }
                $currentGroup = [
                    'group_number' => count($groups) + 1,
                    'label' => 'Rewatch',
                    'count_this_group' => 0,
                    'on_copy' => 'reset',
                    'items' => [],
                ];
            }
            // Item: "        1. Happy Days - s5 - 3-27"
            elseif ($currentGroup && preg_match('/^\s+\d+\.\s+(.+)$/', $line, $match)) {
                $text = trim($match[1]);
                $lastDate = null;
                if (preg_match('/^(.+?)\s*-\s*(\d{1,2})-(\d{1,2})$/', $text, $dateMatch)) {
                    $text = trim($dateMatch[1]);
                    $lastDate = "2026-" . str_pad($dateMatch[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($dateMatch[3], 2, '0', STR_PAD_LEFT);
                }

                $currentGroup['items'][] = [
                    'id' => 'pg-' . count($groups) . '-' . count($currentGroup['items']),
                    'text' => $text,
                    'last_date' => $lastDate,
                    'on_copy' => 'preserve',
                ];
            }
        }

        if ($currentGroup) {
            $groups[] = $currentGroup;
        }

        return $groups;
    }

    protected function parseGameItems(string $content): array
    {
        $items = [];
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (preg_match('/^\s+- (.+?)\s+x\s+([\d.]+)(?:\s*-\s*(\d+-\d+))?\s*$/i', $line, $match)) {
                $items[] = [
                    'id' => 'game-' . (count($items) + 1),
                    'text' => trim($match[1]),
                    'tally' => (float) $match[2],
                    'on_copy' => 'reset',
                ];
            }
        }
        return $items;
    }

    protected function inferOnCopy(string $text, float $tally): string
    {
        // Items like "Update Finances" with high tallies are likely increment
        if ($tally > 10) {
            return 'increment';
        }
        return 'reset';
    }

    // ─── Helper ─────────────────────────────────────────────────────────

    protected function findOrCreatePage(User $user, array $attrs): UserPage
    {
        $existing = $this->pageRepository->findAll([
            ['user_id', '=', $user->id],
            ['slug', '=', $attrs['slug']],
        ])->first();

        if ($existing) {
            return $existing;
        }

        return $this->pageRepository->create(array_merge(['user_id' => $user->id], $attrs));
    }

    protected function createComponent(UserPage $page, string $type, int $order, array $config): void
    {
        $this->componentRepository->create([
            'user_page_id' => $page->id,
            'component_type' => $type,
            'display_order' => $order,
            'config_json' => $config,
        ]);
    }
}
