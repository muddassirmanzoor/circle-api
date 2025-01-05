<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

return [
    "globals" => [
        "notification_gateway" => env('NOTIFICATION_GATEWAY', 'ssl://gateway.push.apple.com:2195'),
        "hyperpay_access_token" => 'OGFjN2E0Yzc3MmE4Zjg3YzAxNzJiMWUyNDhlYzE5YzN8WFFDNTVoTnJzeg==', // test credentials
//        "hyperpay_access_token" => 'OGFjZGE0Y2Q3NTQwYWI4ZDAxNzU0YjAzODJlMDcyYTN8d3B5QVdyc3g4eA==',  // live credentials
        "hyperpay_entity_id" => '8ac7a4c772a8f87c0172b1e3c09f19c7', // test credentials
        "hyperpay_pound_entity_id" => '8ac7a4c8786e850501787790f7b011cf', // test credentials
//        "hyperpay_entity_id" => '8acda4cd7540ab8d01754b03ecbf72aa',  // live credentials
        "hyperpay_base_address" => 'https://test.oppwa.com', // test link
//        "hyperpay_base_address" => 'https://oppwa.com/', // live link
        "hyperpay_full_address" => 'https://test.oppwa.com/v1/checkouts', // test credentials
//        "hyperpay_full_address" => 'https://oppwa.com/v1/checkouts',      // live credetials
        "hyperpay_chargeback_address" => 'https://test.oppwa.com/v1/payments', // test credentials
//        "hyperpay_chargeback_address" => 'https://oppwa.com/v1/payments',      // live credetials
        "SAR" => 4.72,
        "Pound" => 0.21186441,
        "code_expire_time" => 120,
    ],
    "url" => [
        "staging_url" => "https://api.circlonline.com/staging/",
        "development_url" => "https://api.circlonline.com/dev/",
        "production_url" => "https://api.circlonline.com/prod/",
    ],
    'chat_channel' => [
        'one_to_one' => 'presence-message-chat-',
        'personal_presence' => 'presence-circl-channel-',
        'chat_event' => 'chat-event-',
    ],
    'ipregistry' => [
//        'ipregistry_key' => 'tgh247t2zhyrjy',
        'ipregistry_key' => 'nwdh2g829l5hs9xq',
//        'ipregistry_key' => 'tgh247t2zhyrjy',
//        'ipregistry_key' => '611k8s1a3o0g5h',
    ],
    "notifications" => [
        "notification_gateway" => env('NOTIFICATION_GATEWAY', 'ssl://gateway.push.apple.com:2195'),
//        "bundle_identifier" => "com.circlonline.app",
        "bundle_identifier" => "com.circlonline.mobileapp",
        "key_id" => "W2JN797RY6",
        "team_id" => "662LW2NFS8",
        "p8_key" => "-----BEGIN PRIVATE KEY-----
MIGTAgEAMBMGByqGSM49AgEGCCqGSM49AwEHBHkwdwIBAQQggmEkzxbEJTBWPADM
PBpi0zkthQ2I26Ud1n6pWNwIPCegCgYIKoZIzj0DAQehRANCAARSSz5IxRL/L18x
TL+vzxn1K6JH8LSlvOQVAlfQa+2NDWBYCp2SuU8ZYijOucVL36yNG4wfDnmZvnGN
x41ap9Xb
-----END PRIVATE KEY-----",
    ],
    'fixer' => [
        'fixer_key' => '74347d47de7750e9bb484c4065a5483b',
    ],
    'subscriptions' => [
        'monthly' => '1',
        'quarterly' => '4',
        'annual' => '12',
        'appointment' => '1',
        'class' => '1'
    ],
    'subscription_type' => [
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'annual' => 'Annual',
    ],
    "withdraw_schedule" => [
        0 => 'One Week',
        1 => 'Two Week',
        2 => 'One Month',
    ],
];
