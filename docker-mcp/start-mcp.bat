@echo off
echo Starting GitNexus MCP Server via Docker...
docker run -i --rm ^
  -v "D:\PHP_WS:/repos/PHP_WS:ro" ^
  -v "D:\PHP_WS\.gitnexus:/repos/PHP_WS/.gitnexus" ^
  --name gitnexus-mcp ^
  node:22-slim ^
  npx -y gitnexus@latest mcp
