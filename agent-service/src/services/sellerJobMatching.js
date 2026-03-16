import OpenAI from "openai";
import { prisma } from "../lib/prisma.js";

const LOG_PREFIX = "[SellerJobMatch]";

function logStep(step, message, data = {}) {
  console.log(`${LOG_PREFIX} ${step}`, message, Object.keys(data).length ? data : "");
}

function logError(step, message, data = {}) {
  console.error(`${LOG_PREFIX} ${step} ERROR`, message, Object.keys(data).length ? data : "");
}

function getOpenAIClient() {
  if (!process.env.OPENAI_API_KEY) {
    throw new Error("OPENAI_API_KEY is not set");
  }
  return new OpenAI({ apiKey: process.env.OPENAI_API_KEY });
}

function cleanJobPostText(raw) {
  if (!raw || typeof raw !== "string") return "";
  let text = raw.trim();
  const reviewSuffix =
    "Please review this job post and let me know if you would like any changes or if it looks good to go!";
  if (text.includes(reviewSuffix)) {
    text = text.replace(reviewSuffix, "").trim();
  }
  return text;
}

function cleanSellerProfileText(raw) {
  if (!raw || typeof raw !== "string") return "";
  let text = raw.trim();
  const updateSuffix = "If you need any updates or changes, let me know!";
  if (text.includes(updateSuffix)) {
    text = text.replace(updateSuffix, "").trim();
  }
  return text;
}

function parseLabeledMarkdownSections(text) {
  const cleaned = text.replace(/\r\n/g, "\n");
  const result = {};
  const regex = /\*\*([^*]+?):\*\*\s*([^*]+?)(?=(\s*\*\*[^*]+:\*\*|$))/gs;
  let match;
  while ((match = regex.exec(cleaned)) !== null) {
    const label = match[1].trim();
    const value = match[2].trim();
    if (!label) continue;
    result[label] = value;
  }
  return result;
}

function buildStructuredJob(jobPostRaw) {
  const rawText = cleanJobPostText(jobPostRaw);
  const sections = parseLabeledMarkdownSections(rawText);

  return {
    jobTitle: sections["Job Title"] ?? null,
    serviceCategory: sections["Service Category Needed"] ?? null,
    serviceType: sections["Specific Service Type"] ?? null,
    projectOverview: sections["Project Overview"] ?? null,
    issuesScope: sections["Issues/Scope"] ?? null,
    urgency: sections["Project Urgency"] ?? null,
    complexity: sections["Project Scope/Complexity"] ?? null,
    location: sections["Location"] ?? null,
    budgetRange: sections["Budget Range"] ?? null,
    timeline: sections["Timeline/Schedule"] ?? null,
    availability: sections["Availability for Consultation/Work"] ?? null,
    photosDocumentation: sections["Photos/Documentation"] ?? null,
    specialRequirements: sections["Special Requirements"] ?? null,
    licensingRequired: sections["Licensing/Credentials Required"] ?? null,
    decisionTimeline: sections["Decision Timeline"] ?? null,
    referencesImportant: sections["References/Reviews Important?"] ?? null,
    additionalComments: sections["Additional Comments"] ?? null,
    rawText,
  };
}

function buildStructuredSellerProfile(sellerProfileRaw) {
  const rawText = cleanSellerProfileText(sellerProfileRaw);
  const sections = parseLabeledMarkdownSections(rawText);

  return {
    headline: sections["Headline"] ?? null,
    bio: sections["Bio"] ?? null,
    servicesDescription: sections["Services Description"] ?? null,
    uniqueValue: sections["What Makes Me Unique"] ?? null,
    serviceType: sections["Service Type"] ?? null,
    businessStructure: sections["Business Structure"] ?? null,
    licensingInsurance: sections["Licensing & Insurance"] ?? null,
    experience: sections["Experience"] ?? null,
    availability: sections["Availability"] ?? null,
    serviceArea: sections["Service Area"] ?? null,
    pricing: sections["Pricing"] ?? null,
    paymentMethods: sections["Payment Methods"] ?? null,
    references: sections["References"] ?? null,
    specializations: sections["Specializations"] ?? null,
    minimumJobSize: sections["Minimum Job Size"] ?? null,
    materialsEquipment: sections["Materials & Equipment"] ?? null,
    warrantyGuarantee: sections["Warranty/Guarantee"] ?? null,
    portfolio: sections["Portfolio"] ?? null,
    reviews: sections["Reviews"] ?? null,
    languages: sections["Languages"] ?? null,
    additionalInfo: sections["Additional Info"] ?? null,
    rawText,
  };
}

