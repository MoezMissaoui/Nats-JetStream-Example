# Central NATS Microservice (PHP + Symfony Console)

CLI utilities to publish messages to NATS JetStream and run a persistent listener.

## Overview
- `nats:publish` publishes a message to stream `CENTER_DATA` (auto-creates if missing).
- `nats:listen` runs a consumer on stream `NODE_DATA` with subject filter `nodes.data.>`.
- Environment is loaded from `.env` via `src/console.php`.

## Prerequisites
- PHP 8.1+ and Composer
- A NATS server with JetStream enabled
- Optional: Docker for local NATS

## Setup
1. Install dependencies:
   - `composer install`
2. Configure environment:
   - Copy `.env.example` to `.env`
   - Edit values (e.g., `NATS_HOST`, `NATS_PORT`, `NATS_USER`, `NATS_PASS`)

## Environment Variables
- `NATS_HOST` — NATS host (default `nats`)
- `NATS_PORT` — NATS port (default `4222`)
- `NATS_USER` — Username (optional)
- `NATS_PASS` — Password (optional)

## Commands
- Publish a message:
  - `php .\src\console.php nats:publish "your message here"`
  - If omitted, defaults to `hello from center`
  - Default subject: `center.data.test.123456`
  - Stream auto-config: `CENTER_DATA`, retention `WORK_QUEUE`, storage `MEMORY`, subjects `center.data.*.*`

- Run the listener:
  - `php .\src\console.php nats:listen`
  - Consumer: `Center_Data_Consumer`
  - Stream: `NODE_DATA`, subject filter `nodes.data.>`

## Run NATS Locally (Docker)
- Basic JetStream-enabled server:
  - `docker run -it --rm -p 4222:4222 -p 8222:8222 nats:latest -js`
- Then set in `.env`:
  - `NATS_HOST=localhost`
  - `NATS_PORT=4222`

## Project Structure
- `src/console.php` — Console entrypoint
- `src/Command/NatsPublishCommand.php` — Publish command
- `src/Command/NatsListenCommand.php` — Listen command

## Troubleshooting
- Connection failed: verify `NATS_HOST`/`NATS_PORT`, and server is reachable.
- Auth errors: set `NATS_USER`/`NATS_PASS` correctly.
- JetStream errors: ensure NATS started with `-js`.
- Windows quoting: use `"message with spaces"` in PowerShell.