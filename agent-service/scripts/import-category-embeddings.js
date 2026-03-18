import "dotenv/config";
import fs from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";
import OpenAI from "openai";
import { Pinecone } from "@pinecone-database/pinecone";

/**
 * Usage:
 *   npm run embed:categories
 *   npm run embed:categories -- --dry-run
 *   npm run embed:categories -- --namespace=categories --limit=100
 */

const SCRIPT_NAME = "CategoryEmbeddingImport";
const SOURCE_FILE = "src/data/categories.json";
const DEFAULT_INDEX_NAME = process.env.PINECONE_INDEX_NAME || "fixerity-agent";
const DEFAULT_NAMESPACE = process.env.PINECONE_CATEGORIES_NAMESPACE || "categories";
const DEFAULT_EMBEDDING_MODEL = process.env.OPENAI_EMBEDDING_MODEL || "text-embedding-3-large";
const DEFAULT_EMBEDDING_DIMENSIONS = parsePositiveInteger(
  process.env.OPENAI_EMBEDDING_DIMENSIONS,
  1024
);
const DEFAULT_EMBEDDING_BATCH_SIZE = parsePositiveInteger(
  process.env.CATEGORY_EMBEDDING_BATCH_SIZE,
  50
);
const DEFAULT_UPSERT_BATCH_SIZE = parsePositiveInteger(process.env.CATEGORY_UPSERT_BATCH_SIZE, 100);
const DEFAULT_MAX_RETRIES = parsePositiveInteger(process.env.CATEGORY_IMPORT_MAX_RETRIES, 4);
const RETRY_BASE_DELAY_MS = parsePositiveInteger(process.env.CATEGORY_IMPORT_RETRY_BASE_MS, 1000);

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const taxonomyFilePath = path.resolve(__dirname, "..", SOURCE_FILE);

const cliOptions = parseCliArgs(process.argv.slice(2));
const runConfig = {
  namespace: cliOptions.namespace || DEFAULT_NAMESPACE,
  indexName: cliOptions.index || DEFAULT_INDEX_NAME,
  embeddingModel: cliOptions.embeddingModel || DEFAULT_EMBEDDING_MODEL,
  embeddingDimensions:
    cliOptions.embeddingDimensions != null
      ? parsePositiveInteger(cliOptions.embeddingDimensions, DEFAULT_EMBEDDING_DIMENSIONS)
      : DEFAULT_EMBEDDING_DIMENSIONS,
  embeddingBatchSize:
    cliOptions.embeddingBatchSize != null
      ? parsePositiveInteger(cliOptions.embeddingBatchSize, DEFAULT_EMBEDDING_BATCH_SIZE)
      : DEFAULT_EMBEDDING_BATCH_SIZE,
  upsertBatchSize:
    cliOptions.upsertBatchSize != null
      ? parsePositiveInteger(cliOptions.upsertBatchSize, DEFAULT_UPSERT_BATCH_SIZE)
      : DEFAULT_UPSERT_BATCH_SIZE,
  maxRetries:
    cliOptions.maxRetries != null
      ? parsePositiveInteger(cliOptions.maxRetries, DEFAULT_MAX_RETRIES)
      : DEFAULT_MAX_RETRIES,
  limit:
    cliOptions.limit != null ? parsePositiveInteger(cliOptions.limit, cliOptions.limit) : null,
  dryRun: cliOptions.dryRun === true,
};

const openai = new OpenAI({ apiKey: process.env.OPENAI_API_KEY });
const pinecone = new Pinecone({ apiKey: process.env.PINECONE_API_KEY });

main().catch((error) => {
  logError("Import failed", { message: error.message });
  if (error.stack) {
    console.error(error.stack);
  }
  process.exitCode = 1;
});