function formatPromptValue(value) {
  if (value == null) return "Not provided";
  const text = String(value).replace(/\s+/g, " ").trim();
  return text || "Not provided";
}

function buildSellerDetailsForPrompt(seller) {
  const s = seller.structured;
  return [
    `Headline - ${formatPromptValue(s.headline)}`,
    `Bio - ${formatPromptValue(s.bio)}`,
    `Services Description - ${formatPromptValue(s.servicesDescription)}`,
    `What Makes Me Unique - ${formatPromptValue(s.uniqueValue)}`,
    `Service Type - ${formatPromptValue(s.serviceType)}`,
    `Business Structure - ${formatPromptValue(s.businessStructure)}`,
    `Licensing & Insurance - ${formatPromptValue(s.licensingInsurance)}`,
    `Experience - ${formatPromptValue(s.experience)}`,
    `Availability - ${formatPromptValue(s.availability)}`,
    `Service Area - ${formatPromptValue(s.serviceArea)}`,
    `Pricing - ${formatPromptValue(s.pricing)}`,
    `Payment Methods - ${formatPromptValue(s.paymentMethods)}`,
    `References - ${formatPromptValue(s.references)}`,
    `Specializations - ${formatPromptValue(s.specializations)}`,
    `Minimum Job Size - ${formatPromptValue(s.minimumJobSize)}`,
    `Materials & Equipment - ${formatPromptValue(s.materialsEquipment)}`,
    `Warranty/Guarantee - ${formatPromptValue(s.warrantyGuarantee)}`,
    `Portfolio - ${formatPromptValue(s.portfolio)}`,
    `Reviews - ${formatPromptValue(s.reviews)}`,
    `Languages - ${formatPromptValue(s.languages)}`,
    `Additional Info - ${formatPromptValue(s.additionalInfo)}`,
    `Full Seller Profile Text - ${formatPromptValue((seller.rawText || "").slice(0, 2000))}`,
  ].join("\n");
}

function buildJobDetailsForPrompt(job, index) {
  return [
    `Job ${index + 1}`,
    `jobIndex - ${index}`,
    `jobId - ${formatPromptValue(job.jobId)}`,
    `Job Title - ${formatPromptValue(job.structured.jobTitle)}`,
    `Service Category Needed - ${formatPromptValue(job.structured.serviceCategory)}`,
    `Specific Service Type - ${formatPromptValue(job.structured.serviceType)}`,
    `Project Overview - ${formatPromptValue(job.structured.projectOverview)}`,
    `Issues/Scope - ${formatPromptValue(job.structured.issuesScope)}`,
    `Project Urgency - ${formatPromptValue(job.structured.urgency)}`,
    `Project Scope/Complexity - ${formatPromptValue(job.structured.complexity)}`,
    `Location - ${formatPromptValue(job.structured.location)}`,
    `Budget Range - ${formatPromptValue(job.structured.budgetRange)}`,
    `Timeline/Schedule - ${formatPromptValue(job.structured.timeline)}`,
    `Availability for Consultation/Work - ${formatPromptValue(job.structured.availability)}`,
    `Special Requirements - ${formatPromptValue(job.structured.specialRequirements)}`,
    `Licensing/Credentials Required - ${formatPromptValue(job.structured.licensingRequired)}`,
    `Additional Comments - ${formatPromptValue(job.structured.additionalComments)}`,
    `Full Job Post Text - ${formatPromptValue((job.structured.rawText || "").slice(0, 1500))}`,
  ].join("\n");
}

