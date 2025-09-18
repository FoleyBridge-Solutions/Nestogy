<?php
// This is a temporary file to help reorganize the sidebar configuration
// We'll use this to extract the Physical Mail section and move it to a better location

$physicalMailSection = [
    'type' => 'section',
    'title' => 'PHYSICAL MAIL',
    'expandable' => true,
    'default_expanded' => false,
    'items' => [
        [
            'name' => 'Mail History',
            'route' => 'mail.index',
            'icon' => 'envelope',
            'key' => 'mail-history',
            'description' => 'View all sent physical mail'
        ],
        [
            'name' => 'Send Mail',
            'route' => 'mail.send',
            'icon' => 'paper-airplane',
            'key' => 'send-mail',
            'description' => 'Send letters and documents'
        ],
        [
            'name' => 'Mail Templates',
            'route' => 'mail.templates',
            'icon' => 'document-duplicate',
            'key' => 'mail-templates',
            'description' => 'Manage mail templates'
        ],
        [
            'name' => 'Mail Contacts',
            'route' => 'mail.contacts',
            'icon' => 'user-group',
            'key' => 'mail-contacts',
            'description' => 'Manage mailing addresses'
        ],
        [
            'name' => 'Tracking',
            'route' => 'mail.tracking',
            'icon' => 'map-pin',
            'key' => 'mail-tracking',
            'description' => 'Track mail delivery status'
        ]
    ]
];