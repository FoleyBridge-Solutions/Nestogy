#!/usr/bin/env node

import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
  Tool,
} from "@modelcontextprotocol/sdk/types.js";
import { getAllTopics, getTopic, searchTopics } from "./laravel-docs.js";

const server = new Server(
  {
    name: "laravel-server",
    version: "1.0.0",
  },
  {
    capabilities: {
      tools: {},
    },
  }
);

const tools: Tool[] = [
  {
    name: "list_laravel_topics",
    description: "Get a list of all available Laravel documentation topics",
    inputSchema: {
      type: "object",
      properties: {},
    },
  },
  {
    name: "get_laravel_topic_details",
    description: "Get detailed information about a specific Laravel topic",
    inputSchema: {
      type: "object",
      properties: {
        topicName: {
          type: "string",
          description: 'Name of the topic (e.g., "mcp", "routing", "eloquent")',
        },
      },
      required: ["topicName"],
    },
  },
  {
    name: "search_laravel_docs",
    description: "Search for topics in Laravel documentation by keyword",
    inputSchema: {
      type: "object",
      properties: {
        query: {
          type: "string",
          description: "Search query",
        },
      },
      required: ["query"],
    },
  },
];

server.setRequestHandler(ListToolsRequestSchema, async () => {
  return { tools };
});

server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;

  try {
    if (name === "list_laravel_topics") {
      const topics = getAllTopics();
      const topicsList = topics.map((topicName) => {
        const topic = getTopic(topicName);
        return {
          name: topicName,
          title: topic.title,
          description: topic.description,
        };
      });

      return {
        content: [
          {
            type: "text",
            text: JSON.stringify(topicsList, null, 2),
          },
        ],
      };
    }

    if (name === "get_laravel_topic_details") {
      const topicName = String(args?.topicName);
      const topic = getTopic(topicName);

      if (!topic) {
        return {
          content: [
            {
              type: "text",
              text: `Topic "${topicName}" not found. Use list_laravel_topics to see available topics.`,
            },
          ],
          isError: true,
        };
      }

      let content = `# ${topic.title}\n\n${topic.description}\n\n`;
      
      if ('sections' in topic) {
        for (const [sectionName, sectionContent] of Object.entries(topic.sections)) {
          content += `\n${sectionContent}\n`;
        }
      } else if ('content' in topic) {
        content += topic.content;
      }

      return {
        content: [
          {
            type: "text",
            text: content,
          },
        ],
      };
    }

    if (name === "search_laravel_docs") {
      const query = String(args?.query);
      const results = searchTopics(query);

      if (results.length === 0) {
        return {
          content: [
            {
              type: "text",
              text: `No results found for "${query}"`,
            },
          ],
        };
      }

      return {
        content: [
          {
            type: "text",
            text: JSON.stringify(results, null, 2),
          },
        ],
      };
    }

    return {
      content: [
        {
          type: "text",
          text: `Unknown tool: ${name}`,
        },
      ],
      isError: true,
    };
  } catch (error) {
    return {
      content: [
        {
          type: "text",
          text: `Error: ${error instanceof Error ? error.message : String(error)}`,
        },
      ],
      isError: true,
    };
  }
});

async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error("Laravel MCP Server running on stdio");
}

main().catch((error) => {
  console.error("Fatal error in main():", error);
  process.exit(1);
});
