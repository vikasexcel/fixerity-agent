/**
 * Agent service configuration.
 * Loads and exports environment variables for the Laravel API, Mem0, OpenAI, and runtime.
 */

const LARAVEL_API_BASE_URL = process.env.LARAVEL_API_BASE_URL ?? 'http://localhost:8000/api';
const MEM0_API_KEY = process.env.MEM0_API_KEY ?? 'm0-0qHnIyF5zRSjXX4WPOSFRmiCKlyejaxSV2M7gmW1';
const OPENAI_API_KEY = process.env.OPENAI_API_KEY ?? '';
const PORT = Number(process.env.PORT ?? process.env.AGENT_SERVICE_PORT ?? 3001);

export { LARAVEL_API_BASE_URL, MEM0_API_KEY, OPENAI_API_KEY, PORT };
