<?php
require_once __DIR__ . '/../config/google.php';

/**
 * Build the Google OAuth2 authorization URL.
 */
function googleAuthUrl(string $state): string
{
    $params = http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => GOOGLE_SCOPES,
        'access_type'   => 'online',
        'state'         => $state,
        'prompt'        => 'select_account',
    ]);
    return GOOGLE_AUTH_URL . '?' . $params;
}

/**
 * Exchange an authorization code for an access token.
 * Returns the decoded JSON response or throws on failure.
 */
function exchangeCodeForToken(string $code): array
{
    $payload = http_build_query([
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]);

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $payload,
            'ignore_errors' => true,
        ],
    ]);

    $response = file_get_contents(GOOGLE_TOKEN_URL, false, $ctx);
    $data = json_decode($response, true);

    if (empty($data['access_token'])) {
        throw new RuntimeException('Failed to obtain access token from Google.');
    }

    return $data;
}

/**
 * Fetch the authenticated user's profile from Google.
 */
function fetchGoogleUserInfo(string $accessToken): array
{
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => "Authorization: Bearer $accessToken\r\n",
            'ignore_errors' => true,
        ],
    ]);

    $response = file_get_contents(GOOGLE_USER_URL, false, $ctx);
    $data = json_decode($response, true);

    if (empty($data['sub'])) {
        throw new RuntimeException('Failed to retrieve user info from Google.');
    }

    return $data;
}