# Debug data creator

![CI pipeline](https://github.com/szemul/debug-data-creator/actions/workflows/php.yml/badge.svg)
[![codecov](https://codecov.io/gh/szemul/debug-data-creator/branch/main/graph/badge.svg?token=18YTWE8BFM)](https://codecov.io/gh/szemul/debug-data-creator)

Error handler to create debug/trace files to aid in debugging.

## Data categories

* Error message - A description of the error or exception
* Exception - If the debug is for an exception or other throwable, the dump of the throwable itself
* Back trace - The backtrace for the error. In case of an exception or a throwable the exception category contains this
* Server - The contents of the $_SERVER superglobal
* Get - The contents of the $_GET superglobal
* Post - The contents of the $_POST superglobal
* Cookie - The contents of the $_COOKIE superglobal
* Env - The contents of the $_ENV superglobal

All of the above categories are dumped using the `var_dump()` PHP function except for the error message.

### Config

Each section above can be enabled or disabled. To avoid potential security issues the following categories are disabled 
by default:

* Server - This may contain environment variables and sensitive configuration data. 
* Post - This may contain credentials in case of a login for example.
* Cookie - This may contain session cookies for example.
* Env - This may contain environment variables and sensitive configuration data.

When enabling any of these, it's strongly recommended to use sanitizers to sanitize the categories 

## Input sanitization

A sanitizer will process the individual categories and remove or mask any sensitive values.

### Object sanitization

Any sanitizer may process any object it knows, but an easier way to handle object sanitization is to implement the 
`__debugInfo` magic method in them.
