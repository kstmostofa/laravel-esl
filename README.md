# Laravel ESL

A Laravel package for connecting to and interacting with a FreeSWITCH ESL (Event Socket Library) server.

## Features

-	Easy configuration for FreeSWITCH ESL connection details.
-	Fluent API for sending commands to FreeSWITCH.
-	Handles connection and authentication automatically.
-	Provides a `Esl` facade for convenient access.
-	Includes a custom exception for robust error handling.
-	Automatic resource management (disconnection).

## Installation

You can install the package via Composer:

```bash
composer require kstmostofa/laravel-esl
```

The package will automatically register its service provider and facade.

## Configuration

To publish the configuration file, run the following command:

```bash
php artisan vendor:publish --provider="Kstmostofa\LaravelEsl\LaravelEslServiceProvider"
```

This will create a `config/esl.php` file in your application's config directory. You can then configure your FreeSWITCH ESL connection details in your `.env` file:

```
ESL_HOST=127.0.0.1
ESL_PORT=8021
ESL_PASSWORD=ClueCon
```

## Usage

You can interact with the FreeSWITCH ESL server in two primary ways:

### Using the `Esl` Facade

The `Esl` facade provides a quick and easy way to send commands.

```php
use Kstmostofa\LaravelEsl\Facades\Esl;
use Kstmostofa\LaravelEsl\EslConnectionException;

try {
    // Execute the 'status' command
    $response = Esl::execute('status');
    echo "FreeSWITCH Status:\n";
    print_r($response);

    // Originate a call
    $uuid = Esl::execute('originate user/1000 &echo');
    echo "Call UUID: " . $uuid;

} catch (EslConnectionException $e) {
    // Handle connection or authentication errors
    Log::error('ESL Connection failed: ' . $e->getMessage());
}
```

### Using a Custom Connection

For situations where you need to connect to a different FreeSWITCH server on-the-fly, you can use the `connection()` method before executing a command. This will not affect the default connection configuration.

```php
use Kstmostofa\LaravelEsl\Facades\Esl;
use Kstmostofa\LaravelEsl\EslConnectionException;

try {
    // Use the default connection from your config
    $defaultStatus = Esl::execute('status');

    // Connect to a different server for a specific command
    $customStatus = Esl::connection('10.0.1.5', 8021, 'another_password')
                       ->execute('status');

} catch (EslConnectionException $e) {
    Log::error('ESL command failed: ' . $e->getMessage());
}
```

### Available Command Methods

The package provides the following helper methods for common API commands to make your code more readable.

```php
// Get the server status
$status = Esl::status();

// Get a list of all active channels
$channels = Esl::showChannels();

// Get a list of all active calls
$calls = Esl::showCalls();

// Get the global sofia status
$sofiaStatus = Esl::sofiaStatus();

// Get the status of a specific sofia profile
$profileStatus = Esl::sofiaStatusProfile('internal');
```

For any command that does not have a dedicated helper method, you can use the `execute()` method directly:

```php
// For example, to get the server uptime
$uptime = Esl::execute('uptime');
```

### Using Dependency Injection

You can also type-hint the `EslConnection` class in your controllers or other classes to have it automatically injected by Laravel's service container.

```php
use Kstmostofa\LaravelEsl\EslConnection;
use Kstmostofa\LaravelEsl\EslConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallController extends Controller
{
    private $esl;

    public function __construct(EslConnection $esl)
    {
        $this->esl = $esl;
    }

    public function makeCall(Request $request)
    {
        try {
            $destination = $request->input('destination', 'user/1001');
            $uuid = $this->esl->execute("originate sofia/gateway/my_gateway/{$destination} &echo");
            return response()->json(['message' => 'Call initiated.', 'uuid' => $uuid]);
        } catch (EslConnectionException $e) {
            Log::error('ESL command failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to initiate call.'], 500);
        }
    }
}
```

### Response Parsing

Currently, the `execute` method returns the raw response body from the FreeSWITCH API command. Future versions will include a more sophisticated parser to return structured data.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
