/**
 * Agent service configuration.
 * Loads and exports environment variables for the Laravel API, Mem0, OpenAI, and runtime.
 */

const LARAVEL_API_BASE_URL = process.env.LARAVEL_API_BASE_URL ?? 'http://116.202.210.102:8002/api';
const MEM0_API_KEY = process.env.MEM0_API_KEY ?? 'm0-0qHnIyF5zRSjXX4WPOSFRmiCKlyejaxSV2M7gmW1';
const OPENAI_API_KEY = process.env.OPENAI_API_KEY ?? '';
const PORT = process.env.PORT ?? 3017;
const REDIS_URL = process.env.REDIS_URL ?? '';
const NEGOTIATION_TIME_SECONDS = Number(process.env.NEGOTIATION_TIME_SECONDS ?? 60);
const NEGOTIATION_MAX_ROUNDS = Number(process.env.NEGOTIATION_MAX_ROUNDS ?? 5);

export { LARAVEL_API_BASE_URL, MEM0_API_KEY, OPENAI_API_KEY, PORT, REDIS_URL, NEGOTIATION_TIME_SECONDS, NEGOTIATION_MAX_ROUNDS };
