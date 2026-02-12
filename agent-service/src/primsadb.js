import 'dotenv/config';
import { PrismaClient } from '@prisma/client';
import { PrismaPg } from '@prisma/adapter-pg';

const connectionString = process.env.DATABASE_URL;
if (!connectionString) {
  throw new Error(
    'DATABASE_URL is not set. Add it to .env (e.g. postgresql://postgres:postgres@localhost:5436/agentdb when using docker-compose postgres).'
  );
}

const adapter = new PrismaPg({ connectionString });
const prisma = new PrismaClient({ adapter });

async function connectDB() {
  try {
    await prisma.$connect();
    console.log('Database connected successfully');
  } catch (error) {
    const hint =
      error.code === 'P1000'
        ? ' Check: (1) Postgres is running: cd agent-service && docker compose up -d. (2) DATABASE_URL in .env matches docker-compose (postgres/postgres@localhost:5436/agentdb). (3) If you changed credentials, recreate the DB volume: docker compose down -v && docker compose up -d, then re-run migrations.'
        : '';
    console.error('❌ Error connecting to the database:', error.message + hint);
    throw error;
  }
}

export { prisma, connectDB };