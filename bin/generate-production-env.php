<?php

/**
 * Generates a production .env file for a Bedrock deploy (fresh random salts each
 * run — never reuse this to regenerate an existing prod .env, that would log
 * out every session and invalidate auth cookies/nonces).
 *
 * Its output is meant to be stored verbatim in the DEPLOY_ENV_FILE GitHub
 * secret (.github/workflows/deploy.yml writes it to the server on every
 * deploy) — run this once to seed that secret, and only ever again to
 * deliberately rotate a credential (new DB password, new SENTRY_DSN...),
 * never on a routine basis:
 *
 *   docker compose -f docker/docker-compose.yml --project-directory . \
 *     exec -T php php bin/generate-production-env.php DB_NAME DB_USER DB_PASSWORD DOMAIN SENTRY_DSN \
 *     | gh secret set DEPLOY_ENV_FILE
 *
 * Usage: php bin/generate-production-env.php DB_NAME DB_USER DB_PASSWORD DOMAIN [SENTRY_DSN]
 */

[, $dbName, $dbUser, $dbPassword, $domain] = $argv;
$sentryDsn = $argv[5] ?? '';

$salts = [];
for ($i = 0; $i < 8; $i++) {
    $salts[] = bin2hex(random_bytes(32));
}
[$authKey, $secureAuthKey, $loggedInKey, $nonceKey, $authSalt, $secureAuthSalt, $loggedInSalt, $nonceSalt] = $salts;

echo <<<ENV
DB_NAME='{$dbName}'
DB_USER='{$dbUser}'
DB_PASSWORD='{$dbPassword}'

WP_ENV='production'
WP_HOME='{$domain}'
WP_SITEURL="\${WP_HOME}/wp"

AUTH_KEY='{$authKey}'
SECURE_AUTH_KEY='{$secureAuthKey}'
LOGGED_IN_KEY='{$loggedInKey}'
NONCE_KEY='{$nonceKey}'
AUTH_SALT='{$authSalt}'
SECURE_AUTH_SALT='{$secureAuthSalt}'
LOGGED_IN_SALT='{$loggedInSalt}'
NONCE_SALT='{$nonceSalt}'

SENTRY_DSN='{$sentryDsn}'

ENV;
