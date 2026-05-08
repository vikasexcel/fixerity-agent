import { prisma } from "./prisma.js";

/**
 * Create a buyer job record with category hierarchy.
 *
 * @param {object} params
 * @param {string} params.threadId
 * @param {string} params.jobTitle
 * @param {string} params.jobDescription
 * @param {string} params.categorySearchQuery
 * @param {string|null} params.categoryId
 * @param {string|null} params.categoryName
 * @param {string|null} params.subcategoryId
 * @param {string|null} params.subcategoryName
 * @param {string|null} params.serviceId
 * @param {string|null} params.serviceName
 * @param {string} [params.status='published']
 * @param {string|null} [params.pineconeJobId]
 * @returns {Promise<object>}
 */
export async function createBuyerJob({
  threadId,
  jobTitle,
  jobDescription,
  categorySearchQuery,
  categoryId,
  categoryName,
  subcategoryId,
  subcategoryName,
  serviceId,
  serviceName,
  status = "published",
  pineconeJobId = null,
}) {
  return prisma.buyerJob.create({
    data: {
      threadId,
      jobTitle,
      jobDescription,
      categorySearchQuery: categorySearchQuery || null,
      categoryId: categoryId ?? null,
      categoryName: categoryName ?? null,
      subcategoryId: subcategoryId ?? null,
      subcategoryName: subcategoryName ?? null,
      serviceId: serviceId ?? null,
      serviceName: serviceName ?? null,
      status,
      pineconeJobId,
    },
  });
}

/**
 * Get buyer job Postgres IDs by Pinecone job IDs.
 *
 * @param {string[]} pineconeJobIds
 * @returns {Promise<Map<string, string>>} Map of pineconeJobId -> buyerJob.id
 */
export async function getBuyerJobIdsByPineconeJobIds(pineconeJobIds) {
  if (!pineconeJobIds || pineconeJobIds.length === 0) {
    return new Map();
  }
  const jobs = await prisma.buyerJob.findMany({
    where: { pineconeJobId: { in: pineconeJobIds } },
    select: { id: true, pineconeJobId: true },
  });
  const map = new Map();
  for (const j of jobs) {
    if (j.pineconeJobId) map.set(j.pineconeJobId, j.id);
  }
  return map;
}

/**
 * Update a buyer job with Pinecone embedding ID.
 *
 * @param {string} jobId
 * @param {string} pineconeJobId
 * @returns {Promise<object>}
 */
export async function updateBuyerJobPineconeId(jobId, pineconeJobId) {
  return prisma.buyerJob.update({
    where: { id: jobId },
    data: { pineconeJobId },
  });
}

/**
 * Update job status (e.g. on error).
 *
 * @param {string} jobId
 * @param {string} status
 * @returns {Promise<object>}
 */
export async function updateBuyerJobStatus(jobId, status) {
  return prisma.buyerJob.update({
    where: { id: jobId },
    data: { status },
  });
}

/**
 * Create seller match records for a buyer job.
 *
 * @param {string} buyerJobId
 * @param {Array<{ profileId: string, vectorRank: number, llmScore: number, matchExplanation: string, finalRank: number }>} matches
 * @returns {Promise<object>}
 */
export async function createSellerMatches(buyerJobId, matches) {
  if (!matches || matches.length === 0) {
    return [];
  }

  return prisma.sellerMatch.createMany({
    data: matches.map((m) => ({
      buyerJobId,
      profileId: m.profileId,
      vectorRank: m.vectorRank,
      llmScore: m.llmScore,
      matchExplanation: m.matchExplanation,
      finalRank: m.finalRank,
    })),
  });
}

/**
 * Create a seller profile record with category hierarchy.
 *
 * @param {object} params
 * @param {string} params.threadId
 * @param {string} params.profileDescription
 * @param {string} [params.status='published']
 * @param {string|null} [params.pineconeProfileId]
 * @param {string|null} params.categorySearchQuery
 * @param {string|null} params.categoryId
 * @param {string|null} params.categoryName
 * @param {string|null} params.subcategoryId
 * @param {string|null} params.subcategoryName
 * @param {string|null} params.serviceId
 * @param {string|null} params.serviceName
 * @returns {Promise<object>}
 */
export async function createSellerProfile({
  threadId,
  profileDescription,
  status = "published",
  pineconeProfileId = null,
  categorySearchQuery,
  categoryId,
  categoryName,
  subcategoryId,
  subcategoryName,
  serviceId,
  serviceName,
}) {
  return prisma.sellerProfile.create({
    data: {
      threadId,
      profileDescription,
      status,
      pineconeProfileId,
      categorySearchQuery: categorySearchQuery || null,
      categoryId: categoryId ?? null,
      categoryName: categoryName ?? null,
      subcategoryId: subcategoryId ?? null,
      subcategoryName: subcategoryName ?? null,
      serviceId: serviceId ?? null,
      serviceName: serviceName ?? null,
    },
  });
}

/**
 * Update a seller profile with Pinecone embedding ID.
 *
 * @param {string} sellerProfileId
 * @param {string} pineconeProfileId
 * @returns {Promise<object>}
 */
export async function updateSellerProfilePineconeId(sellerProfileId, pineconeProfileId) {
  return prisma.sellerProfile.update({
    where: { id: sellerProfileId },
    data: { pineconeProfileId },
  });
}

/**
 * Update seller profile status (e.g. on error).
 *
 * @param {string} sellerProfileId
 * @param {string} status
 * @returns {Promise<object>}
 */
export async function updateSellerProfileStatus(sellerProfileId, status) {
  return prisma.sellerProfile.update({
    where: { id: sellerProfileId },
    data: { status },
  });
}

/**
 * Create job match records for a seller profile.
 *
 * @param {string} sellerProfileId
 * @param {Array<{ buyerJobId: string, vectorRank: number, llmScore: number, matchExplanation: string, finalRank: number }>} matches
 * @returns {Promise<object>}
 */
export async function createJobMatches(sellerProfileId, matches) {
  if (!matches || matches.length === 0) {
    return { count: 0 };
  }

  return prisma.jobMatch.createMany({
    data: matches.map((m) => ({
      sellerProfileId,
      buyerJobId: m.buyerJobId,
      vectorRank: m.vectorRank,
      llmScore: m.llmScore,
      matchExplanation: m.matchExplanation,
      finalRank: m.finalRank,
    })),
  });
}
