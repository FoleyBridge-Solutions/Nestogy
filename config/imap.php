<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Account
    |--------------------------------------------------------------------------
    |
    | The default account identifier. It will be used as default for any missing account parameters.
    | If however the default account is missing a parameter the package default will be used.
    | Set to 'false' [boolean] to disable and enforce account specific configurations.
    |
    */

    'default' => env('IMAP_DEFAULT_ACCOUNT', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Available Accounts
    |--------------------------------------------------------------------------
    |
    | Please list all IMAP accounts which you are planning to use within the
    | array below.
    |
    */

    'accounts' => [

        'default' => [
            'host'          => env('IMAP_HOST', 'localhost'),
            'port'          => env('IMAP_PORT', 993),
            'protocol'      => env('IMAP_PROTOCOL', 'imap'),
            'encryption'    => env('IMAP_ENCRYPTION', 'ssl'),
            'validate_cert' => env('IMAP_VALIDATE_CERT', true),
            'username'      => env('IMAP_USERNAME', 'root@example.com'),
            'password'      => env('IMAP_PASSWORD', ''),
            'authentication' => env('IMAP_AUTHENTICATION', null),
        ],

        'gmail' => [
            'host'          => 'imap.gmail.com',
            'port'          => 993,
            'encryption'    => 'ssl',
            'validate_cert' => true,
            'username'      => env('GMAIL_USERNAME'),
            'password'      => env('GMAIL_PASSWORD'),
            'authentication' => 'oauth',
        ],

        'outlook' => [
            'host'          => 'outlook.office365.com',
            'port'          => 993,
            'encryption'    => 'ssl',
            'validate_cert' => true,
            'username'      => env('OUTLOOK_USERNAME'),
            'password'      => env('OUTLOOK_PASSWORD'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Available Options
    |--------------------------------------------------------------------------
    |
    | Available php imap config parameters are listed below
    |   -Delimiter (optional):
    |       This option is only used when calling $oClient->
    |       You can use any string you want, but it must be only one character.
    |       Defaults to "."
    |
    |   -Fetch option:
    |       IMAP::FT_UID  - Message marked as read by fetching the message body
    |       IMAP::FT_PEEK - Fetch the message without setting the "seen" flag
    |
    |   -Fetch order:
    |       IMAP::SO_ASC  - Sort in ascending order
    |       IMAP::SO_DESC - Sort in descending order
    |
    |   -Open IMAP options:
    |       IMAP::OP_READONLY   - Open mailbox read-only
    |       IMAP::OP_ANONYMOUS  - Don't use or update a .newsrc for news
    |       IMAP::OP_HALFOPEN   - For IMAP and NNTP names, open a connection but don't open a mailbox.
    |       IMAP::OP_EXPUNGE    - Silently expunge recycle stream
    |       IMAP::OP_DEBUG      - Debug protocol negotiations
    |       IMAP::OP_SHORTCACHE - Short (elt-only) caching
    |       IMAP::OP_SILENT     - Don't pass up events (internal use)
    |       IMAP::OP_PROTOTYPE  - Return driver prototype
    |       IMAP::OP_SECURE     - Don't do non-secure authentication
    |
    */

    'options' => [
        'delimiter' => env('IMAP_DEFAULT_DELIMITER', '.'),
        // Use string values supported by webklex/php-imap v5.x config
        'fetch' => 'FT_PEEK',           // or 'FT_UID'
        'fetch_order' => 'desc',        // 'asc' | 'desc'
        'open' => [
            // 'OP_READONLY',
        ],
        'decoder' => [
            'message' => [
                'subject' => 'utf-8',
                'from'    => 'utf-8',
                'to'      => 'utf-8',
                'cc'      => 'utf-8',
                'bcc'     => 'utf-8',
            ],
            'attachment' => [
                'name' => 'utf-8',
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Available events
    |--------------------------------------------------------------------------
    |
    */

    'events' => [
        'message' => [
            'new' => \Webklex\PHPIMAP\Events\MessageNewEvent::class,
            'moved' => \Webklex\PHPIMAP\Events\MessageMovedEvent::class,
            'copied' => \Webklex\PHPIMAP\Events\MessageCopiedEvent::class,
            'deleted' => \Webklex\PHPIMAP\Events\MessageDeletedEvent::class,
            'restored' => \Webklex\PHPIMAP\Events\MessageRestoredEvent::class,
        ],
        'folder' => [
            'new' => \Webklex\PHPIMAP\Events\FolderNewEvent::class,
            'moved' => \Webklex\PHPIMAP\Events\FolderMovedEvent::class,
            'deleted' => \Webklex\PHPIMAP\Events\FolderDeletedEvent::class,
        ],
        'flag' => [
            'new' => \Webklex\PHPIMAP\Events\FlagNewEvent::class,
            'deleted' => \Webklex\PHPIMAP\Events\FlagDeletedEvent::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available masking options
    |--------------------------------------------------------------------------
    |
    | By using your own custom masks you can implement your own methods for
    | a better and faster access and less code to write.
    |
    | Checkout the two examples custom_attachment_mask and custom_message_mask
    | for further information.
    |
    */

    'masks' => [
        'message' => \Webklex\PHPIMAP\Support\Masks\MessageMask::class,
        'attachment' => \Webklex\PHPIMAP\Support\Masks\AttachmentMask::class
    ]

];