async function main() {
  validateRequiredEnv();

  logSection("Starting category embedding import");
  logInfo("Configuration", {
    indexName: runConfig.indexName,
    namespace: runConfig.namespace,
    embeddingModel: runConfig.embeddingModel,
    embeddingDimensions: runConfig.embeddingDimensions,
    embeddingBatchSize: runConfig.embeddingBatchSize,
    upsertBatchSize: runConfig.upsertBatchSize,
    maxRetries: runConfig.maxRetries,
    dryRun: runConfig.dryRun,
    limit: runConfig.limit ?? "none",
  });

  const taxonomy = await loadTaxonomyFile(taxonomyFilePath);
  const dataset = buildDataset(taxonomy);
  const records = runConfig.limit ? dataset.records.slice(0, runConfig.limit) : dataset.records;

  logInfo("Taxonomy loaded", {
    taxonomyVersion: taxonomy.version || "unknown",
    categories: taxonomy.categories.length,
    subcategories: taxonomy.subcategories.length,
    services: taxonomy.services.length,
    preparedRecords: records.length,
    skippedRecords: dataset.skippedRecords,
  });

  logInfo("Prepared record counts", countByRecordType(records));

  if (records.length === 0) {
    logWarn("No valid records were prepared. Nothing to embed.");
    return;
  }

  if (runConfig.dryRun) {
    logInfo("Dry run enabled. Skipping embedding and Pinecone upsert.");
    logInfo("Sample record preview", previewRecord(records[0]));
    return;
  }

  const index = pinecone.index(runConfig.indexName);
  const namespace = index.namespace(runConfig.namespace);

  const vectors = await createVectors(records);
  logInfo("Vector generation complete", { vectors: vectors.length });

  await upsertVectors(namespace, vectors);

  logSection("Import complete");
  logInfo("Final summary", {
    namespace: runConfig.namespace,
    indexName: runConfig.indexName,
    importedRecords: vectors.length,
    ...countByRecordType(records),
  });
}

function validateRequiredEnv() {
  const missing = ["OPENAI_API_KEY", "PINECONE_API_KEY"].filter((name) => !process.env[name]);
  if (missing.length > 0) {
    throw new Error(`Missing required environment variables: ${missing.join(", ")}`);
  }
}

async function loadTaxonomyFile(filePath) {
  const raw = await fs.readFile(filePath, "utf8");
  const parsed = JSON.parse(raw);

  if (!parsed || typeof parsed !== "object") {
    throw new Error("Taxonomy file is not a valid JSON object.");
  }
  if (!Array.isArray(parsed.categories) || !Array.isArray(parsed.subcategories) || !Array.isArray(parsed.services)) {
    throw new Error("Taxonomy file must contain categories, subcategories, and services arrays.");
  }

  return parsed;
}

function buildDataset(taxonomy) {
  const categoriesById = new Map(taxonomy.categories.map((category) => [category.id, category]));
  const subcategoriesById = new Map(
    taxonomy.subcategories.map((subcategory) => [subcategory.id, subcategory])
  );
  const subcategoriesByCategoryId = groupBy(taxonomy.subcategories, "category_id");
  const servicesBySubcategoryId = groupBy(taxonomy.services, "subcategory_id");

  const records = [];
  let skippedRecords = 0;

  for (const category of taxonomy.categories) {
    records.push(
      buildCategoryRecord({
        taxonomyVersion: taxonomy.version || "unknown",
        category,
        subcategories: subcategoriesByCategoryId.get(category.id) || [],
        servicesBySubcategoryId,
      })
    );
  }

  for (const subcategory of taxonomy.subcategories) {
    const category = categoriesById.get(subcategory.category_id);
    if (!category) {
      skippedRecords++;
      logWarn("Skipping subcategory with missing category", {
        subcategoryId: subcategory.id,
        categoryId: subcategory.category_id,
      });
      continue;
    }

    records.push(
      buildSubcategoryRecord({
        taxonomyVersion: taxonomy.version || "unknown",
        category,
        subcategory,
        services: servicesBySubcategoryId.get(subcategory.id) || [],
      })
    );
  }

  for (const service of taxonomy.services) {
    const subcategory = subcategoriesById.get(service.subcategory_id);
    if (!subcategory) {
      skippedRecords++;
      logWarn("Skipping service with missing subcategory", {
        serviceId: service.id,
        subcategoryId: service.subcategory_id,
      });
      continue;
    }

    const category = categoriesById.get(subcategory.category_id);
    if (!category) {
      skippedRecords++;
      logWarn("Skipping service with missing parent category", {
        serviceId: service.id,
        subcategoryId: subcategory.id,
        categoryId: subcategory.category_id,
      });
      continue;
    }

    records.push(
      buildServiceRecord({
        taxonomyVersion: taxonomy.version || "unknown",
        category,
        subcategory,
        service,
      })
    );
  }

  return {
    records,
    skippedRecords,
  };
}

