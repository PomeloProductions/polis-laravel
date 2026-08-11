<?php

/**
 * Routes that are available to the public
 */
Route::group(['middleware' => 'jwt.auth.unprotected'], function () {

    Route::get('status', 'StatusController')
        ->name('status');

    /**
     * Categories context
     */
    Route::resource('categories', 'CategoryController', [
        'only' => [
            'index', 'show',
        ],
    ]);

    /**
     * Features Context
     */
    Route::resource('features', 'FeatureController', [
        'only' => [
            'index', 'show',
        ],
    ]);

    /**
     * Categories context
     */
    Route::resource('messages', 'MessageController', [
        'only' => [
            'store',
        ],
    ]);
});

/**
 * Forgot password routes
 */
Route::post('forgot-password', 'ForgotPasswordController@forgotPassword')
    ->name('forgot-password');

Route::post('reset-password', 'ForgotPasswordController@resetPassword')
    ->name('reset-password');

/**
 * Authentication routes
 */
Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {

    Route::post('refresh', 'AuthenticationController@refresh')
        ->name('refresh');

    Route::post('login', 'AuthenticationController@login')
        ->name('login');

    Route::post('logout', 'AuthenticationController@logout')
        ->name('logout');

    Route::post('sign-up', 'AuthenticationController@signUp')
        ->name('sign-up');
});

/**
 * Routes that a user needs to be authenticated for in order to access
 */
