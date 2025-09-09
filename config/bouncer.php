<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bouncer Tables
    |--------------------------------------------------------------------------
    |
    | These are the tables used by Bouncer to store abilities and roles.
    | Since we have existing permissions/roles tables, we'll use prefixed names.
    |
    */

    'tables' => [
        'abilities' => 'bouncer_abilities',
        'assigned_roles' => 'bouncer_assigned_roles',
        'permissions' => 'bouncer_permissions',
        'roles' => 'bouncer_roles',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bouncer Cache
    |--------------------------------------------------------------------------
    |
    | Here you may configure the cache settings for Bouncer.
    |
    */

    'cache' => [
        'ttl' => 24 * 60, // 24 hours
        'store' => 'default',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This is the User model used by Bouncer.
    |
    */

    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Scope Configuration
    |--------------------------------------------------------------------------
    |
    | This is the scope configuration for multi-tenancy.
    |
    */

    'scope' => [
        'multi_tenant' => true,
        'scope_column' => 'company_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | Here you may specify which database connection should be used
    | by Bouncer's migrations.
    |
    */

    'connection' => null,

];