function buildCategoryRecord({ taxonomyVersion, category, subcategories, servicesBySubcategoryId }) {
  const totalServices = subcategories.reduce(
    (count, subcategory) => count + (servicesBySubcategoryId.get(subcategory.id) || []).length,
    0
  );
  const sampleServices = uniqueStrings(
    subcategories.flatMap((subcategory) =>
      (servicesBySubcategoryId.get(subcategory.id) || []).map((service) => service.name)
    )
  ).slice(0, 20);

  const aliases = buildAliases([
    category.name,
    category.description,
    `${category.name} services`,
    replaceAmpersand(category.name),
  ]);

  const semanticText = compactLines([
    `Record type: category`,
    `Category name: ${category.name}`,
    `Description: ${category.description || `${category.name} services`}`,
    `Hierarchy path: ${category.name}`,
    subcategories.length > 0
      ? `Subcategories: ${subcategories.map((item) => item.name).join(", ")}`
      : null,
    sampleServices.length > 0 ? `Sample services: ${sampleServices.join(", ")}` : null,
    aliases.length > 0 ? `Search aliases: ${aliases.join(", ")}` : null,
  ]);

  return createPreparedRecord({
    id: `category:${category.id}`,
    recordType: "category",
    displayName: category.name,
    category,
    subcategory: null,
    service: null,
    semanticText,
    aliases,
    keywords: [],
    sourceName: category.name,
    taxonomyVersion,
    extraMetadata: {
      description: safeText(category.description || `${category.name} services`, 500),
      subcategoryCount: subcategories.length,
      serviceCount: totalServices,
    },
  });
}

function buildSubcategoryRecord({ taxonomyVersion, category, subcategory, services }) {
  const sampleServices = uniqueStrings(services.map((service) => service.name)).slice(0, 20);
  const aliases = buildAliases([
    subcategory.name,
    `${category.name} ${subcategory.name}`,
    `${subcategory.name} services`,
    `${replaceAmpersand(category.name)} ${subcategory.name}`,
  ]);

  const semanticText = compactLines([
    `Record type: subcategory`,
    `Category name: ${category.name}`,
    `Subcategory name: ${subcategory.name}`,
    `Hierarchy path: ${category.name} > ${subcategory.name}`,
    sampleServices.length > 0 ? `Services: ${sampleServices.join(", ")}` : null,
    aliases.length > 0 ? `Search aliases: ${aliases.join(", ")}` : null,
  ]);

  return createPreparedRecord({
    id: `subcategory:${subcategory.id}`,
    recordType: "subcategory",
    displayName: subcategory.name,
    category,
    subcategory,
    service: null,
    semanticText,
    aliases,
    keywords: [],
    sourceName: subcategory.name,
    taxonomyVersion,
    extraMetadata: {
      parentId: category.id,
      parentName: category.name,
      serviceCount: services.length,
    },
  });
}

