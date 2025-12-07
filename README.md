# Universal HTTP Client Profiler Bundle

A ready-to-use Symfony bundle that profiles outgoing HTTP requests and exposes them in the Web Profiler. It decorates the default `http_client`, records request/response details, and stores CLI session traces for later inspection.

## Installation

```bash
composer require universal/http-client-profiler-bundle
```

## Usage

1. Enable the bundle in your Symfony application as you would with any third-party bundle.
2. Browse any page while making HTTP requests (or run CLI commands); the "HTTP Client" panel will appear in the Web Profiler with the captured traces.
3. CLI sessions are stored under `var/http-profiler` and are listed in the collector UI.

The bundle works out of the box with sensible defaults—no additional configuration or optional integrations are required.
