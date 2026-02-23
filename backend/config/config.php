<?php

// GitHub OAuth App Credentials

define('GITHUB_CLIENT_ID', getenv('GITHUB_CLIENT_ID'));
define('GITHUB_CLIENT_SECRET', getenv('GITHUB_CLIENT_SECRET'));

// Github Personal Acess Token
define('GITHUB_PERSONAL_ACCESS_TOKEN', getenv('GITHUB_PERSONAL_ACCESS_TOKEN'));

// Github Identity
define('GITHUB_USERNAME', getenv('GITHUB_USERNAME'));

// Private repo where projects.json lives
define('GITHUB_REPO', getenv('GITHUB_REPO'));
define('GITHUB_FILE_PATH', getenv('GITHUB_FILE_PATH'));

// Session secret
define('SESSION_SECRET', getenv('SESSION_SECRET'));

// OAuth callback URL
define('OAUTH_CALLBACK_URL', getenv('OAUTH_CALLBACK_URL'));
