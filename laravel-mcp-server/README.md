# Laravel MCP Server

A Model Context Protocol (MCP) server that provides Laravel 12 documentation, focusing on the new Laravel MCP package and other core features.

## Features

- **List Topics**: Get all available Laravel documentation topics
- **Topic Details**: Get detailed documentation for specific topics
- **Search**: Search through Laravel documentation

## Topics Covered

- **Laravel MCP**: Complete guide to Laravel's MCP integration
  - Installation
  - Creating servers, tools, resources, and prompts
  - Authentication (OAuth 2.1, Sanctum)
  - Testing
- **Installation**: Laravel setup and requirements
- **Routing**: Route definitions and configuration
- **Eloquent ORM**: Database models and relationships
- **Migrations**: Database schema management
- **Validation**: Input validation
- **Artisan Console**: Command line interface

## Installation

```bash
npm install
npm run build
```

## Usage

This server is designed to be used with MCP clients. Add it to your MCP client configuration:

```json
{
  "mcpServers": {
    "laravel-server": {
      "command": "node",
      "args": ["/path/to/laravel-mcp-server/build/index.js"]
    }
  }
}
```

Or in opencode.json:

```json
{
  "mcp": {
    "laravel-server": {
      "type": "local",
      "command": ["npx", "-y", "/path/to/laravel-mcp-server"],
      "enabled": true
    }
  }
}
```

## Available Tools

### list_laravel_topics
Returns a list of all available Laravel documentation topics.

### get_laravel_topic_details
Get detailed documentation for a specific topic.

Parameters:
- `topicName` (required): Name of the topic (e.g., "mcp", "routing", "eloquent")

### search_laravel_docs
Search through Laravel documentation.

Parameters:
- `query` (required): Search query string

## Development

```bash
# Watch mode
npm run watch

# Build
npm run build
```

## License

MIT