/**
 * Load all jobs from the database, compare the seller profile against each job
 * one by one using an LLM, and return sorted matched jobs for the frontend.
 *
 * @param {string} sellerProfileRaw - The confirmed seller profile markdown text
 * @returns {{ matchedJobs: Array<{ jobId, jobTitle, jobText, matchScore, matchExplanation, metadata }>, jobMatchingStatus: 'found' | 'error' }}
 */
async function scoreJobsForSeller(sellerProfileRaw) {
  const structuredSeller = buildStructuredSellerProfile(sellerProfileRaw);
  logStep("Step 1", "Build seller scoring payload", {
    headline: structuredSeller.headline,
    serviceType: structuredSeller.serviceType,
    serviceArea: structuredSeller.serviceArea,
  });

  logStep("Step 2", "Load all jobs");
  const jobRows = await prisma.job.findMany();
  logStep("Step 2", "Loaded jobs", { count: jobRows.length });

  if (!jobRows.length) {
    return { matchedJobs: [], jobMatchingStatus: "found" };
  }

  const jobsForScoring = jobRows.map((row) => ({
    jobId: row.id,
    structured: buildStructuredJob(row.jobPost || ""),
    rawText: (row.jobPost || "").trim(),
  }));

  logStep("Step 3", "Build job scoring payload", { jobs: jobsForScoring.length });

  const sellerForPrompt = {
    structured: structuredSeller,
    rawText: structuredSeller.rawText,
  };

  const formattedSellerDetails = buildSellerDetailsForPrompt(sellerForPrompt);
  const formattedJobDetails = jobsForScoring
    .map((job, index) => buildJobDetailsForPrompt(job, index))
    .join("\n\n");

  const scoringInput = jobsForScoring.map((j, index) => ({
    index,
    jobId: j.jobId,
    jobTitle: j.structured.jobTitle,
    serviceType: j.structured.serviceType,
    location: j.structured.location,
    budgetRange: j.structured.budgetRange,
    rawJobPreview: (j.structured.rawText || "").slice(0, 1200),
  }));

  const systemPrompt = `
You are a deterministic job-matching analyst for a marketplace.
Your job is to evaluate how well each job matches the seller's profile using only the information provided.

Data sources:
- The seller profile includes both structured fields (like "serviceType", "serviceArea", etc.) and a full free-text description.
- Each job includes both structured fields (like "serviceType", "location", etc.) and a full free-text "rawText" description created by the buyer.

Process requirements:
- Reason carefully and consistently before scoring, but return only the final JSON array.
- When structured fields are present and informative, use them as primary signals.
- When structured fields are missing, vague, or null, you MUST rely heavily on the natural-language descriptions to infer service type, location fit, and capabilities.
- Always consider obvious semantic matches in the free text even if the structured fields are sparse or empty, and score those as strong matches when appropriate.
- Do NOT ignore free-text descriptions; they are often the most accurate source of truth.
- Do not invent capabilities, service areas, or requirements that are not explicitly present.
- If important information is missing in both structured and free-text data, reduce confidence instead of filling the gap with assumptions.
- Evaluate every job independently against the same rubric.
- Read the seller profile and each job's fields exactly as provided.
- Compare the seller profile against each job one by one before producing the final JSON array.

Primary rubric, in order:
1. Core service fit: does the seller clearly provide the service type or category the job needs?
2. Geographic fit: is the seller's service area compatible with the job location?
3. Capability fit: does the seller's experience, specialization, licensing, and credentials align with job requirements?
4. Delivery fit: urgency, timeline, availability, and logistical constraints.
5. Commercial fit: pricing, minimum job size, and budget when relevant.

Score calibration:
- 0-10: clear mismatch; wrong category or seller obviously cannot do the work.
- 11-30: very weak fit; only loose adjacency or major missing requirements.
- 31-50: partial fit; some relevant signals, but notable gaps or uncertainty.
- 51-70: solid fit; broadly relevant with a few limitations.
- 71-85: strong fit; good alignment across the main criteria.
- 86-100: exceptional fit; highly aligned service, geography, credibility, and practical fit.

Output requirements:
- Return one object for every job in the provided array.
- Preserve the job's "jobIndex" exactly as provided.
- "matchScore" must be an integer from 0 to 100.
- "matchExplanation" must be 2-4 concise sentences grounded in the given fields.
- Mention the strongest positive signal and the biggest limitation or uncertainty when relevant.
- If there is a category mismatch, say that plainly and score near zero.
- Return only a JSON array with no markdown and no extra commentary.
`;

  const userPrompt = `
SELLER PROFILE:
${formattedSellerDetails}

JOBS:
${formattedJobDetails}

Reference JSON payload:
${JSON.stringify(scoringInput, null, 2)}

Return ONLY a JSON array of:
[
  {
    "jobIndex": number,
    "matchScore": number,
    "matchExplanation": string
  },
  ...
]
`;

  let scores = [];
  try {
    const openai = getOpenAIClient();
    const model = process.env.OPENAI_MODEL || "gpt-4o";

    const completion = await openai.chat.completions.create({
      model,
      messages: [
        { role: "system", content: systemPrompt.trim() },
        { role: "user", content: userPrompt.trim() },
      ],
    });

    const content = (completion.choices?.[0]?.message?.content || "").trim();
    const jsonMatch = content.match(/\[[\s\S]*\]/);
    scores = jsonMatch ? JSON.parse(jsonMatch[0]) : [];
  } catch (err) {
    logError("Step 4", "LLM scoring failed", {
      error: err instanceof Error ? err.message : String(err),
    });
    return { matchedJobs: [], jobMatchingStatus: "error" };
  }

  const byIndex = new Map();
  if (Array.isArray(scores)) {
    for (const entry of scores) {
      const idx = Number(entry.jobIndex);
      if (Number.isNaN(idx)) continue;
      byIndex.set(idx, entry);
    }
  }

  const matchedJobs = jobsForScoring.map((job, index) => {
    const scored = byIndex.get(index) || {};
    const scoreRaw = Number(scored.matchScore);
    const matchScore =
      Number.isFinite(scoreRaw) && scoreRaw >= 0 && scoreRaw <= 100
        ? Math.round(scoreRaw)
        : 0;

    const title =
      job.structured.jobTitle ||
      (job.structured.serviceType ? String(job.structured.serviceType) : "Job");

    const metadata = {
      location: job.structured.location || null,
      budget: job.structured.budgetRange || null,
      budgetRange: job.structured.budgetRange || null,
      timeline: job.structured.timeline || null,
    };

    return {
      jobId: job.jobId,
      jobTitle: String(title).replace(/^#+\s*/, "").replace(/\*\*/g, "").trim() || "Job",
      jobText: job.structured.rawText || job.rawText || "",
      matchScore,
      matchExplanation:
        (scored.matchExplanation &&
          String(scored.matchExplanation).slice(0, 500)) ||
        "Match analysis not available.",
      metadata,
    };
  });

  const sorted = matchedJobs
    .slice()
    .sort((a, b) => (b.matchScore ?? 0) - (a.matchScore ?? 0));

  logStep("Step 5", "Sort rankings", {
    topPreview: sorted.slice(0, 3).map((j) => ({
      jobId: j.jobId,
      jobTitle: j.jobTitle,
      matchScore: j.matchScore,
    })),
  });

  return { matchedJobs: sorted, jobMatchingStatus: "found" };
}

export { scoreJobsForSeller };