function buildServiceRecord({ taxonomyVersion, category, subcategory, service }) {
  const serviceAction = deriveServiceAction(subcategory.name, service.name);
  const keywords = uniqueStrings(service.keywords || []);

  const aliases = buildAliases([
    service.name,
    `${subcategory.name} ${serviceAction}`,
    `${category.name} ${serviceAction}`,
    `${subcategory.name} service`,
    `${replaceAmpersand(category.name)} ${serviceAction}`,
    ...keywords,
  ]);

  const serviceTraits = uniqueStrings([
    service.license_likely_required ? "licensed service may be required" : "license typically not required",
    service.onsite_required ? "on-site service" : "can start without on-site requirement",
    service.regulated_domain ? "regulated domain service" : "non-regulated domain service",
  ]);

  const semanticText = compactLines([
    `Record type: service`,
    `Category name: ${category.name}`,
    `Subcategory name: ${subcategory.name}`,
    `Service name: ${service.name}`,
    `Hierarchy path: ${category.name} > ${subcategory.name} > ${service.name}`,
    serviceAction ? `Service action: ${serviceAction}` : null,
    keywords.length > 0 ? `Keywords: ${keywords.join(", ")}` : null,
    `License likely required: ${service.license_likely_required ? "yes" : "no"}`,
    `On-site required: ${service.onsite_required ? "yes" : "no"}`,
    `Regulated domain: ${service.regulated_domain ? "yes" : "no"}`,
    `Traits: ${serviceTraits.join(", ")}`,
    aliases.length > 0 ? `Search aliases: ${aliases.join(", ")}` : null,
  ]);

  return createPreparedRecord({
    id: `service:${service.id}`,
    recordType: "service",
    displayName: service.name,
    category,
    subcategory,
    service,
    semanticText,
    aliases,
    keywords,
    sourceName: service.name,
    taxonomyVersion,
    extraMetadata: {
      parentId: subcategory.id,
      parentName: subcategory.name,
      licenseLikelyRequired: Boolean(service.license_likely_required),
      onsiteRequired: Boolean(service.onsite_required),
      regulatedDomain: Boolean(service.regulated_domain),
      serviceAction,
    },
  });
}

function createPreparedRecord({
  id,
  recordType,
  displayName,
  category,
  subcategory,
  service,
  semanticText,
  aliases,
  keywords,
  sourceName,
  taxonomyVersion,
  extraMetadata = {},
}) {
  const pathParts = [category?.name, subcategory?.name, service?.name].filter(Boolean);
  const pathText = pathParts.join(" > ");
  const metadata = sanitizeMetadata({
    schemaVersion: "category-taxonomy-v1",
    taxonomyVersion,
    recordType,
    recordId: id,
    title: displayName,
    canonicalName: sourceName,
    hierarchyPath: pathText,
    hierarchyDepth: pathParts.length,
    categoryId: category?.id,
    categoryName: category?.name,
    categorySlug: slugify(category?.name),
    subcategoryId: subcategory?.id,
    subcategoryName: subcategory?.name,
    subcategorySlug: slugify(subcategory?.name),
    serviceId: service?.id,
    serviceName: service?.name,
    serviceSlug: slugify(service?.name),
    keywordsText: keywords.join(", "),
    keywordCount: keywords.length,
    aliasesText: aliases.join(", "),
    aliasCount: aliases.length,
    semanticText: safeText(semanticText, 3500),
    source: "category-taxonomy-import",
    sourceFile: SOURCE_FILE,
    updatedAt: new Date().toISOString(),
    ...extraMetadata,
  });

  return {
    id,
    recordType,
    text: semanticText,
    metadata,
  };
}

