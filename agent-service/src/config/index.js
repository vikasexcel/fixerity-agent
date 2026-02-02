/**
 * Agent service configuration.
 * Loads and exports environment variables for the Laravel API and runtime.
 */

const LARAVEL_API_BASE_URL = process.env.LARAVEL_API_BASE_URL ?? 'http://localhost:8000/api';

export { LARAVEL_API_BASE_URL };
