php-apple-signin
=======
PHP library to manage Sign In with Apple identifier tokens, and validate them server side passed through by the iOS client.

FORK to make it compatible with PHP 5.6

Installation
------------

Use composer to manage your dependencies and download php-apple-signin:

```bash
composer require jsoprano/php-apple-signin-php56
```

Example
-------
```php
<?php
use AppleSignIn\ASDecoder;

$clientUser = "example_client_user";
$identityToken = "example_encoded_jwt";

$appleSignInPayload = ASDecoder::getAppleSignInPayload($identityToken);

/**
 * Obtain the Sign In with Apple email and user creds.
 */
$email = $appleSignInPayload->getEmail();
$user = $appleSignInPayload->getUser();

/**
 * Determine whether the client-provided user is valid.
 */
$isValid = $appleSignInPayload->verifyUser($clientUser);

?>
```