async function createVectors(records) {
  const vectors = [];
  const totalBatches = Math.ceil(records.length / runConfig.embeddingBatchSize);

  for (let i = 0; i < records.length; i += runConfig.embeddingBatchSize) {
    const batch = records.slice(i, i + runConfig.embeddingBatchSize);
    const batchNumber = Math.floor(i / runConfig.embeddingBatchSize) + 1;

    logInfo("Embedding batch", {
      batch: `${batchNumber}/${totalBatches}`,
      batchSize: batch.length,
      processedBeforeBatch: i,
    });

    const embeddings = await withRetry(
      () =>
        openai.embeddings.create({
          model: runConfig.embeddingModel,
          input: batch.map((record) => record.text),
          dimensions: runConfig.embeddingDimensions,
        }),
      `OpenAI embedding batch ${batchNumber}`
    );

    if (!embeddings?.data || embeddings.data.length !== batch.length) {
      throw new Error(
        `Embedding response size mismatch for batch ${batchNumber}. Expected ${batch.length}, received ${
          embeddings?.data?.length ?? 0
        }.`
      );
    }

    for (let index = 0; index < batch.length; index++) {
      const record = batch[index];
      const values = embeddings.data[index]?.embedding;
      if (!Array.isArray(values) || values.length === 0) {
        throw new Error(`Invalid embedding returned for record ${record.id}`);
      }

      vectors.push({
        id: record.id,
        values,
        metadata: record.metadata,
      });
    }
  }

  return vectors;
}

async function upsertVectors(namespace, vectors) {
  if (vectors.length === 0) {
    logWarn("No vectors generated. Skipping Pinecone upsert.");
    return;
  }

  const totalBatches = Math.ceil(vectors.length / runConfig.upsertBatchSize);

  for (let i = 0; i < vectors.length; i += runConfig.upsertBatchSize) {
    const batch = vectors.slice(i, i + runConfig.upsertBatchSize);
    const batchNumber = Math.floor(i / runConfig.upsertBatchSize) + 1;

    logInfo("Upserting batch", {
      batch: `${batchNumber}/${totalBatches}`,
      batchSize: batch.length,
      namespace: runConfig.namespace,
    });

    await withRetry(() => pineconeUpsert(namespace, batch), `Pinecone upsert batch ${batchNumber}`);
  }
}

async function pineconeUpsert(namespace, records) {
  if (!Array.isArray(records) || records.length === 0) {
    throw new Error("Cannot upsert an empty Pinecone batch.");
  }

  await namespace.upsert({ records });
}

async function withRetry(operation, label) {
  let lastError = null;

  for (let attempt = 1; attempt <= runConfig.maxRetries; attempt++) {
    try {
      if (attempt > 1) {
        logInfo("Retry attempt", { label, attempt, maxRetries: runConfig.maxRetries });
      }
      return await operation();
    } catch (error) {
      lastError = error;
      if (attempt === runConfig.maxRetries) {
        break;
      }

      const delayMs = RETRY_BASE_DELAY_MS * 2 ** (attempt - 1);
      logWarn("Operation failed, retrying", {
        label,
        attempt,
        delayMs,
        message: error.message,
      });
      await sleep(delayMs);
    }
  }

  throw lastError;
}

function parseCliArgs(args) {
  const result = {};

  for (let i = 0; i < args.length; i++) {
    const arg = args[i];
    if (!arg.startsWith("--")) {
      throw new Error(`Unexpected argument: ${arg}`);
    }

    if (arg === "--dry-run") {
      result.dryRun = true;
      continue;
    }

    const [key, inlineValue] = arg.slice(2).split("=", 2);
    if (inlineValue != null) {
      result[key] = inlineValue;
      continue;
    }

    const next = args[i + 1];
    if (!next || next.startsWith("--")) {
      throw new Error(`Missing value for --${key}`);
    }

    result[key] = next;
    i++;
  }

  return result;
}

function parsePositiveInteger(value, fallback) {
  if (value == null || value === "") {
    return fallback;
  }

  const parsed = Number.parseInt(String(value), 10);
  if (!Number.isInteger(parsed) || parsed <= 0) {
    throw new Error(`Expected a positive integer, received "${value}"`);
  }

  return parsed;
}

function groupBy(items, field) {
  const map = new Map();

  for (const item of items) {
    const key = item?.[field];
    if (!map.has(key)) {
      map.set(key, []);
    }
    map.get(key).push(item);
  }

  return map;
}