Route::group(['middleware' => 'jwt.auth.protected'], function () {

    /**
     * Article Context
     */
    Route::resource('articles', 'ArticleController', [
        'except' => [
            'create', 'edit', 'destroy',
        ],
    ]);
    Route::group(['prefix' => 'articles/{article}', 'as' => 'article.'], function () {
        Route::resource('iterations', 'Article\IterationController', [
            'parameters' => [
                'iterations' => 'article_iteration',
            ],
            'only' => [
                'index',
            ],
        ]);

        Route::resource('versions', 'Article\ArticleVersionController', [
            'only' => [
                'index', 'store',
            ],
        ]);

        // Article summary routes (singular resource pattern)
        Route::get('article-summary', 'Wiki\ArticleSummaryController@show')->name('article-summary.show');
        Route::post('article-summary', 'Wiki\ArticleSummaryController@store')->name('article-summary.store');
        Route::put('article-summary', 'Wiki\ArticleSummaryController@update')->name('article-summary.update');
        Route::delete('article-summary', 'Wiki\ArticleSummaryController@destroy')->name('article-summary.destroy');
    });

    Route::resource('ballots', 'BallotController', [
        'only' => [
            'show',
        ],
    ]);
    Route::group(['prefix' => 'ballots/{ballot}', 'as' => 'ballot.'], function () {
        Route::resource('ballot-completions', 'Ballot\BallotCompletionController', [
            'only' => [
                'store',
            ],
        ]);
    });
    /**
     * Categories context
     */
    Route::resource('categories', 'CategoryController', [
        'only' => [
            'store', 'update', 'destroy',
        ],
    ]);
    /**
     * Collection context
     */
    Route::resource('collections', 'CollectionController', [
        'only' => [
            'show', 'update', 'destroy',
        ],
    ]);
    Route::group(['prefix' => 'collections/{collection}', 'as' => 'collection.'], function () {

        Route::resource('items', 'Collection\CollectionItemController', [
            'only' => [
                'index', 'store',
            ],
        ]);
    });

    /**
     * Collection Item context
     */
    Route::resource('collection-items', 'CollectionItemController', [
        'only' => [
            'show', 'destroy',
        ],
    ]);

    /**
     * Resource Context
     */
    Route::resource('resources', 'ResourceController', [
        'only' => [
            'index',
        ],
    ]);

    /**
     * User Context
     */
    Route::get('users/me', 'UserController@me')
        ->name('view-self');

    Route::resource('users', 'UserController', [
        'except' => [
            'create', 'edit',
        ],
    ]);
    Route::group(['prefix' => 'users/{user}', 'as' => 'user.'], function () {
        require 'entity-routes.php';

        Route::resource('article-notes', 'User\ArticleNoteController', [
            'except' => [
                'create', 'edit',
            ],
        ]);

        Route::post('random-article', 'User\ArticleNoteController@randomArticle')
            ->name('random-article');

        Route::resource('ballot-completions', 'User\BallotCompletionController', [
            'only' => [
                'index',
            ],
        ]);

        Route::resource('contacts', 'User\ContactController', [
            'only' => [
                'index', 'store', 'update',
            ],
        ]);

        Route::resource('pages', 'User\UserPageController', [
            'only' => [
                'index', 'store', 'update', 'destroy',
            ],
        ]);

        Route::group(['prefix' => 'todos', 'as' => 'todo.'], function () {
            Route::get('today', 'User\TodoController@today')->name('today');
            Route::get('resolve', 'User\TodoController@resolve')->name('resolve');
            Route::get('navigate', 'User\TodoController@navigate')->name('navigate');
            Route::get('hierarchy', 'User\TodoController@hierarchy')->name('hierarchy');
            Route::post('generate', 'User\TodoController@generate')->name('generate');
            Route::get('settings', 'User\TodoController@settings')->name('settings');
            Route::put('settings', 'User\TodoController@updateSettings')->name('settings.update');
            Route::get('timer', 'User\TodoController@timerShow')->name('timer.show');
            Route::post('timer', 'User\TodoController@timerStart')->name('timer.start');
            Route::patch('timer', 'User\TodoController@timerUpdate')->name('timer.update');
            Route::delete('timer', 'User\TodoController@timerStop')->name('timer.stop');
            Route::get('time-entries', 'User\TodoController@timeEntryIndex')->name('time-entries.index');
            Route::post('time-entries', 'User\TodoController@timeEntryStore')->name('time-entries.store');
            Route::put('time-entries/{timeEntry}', 'User\TodoController@timeEntryUpdate')->name('time-entries.update');
            Route::delete('time-entries/{timeEntry}', 'User\TodoController@timeEntryDestroy')->name('time-entries.destroy');
            Route::get('balances', 'User\TodoController@balanceIndex')->name('balances.index');
            Route::get('calendars', 'User\TodoController@calendarIndex')->name('calendars.index');
            Route::post('calendars', 'User\TodoController@calendarStore')->name('calendars.store');
            Route::put('calendars/{calendar}', 'User\TodoController@calendarUpdate')->name('calendars.update');
            Route::delete('calendars/{calendar}', 'User\TodoController@calendarDestroy')->name('calendars.destroy');
            Route::get('vacation', 'User\TodoController@vacationShow')->name('vacation.show');
            Route::put('vacation', 'User\TodoController@vacationUpdate')->name('vacation.update');
            Route::patch('nodes/{clientId}', 'User\TodoController@patchNode')->name('nodes.patch');
            Route::get('templates', 'User\TodoController@templateIndex')->name('templates.index');
            Route::post('templates', 'User\TodoController@templateStore')->name('templates.store');
            Route::put('templates/{template}', 'User\TodoController@templateUpdate')->name('templates.update');
            Route::delete('templates/{template}', 'User\TodoController@templateDestroy')->name('templates.destroy');
        });
        Route::group(['prefix' => 'pages/{page}', 'as' => 'page.'], function () {
            Route::resource('components', 'User\UserPageComponentController', [
                'parameters' => [
                    'components' => 'component',
                ],
                'only' => [
                    'index', 'store', 'update', 'destroy',
                ],
            ]);
        });

        Route::resource('threads', 'User\ThreadController', [
            'only' => [
                'index', 'store',
            ],
        ]);

        Route::group(['prefix' => 'threads/{thread}', 'as' => 'thread.'], function () {
            Route::resource('messages', 'User\Thread\MessageController', [
                'only' => [
                    'index', 'store', 'update',
                ],
            ]);
        });
    });

    /**
     * Membership Plan Context
     */
    Route::resource('membership-plans', 'MembershipPlanController', [
        'except' => [
            'create', 'edit',
        ],
    ]);
    Route::group(['prefix' => 'membership-plans/{membership_plan}', 'as' => 'membership-plan.'], function () {
        Route::resource('rates', 'MembershipPlan\MembershipPlanRateController', [
            'only' => [
                'index',
            ],
        ]);
    });

    /**
     * Organization Context
     */
    Route::resource('organizations', 'OrganizationController', [
        'except' => [
            'create', 'edit',
        ],
    ]);
    Route::group(['prefix' => 'organizations/{organization}', 'as' => 'organization.'], function () {
        require 'entity-routes.php';

        Route::resource('organization-managers', 'Organization\OrganizationManagerController', [
            'except' => [
                'create', 'edit', 'show',
            ],
        ]);

        Route::resource('articles', 'Organization\ArticleController', [
            'only' => [
                'index',
            ],
        ]);
    });

    /**
     * Roles Context
     */
    Route::resource('roles', 'RoleController', [
        'only' => [
            'index',
        ],
    ]);

    /**
     * Invitation Tokens Context (Super Admin only)
     */
    Route::resource('invitation-tokens', 'InvitationTokenController', [
        'except' => [
            'create', 'edit',
        ],
    ]);

    /**
     * Statistics Context
     */
    Route::resource('statistics', 'StatisticController', [
        'only' => [
            'index', 'store', 'show', 'update', 'destroy',
        ],
    ]);
});
