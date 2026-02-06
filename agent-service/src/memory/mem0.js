// config/mem0.js
// Mem0 Configuration for Semantic Memory
import { MEM0_API_KEY } from '../config/index.js';
import { MemoryClient } from 'mem0ai';

// Initialize Mem0 client
export const memoryClient = new MemoryClient({
  apiKey: MEM0_API_KEY,
});

export default memoryClient;