# Universal HTTP Client Profiler Bundle

A Symfony bundle that profiles all outgoing HTTP requests in any execution context: Web requests, CLI commands, Messenger workers, cron jobs, and background processes. It integrates with the Symfony Web Profiler and adds a dedicated panel to inspect every HTTP call with method, URL, status code, duration, headers, body (truncated), errors, and full stack trace.

The bundle automatically decorates Symfony HttpClient and optionally integrates with Guzzle via middleware. When running under CLI, each process produces a profiling session saved as a JSON file, which can later be viewed inside a dedicated "CLI Sessions" panel in the Web Profiler.

Configuration is optional. You can enable or disable profiling, mask sensitive headers, limit stored body length, enable stack trace collection, and persist or skip CLI sessions. By default it works out-of-the-box with reasonable settings.

Main features: outgoing HTTP request capture, unified Web + CLI profiling, Symfony Web Profiler panel, request detail view, timeline, stack trace, CLI session archive, Guzzle compatibility, masking and truncation rules, extensible architecture.

Ideal for debugging API integrations, monitoring long-running workers, understanding performance bottlenecks, and auditing external services usage.

MIT license.
