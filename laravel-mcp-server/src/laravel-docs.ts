export const LARAVEL_TOPICS = {
  "mcp": {
    "title": "Laravel MCP",
    "description": "Model Context Protocol integration for Laravel",
    "sections": {
      "introduction": `## Introduction

[Laravel MCP](https://github.com/laravel/mcp) provides a simple and elegant way for AI clients to interact with your Laravel application through the [Model Context Protocol](https://modelcontextprotocol.io/docs/getting-started/intro). It offers an expressive, fluent interface for defining servers, tools, resources, and prompts that enable AI-powered interactions with your application.`,
      
      "installation": `## Installation

To get started, install Laravel MCP into your project using the Composer package manager:

\`\`\`shell
composer require laravel/mcp
\`\`\`

### Publishing Routes

After installing Laravel MCP, execute the \`vendor:publish\` Artisan command to publish the \`routes/ai.php\` file where you will define your MCP servers:

\`\`\`shell
php artisan vendor:publish --tag=ai-routes
\`\`\`

This command creates the \`routes/ai.php\` file in your application's \`routes\` directory, which you will use to register your MCP servers.`,
      
      "creating-servers": `## Creating Servers

You can create an MCP server using the \`make:mcp-server\` Artisan command. Servers act as the central communication point that exposes MCP capabilities like tools, resources, and prompts to AI clients:

\`\`\`shell
php artisan make:mcp-server WeatherServer
\`\`\`

This command will create a new server class in the \`app/Mcp/Servers\` directory.`,
      
      "tools": `## Tools

Tools enable your server to expose functionality that AI clients can call. They allow language models to perform actions, run code, or interact with external systems.

### Creating Tools

To create a tool, run the \`make:mcp-tool\` Artisan command:

\`\`\`shell
php artisan make:mcp-tool CurrentWeatherTool
\`\`\`

After creating a tool, register it in your server's \`$tools\` property.`,
      
      "prompts": `## Prompts

Prompts enable your server to share reusable prompt templates that AI clients can use to interact with language models. They provide a standardized way to structure common queries and interactions.

### Creating Prompts

To create a prompt, run the \`make:mcp-prompt\` Artisan command:

\`\`\`shell
php artisan make:mcp-prompt DescribeWeatherPrompt
\`\`\``,
      
      "resources": `## Resources

Resources enable your server to expose data and content that AI clients can read and use as context when interacting with language models. They provide a way to share static or dynamic information like documentation, configuration, or any data that helps inform AI responses.

### Creating Resources

To create a resource, run the \`make:mcp-resource\` Artisan command:

\`\`\`shell
php artisan make:mcp-resource WeatherGuidelinesResource
\`\`\``,
      
      "authentication": `## Authentication

You can authenticate web MCP servers with middleware just like you would for routes. This will require a user to authenticate before using any capability of the server.

There are two ways to authenticate access to your MCP server:
- Simple, token based authentication via Laravel Sanctum
- OAuth 2.1 via Laravel Passport

### OAuth 2.1

The most robust way to protect your web-based MCP servers is with OAuth through Laravel Passport.

\`\`\`php
use Laravel\\Mcp\\Facades\\Mcp;

Mcp::oauthRoutes();

Mcp::web('/mcp/weather', WeatherExample::class)
    ->middleware('auth:api');
\`\`\`

### Sanctum

If you would like to protect your MCP server using Sanctum, simply add Sanctum's authentication middleware to your server:

\`\`\`php
use Laravel\\Mcp\\Facades\\Mcp;

Mcp::web('/mcp/demo', WeatherExample::class)
    ->middleware('auth:sanctum');
\`\`\``,
      
      "testing": `## Testing Servers

You may test your MCP servers using the built-in MCP Inspector or by writing unit tests.

### MCP Inspector

The MCP Inspector is an interactive tool for testing and debugging your MCP servers:

\`\`\`shell
# Web server...
php artisan mcp:inspector mcp/weather

# Local server named "weather"...
php artisan mcp:inspector weather
\`\`\`

### Unit Tests

You may write unit tests for your MCP servers, tools, resources, and prompts:

\`\`\`php
test('tool', function () {
    $response = WeatherServer::tool(CurrentWeatherTool::class, [
        'location' => 'New York City',
        'units' => 'fahrenheit',
    ]);

    $response
        ->assertOk()
        ->assertSee('The weather is...');
});
\`\`\``,
    }
  },
  "installation": {
    "title": "Installation",
    "description": "Laravel installation guide",
    "content": `# Installation

## Server Requirements

Before installing Laravel, ensure your server meets the following requirements:

- PHP >= 8.2
- Composer
- MySQL 8.0+ / PostgreSQL 13+ / SQLite 3.35+ / SQL Server 2017+

## Installing Laravel

Laravel utilizes Composer to manage its dependencies. Install Composer before using Laravel.

### Via Laravel Installer

\`\`\`shell
composer global require laravel/installer
laravel new example-app
\`\`\`

### Via Composer Create-Project

\`\`\`shell
composer create-project laravel/laravel example-app
\`\`\`

## Initial Configuration

All configuration files are stored in the \`config\` directory. Review the \`config/app.php\` file and configure your application settings.`
  },
  "routing": {
    "title": "Routing",
    "description": "Laravel routing system",
    "content": `# Routing

## Basic Routing

The most basic Laravel routes accept a URI and a closure:

\`\`\`php
Route::get('/greeting', function () {
    return 'Hello World';
});
\`\`\`

## Available Router Methods

\`\`\`php
Route::get($uri, $callback);
Route::post($uri, $callback);
Route::put($uri, $callback);
Route::patch($uri, $callback);
Route::delete($uri, $callback);
Route::options($uri, $callback);
\`\`\`

## Route Parameters

\`\`\`php
Route::get('/user/{id}', function (string $id) {
    return 'User '.$id;
});
\`\`\`

## Named Routes

\`\`\`php
Route::get('/user/profile', function () {
    // ...
})->name('profile');
\`\`\`

## Route Groups

\`\`\`php
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        // Uses auth middleware
    });
});
\`\`\``
  },
  "eloquent": {
    "title": "Eloquent ORM",
    "description": "Laravel's powerful ORM",
    "content": `# Eloquent ORM

## Introduction

Eloquent is Laravel's ActiveRecord ORM. Each database table has a corresponding "Model" for interacting with that table.

## Defining Models

\`\`\`php
namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class Flight extends Model
{
    protected $fillable = ['name', 'airline'];
}
\`\`\`

## Retrieving Models

\`\`\`php
$flights = Flight::all();
$flight = Flight::find(1);
$flight = Flight::where('active', 1)->first();
\`\`\`

## Creating Models

\`\`\`php
$flight = new Flight;
$flight->name = 'Flight to LA';
$flight->save();

// Or using mass assignment
$flight = Flight::create([
    'name' => 'Flight to LA',
    'airline' => 'United',
]);
\`\`\`

## Updating Models

\`\`\`php
$flight = Flight::find(1);
$flight->name = 'New Name';
$flight->save();
\`\`\`

## Relationships

\`\`\`php
// One to Many
public function posts()
{
    return $this->hasMany(Post::class);
}

// Many to Many
public function roles()
{
    return $this->belongsToMany(Role::class);
}
\`\`\``
  },
  "migrations": {
    "title": "Database Migrations",
    "description": "Version control for your database",
    "content": `# Database Migrations

## Introduction

Migrations are like version control for your database, allowing your team to define and share the application's database schema definition.

## Generating Migrations

\`\`\`shell
php artisan make:migration create_flights_table
\`\`\`

## Migration Structure

\`\`\`php
use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('airline');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flights');
    }
};
\`\`\`

## Running Migrations

\`\`\`shell
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh
\`\`\``
  },
  "validation": {
    "title": "Validation",
    "description": "Input validation in Laravel",
    "content": `# Validation

## Introduction

Laravel provides several approaches to validate your application's incoming data.

## Validating Form Requests

\`\`\`php
$validated = $request->validate([
    'title' => 'required|unique:posts|max:255',
    'body' => 'required',
    'author.name' => 'required',
]);
\`\`\`

## Available Validation Rules

- required
- email
- unique:table,column
- max:value
- min:value
- numeric
- string
- date
- confirmed
- in:foo,bar,...
- exists:table,column

## Form Request Validation

\`\`\`php
namespace App\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

class StorePostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|unique:posts|max:255',
            'body' => 'required',
        ];
    }
}
\`\`\`

## Using Form Requests

\`\`\`php
public function store(StorePostRequest $request): Response
{
    $validated = $request->validated();
    // Store the post...
}
\`\`\``
  },
  "artisan": {
    "title": "Artisan Console",
    "description": "Laravel's command line interface",
    "content": `# Artisan Console

## Introduction

Artisan is the command line interface included with Laravel. It provides helpful commands for your application development.

## Common Commands

\`\`\`shell
# View all available commands
php artisan list

# Serve the application
php artisan serve

# Run migrations
php artisan migrate

# Create a new controller
php artisan make:controller UserController

# Create a new model
php artisan make:model Flight

# Create a model with migration
php artisan make:model Flight -m

# Clear application cache
php artisan cache:clear

# Clear route cache
php artisan route:clear

# Clear config cache
php artisan config:clear
\`\`\`

## Writing Custom Commands

\`\`\`shell
php artisan make:command SendEmails
\`\`\`

\`\`\`php
namespace App\\Console\\Commands;

use Illuminate\\Console\\Command;

class SendEmails extends Command
{
    protected $signature = 'mail:send {user}';
    protected $description = 'Send a marketing email to a user';

    public function handle()
    {
        $userId = $this->argument('user');
        // Send email...
    }
}
\`\`\``
  }
};

export function getAllTopics() {
  return Object.keys(LARAVEL_TOPICS);
}

export function getTopic(name: string) {
  return LARAVEL_TOPICS[name as keyof typeof LARAVEL_TOPICS];
}

export function searchTopics(query: string) {
  const results: Array<{ name: string; title: string; description: string }> = [];
  const lowerQuery = query.toLowerCase();
  
  for (const [name, topic] of Object.entries(LARAVEL_TOPICS)) {
    if (
      name.toLowerCase().includes(lowerQuery) ||
      topic.title.toLowerCase().includes(lowerQuery) ||
      topic.description.toLowerCase().includes(lowerQuery)
    ) {
      results.push({
        name,
        title: topic.title,
        description: topic.description
      });
    }
  }
  
  return results;
}
