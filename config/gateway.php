<?php

return [
    // Require an internal shared secret header to protect direct API bypass.
    'enforce_source' => (bool) env('GATEWAY_ENFORCE_SOURCE', false),

    // Internal secret contract between KrakenD and Laravel.
    'shared_secret' => (string) env('GATEWAY_SHARED_SECRET', 'krakend-internal'),
    'shared_secret_header' => (string) env('GATEWAY_SHARED_SECRET_HEADER', 'X-Gateway-Secret'),

    // Trust marker headers only after source verification succeeds.
    'trust_jwt_assertion' => (bool) env('GATEWAY_TRUST_JWT_ASSERTION', true),
    'jwt_assertion_header' => (string) env('GATEWAY_JWT_ASSERTION_HEADER', 'X-Gateway-Auth'),
    'jwt_assertion_value' => (string) env('GATEWAY_JWT_ASSERTION_VALUE', 'jwt'),

    // JWT claim headers propagated by KrakenD for domain-level authorization.
    'jwt_roles_header' => (string) env('GATEWAY_JWT_ROLES_HEADER', 'X-Auth-Roles'),
    'jwt_subject_header' => (string) env('GATEWAY_JWT_SUBJECT_HEADER', 'X-Auth-Subject'),
    'jwt_moderator_role' => (string) env('GATEWAY_JWT_MODERATOR_ROLE', 'moderator'),
];
