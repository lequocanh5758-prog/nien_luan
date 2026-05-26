FROM node:22-slim

# Install GitNexus globally
RUN npm install -g gitnexus@latest

# Set working directory
WORKDIR /repos

# Expose MCP port (not needed for stdio but good practice)
EXPOSE 4747

# Start MCP server
CMD ["gitnexus", "mcp"]
