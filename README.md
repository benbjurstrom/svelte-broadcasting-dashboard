# Svelte Broadcasting Demo

A Laravel broadcasting demo with Inertia.js, Svelte 5, and Tailwind CSS.

## Prerequisites

This project depends on `@laravel/echo-svelte`, which is not yet published to npm. You need to clone and build it locally.

1. Clone the `svelte-5` branch of Laravel Echo somewhere on your machine:

```bash
git clone -b svelte-5 https://github.com/laravel/echo.git /path/to/echo
```

2. Build the Svelte package:

```bash
cd /path/to/echo/packages/svelte
pnpm install
pnpm run build
```

3. Update `package.json` in this project to point to your local copy:

```json
"@laravel/echo-svelte": "file:/path/to/echo/packages/svelte"
```

4. Install dependencies:

```bash
npm install
```

## Getting Started

```bash
composer setup
composer dev
```

Navigate to `http://127.0.0.1:8000/broadcasting` to see the real-time broadcasting demo.