function buildAliases(values) {
  const variants = [];

  for (const value of values) {
    if (!value || typeof value !== "string") {
      continue;
    }

    const trimmed = value.trim();
    if (!trimmed) {
      continue;
    }

    variants.push(trimmed);
    variants.push(replaceAmpersand(trimmed));
    variants.push(trimmed.replaceAll("/", " "));
    variants.push(trimmed.replaceAll("-", " "));
  }

  return uniqueStrings(variants);
}

function deriveServiceAction(subcategoryName, serviceName) {
  if (!subcategoryName || !serviceName) {
    return "";
  }

  const subcategoryPattern = new RegExp(`^${escapeRegExp(subcategoryName)}\\s+`, "i");
  const action = serviceName.replace(subcategoryPattern, "").trim();
  return action || serviceName;
}

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

function replaceAmpersand(value) {
  return String(value || "").replaceAll("&", "and").replace(/\s+/g, " ").trim();
}

function slugify(value) {
  if (!value) {
    return "";
  }

  return String(value)
    .normalize("NFKD")
    .replace(/[^\w\s-]/g, "")
    .trim()
    .toLowerCase()
    .replace(/[\s_-]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

function uniqueStrings(values) {
  const seen = new Set();
  const result = [];

  for (const value of values) {
    const normalized = String(value || "")
      .replace(/\s+/g, " ")
      .trim();

    if (!normalized) {
      continue;
    }

    const dedupeKey = normalized.toLowerCase();
    if (seen.has(dedupeKey)) {
      continue;
    }

    seen.add(dedupeKey);
    result.push(normalized);
  }

  return result;
}

function compactLines(lines) {
  return lines.filter(Boolean).join("\n");
}

function safeText(value, maxLength) {
  const text = String(value || "").trim();
  if (text.length <= maxLength) {
    return text;
  }
  return `${text.slice(0, Math.max(0, maxLength - 3)).trim()}...`;
}

function sanitizeMetadata(metadata) {
  const cleaned = {};

  for (const [key, value] of Object.entries(metadata)) {
    if (value == null) {
      continue;
    }

    if (typeof value === "string") {
      const trimmed = value.trim();
      if (trimmed.length > 0) {
        cleaned[key] = trimmed;
      }
      continue;
    }

    if (typeof value === "number" || typeof value === "boolean") {
      cleaned[key] = value;
    }
  }

  return cleaned;
}

function countByRecordType(records) {
  return records.reduce(
    (acc, record) => {
      acc.total += 1;
      if (record.recordType === "category") acc.categories += 1;
      if (record.recordType === "subcategory") acc.subcategories += 1;
      if (record.recordType === "service") acc.services += 1;
      return acc;
    },
    {
      total: 0,
      categories: 0,
      subcategories: 0,
      services: 0,
    }
  );
}

function previewRecord(record) {
  return {
    id: record.id,
    recordType: record.recordType,
    textPreview: safeText(record.text, 300),
    metadataKeys: Object.keys(record.metadata),
  };
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function logSection(message) {
  console.log(`\n[${SCRIPT_NAME}] ${message}`);
}

function logInfo(message, details = null) {
  if (details && Object.keys(details).length > 0) {
    console.log(`[${SCRIPT_NAME}] ${message}`, details);
    return;
  }
  console.log(`[${SCRIPT_NAME}] ${message}`);
}

function logWarn(message, details = null) {
  if (details && Object.keys(details).length > 0) {
    console.warn(`[${SCRIPT_NAME}] ${message}`, details);
    return;
  }
  console.warn(`[${SCRIPT_NAME}] ${message}`);
}

function logError(message, details = null) {
  if (details && Object.keys(details).length > 0) {
    console.error(`[${SCRIPT_NAME}] ${message}`, details);
    return;
  }
  console.error(`[${SCRIPT_NAME}] ${message}`);
}
