import { PrismaClient } from "../generated/prisma/client";
import { PrismaPg } from "@prisma/adapter-pg";
import { Pool } from "pg";

const globalForPrisma = globalThis;

function getAdapter() {
  const connectionString = process.env.DATABASE_URL;
  if (!connectionString) {
    throw new Error("DATABASE_URL is required to initialize Prisma (PostgreSQL).");
  }

  const pool = globalForPrisma.__prismaPgPool || new Pool({ connectionString });
  if (process.env.NODE_ENV !== "production") {
    globalForPrisma.__prismaPgPool = pool;
  }

  return new PrismaPg(pool);
}

export const prisma =
  globalForPrisma.__prisma ||
  new PrismaClient({
    adapter: getAdapter(),
    log: process.env.NODE_ENV === "development" ? ["warn", "error"] : ["error"],
  });

if (process.env.NODE_ENV !== "production") {
  globalForPrisma.__prisma = prisma;
}

