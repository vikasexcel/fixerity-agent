import OpenAI from "openai";
import { prisma } from "../lib/prisma.js";

const LOG_PREFIX = "[BuyerMatch]";

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

  const headline = sections["Headline"] ?? null;
  const bio = sections["Bio"] ?? null;
  const servicesDescription = sections["Services Description"] ?? null;
  const uniqueValue = sections["What Makes Me Unique"] ?? null;

  const result = {
    headline,
    bio,
    servicesDescription,
    uniqueValue,
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

  return result;
}

function formatPromptValue(value) {
  if (value == null) return "Not provided";
  const text = String(value).replace(/\s+/g, " ").trim();
  return text || "Not provided";
}

function buildJobDetailsForPrompt(job) {
  return [
    `Job Title - ${formatPromptValue(job.jobTitle)}`,
    `Service Category Needed - ${formatPromptValue(job.serviceCategory)}`,
    `Specific Service Type - ${formatPromptValue(job.serviceType)}`,
    `Project Overview - ${formatPromptValue(job.projectOverview)}`,
    `Issues/Scope - ${formatPromptValue(job.issuesScope)}`,
    `Project Urgency - ${formatPromptValue(job.urgency)}`,
    `Project Scope/Complexity - ${formatPromptValue(job.complexity)}`,
    `Location - ${formatPromptValue(job.location)}`,
    `Budget Range - ${formatPromptValue(job.budgetRange)}`,
    `Timeline/Schedule - ${formatPromptValue(job.timeline)}`,
    `Availability for Consultation/Work - ${formatPromptValue(job.availability)}`,
    `Photos/Documentation - ${formatPromptValue(job.photosDocumentation)}`,
    `Special Requirements - ${formatPromptValue(job.specialRequirements)}`,
    `Licensing/Credentials Required - ${formatPromptValue(job.licensingRequired)}`,
    `Decision Timeline - ${formatPromptValue(job.decisionTimeline)}`,
    `References/Reviews Important? - ${formatPromptValue(job.referencesImportant)}`,
    `Additional Comments - ${formatPromptValue(job.additionalComments)}`,
    `Full Job Post Text - ${formatPromptValue(job.rawText)}`,
  ].join("\n");
}

function buildSellerDetailsForPrompt(seller, index) {
  return [
    `Seller ${index + 1}`,
    `profileIndex - ${index}`,
    `profileId - ${formatPromptValue(seller.profileId)}`,
    `Headline - ${formatPromptValue(seller.structured.headline)}`,
    `Service Type - ${formatPromptValue(seller.structured.serviceType)}`,
    `Business Structure - ${formatPromptValue(seller.structured.businessStructure)}`,
    `Licensing & Insurance - ${formatPromptValue(seller.structured.licensingInsurance)}`,
    `Experience - ${formatPromptValue(seller.structured.experience)}`,
    `Availability - ${formatPromptValue(seller.structured.availability)}`,
    `Service Area - ${formatPromptValue(seller.structured.serviceArea)}`,
    `Pricing - ${formatPromptValue(seller.structured.pricing)}`,
    `Payment Methods - ${formatPromptValue(seller.structured.paymentMethods)}`,
    `References - ${formatPromptValue(seller.structured.references)}`,
    `Specializations - ${formatPromptValue(seller.structured.specializations)}`,
    `Minimum Job Size - ${formatPromptValue(seller.structured.minimumJobSize)}`,
    `Materials & Equipment - ${formatPromptValue(seller.structured.materialsEquipment)}`,
    `Warranty/Guarantee - ${formatPromptValue(seller.structured.warrantyGuarantee)}`,
    `Portfolio - ${formatPromptValue(seller.structured.portfolio)}`,
    `Reviews - ${formatPromptValue(seller.structured.reviews)}`,
    `Languages - ${formatPromptValue(seller.structured.languages)}`,
    `Additional Info - ${formatPromptValue(seller.structured.additionalInfo)}`,
    `Full Seller Profile Text - ${formatPromptValue((seller.rawText || "").slice(0, 2000))}`,
  ].join("\n");
}

async function scoreSellersForJob(jobPostRaw) {
  const structuredJob = buildStructuredJob(jobPostRaw);
  logStep("Step 2/7", "Build job scoring payload", {
    title: structuredJob.jobTitle,
    serviceType: structuredJob.serviceType,
    location: structuredJob.location,
  });

  logStep("Step 3/7", "Load seller profiles");
  const sellerRows = await prisma.sellerProfile.findMany();
  logStep("Step 3/7", "Loaded seller profiles", {
    count: sellerRows.length,
  });

  if (!sellerRows.length) {
    return {
      structuredJob,
      matchedSellers: [],
    };
  }

  const sellersForScoring = sellerRows.map((row) => {
    const structured = buildStructuredSellerProfile(row.sellerProfile || "");
    return {
      profileId: row.id,
      threadId: row.threadId,
      structured,
      rawText: structured.rawText,
    };
  });

  logStep("Step 4/7", "Build seller scoring payload", {
    sellers: sellersForScoring.length,
  });

  const openai = getOpenAIClient();
  const model = process.env.OPENAI_MODEL || "gpt-5.4";
  const reasoningEffort = process.env.OPENAI_MATCHING_REASONING_EFFORT || "medium";
  const responseVerbosity = process.env.OPENAI_MATCHING_VERBOSITY || "low";

  const scoringInput = {
    job: structuredJob,
    sellers: sellersForScoring.map((s, index) => ({
      index,
      profileId: s.profileId,
      headline: s.structured.headline,
      serviceType: s.structured.serviceType,
      location: s.structured.serviceArea,
      experience: s.structured.experience,
      pricing: s.structured.pricing,
      languages: s.structured.languages,
      additionalInfo: s.structured.additionalInfo,
      rawProfilePreview: (s.rawText || "").slice(0, 1200),
    })),
  };

  const formattedJobDetails = buildJobDetailsForPrompt(structuredJob);
  const formattedSellerDetails = sellersForScoring
    .map((seller, index) => buildSellerDetailsForPrompt(seller, index))
    .join("\n\n");

  const systemPrompt = `
You are a deterministic seller-matching analyst for a marketplace.
Your job is to evaluate how well each seller profile matches the buyer's job request using only the information provided.

Data sources:
- Each job includes both structured fields (like "serviceType", "location", etc.) and a full free-text "rawText" description created by the buyer.
- Each seller includes both structured fields (like "serviceType", "serviceArea", etc.) and a free-text "rawProfilePreview" description.

Process requirements:
- Reason carefully and consistently before scoring, but return only the final JSON array.
- When structured fields are present and informative, use them as primary signals.
- When structured fields are missing, vague, or null, you MUST rely heavily on the natural-language descriptions in "job.rawText" and "rawProfilePreview" to infer service type, location fit, and capabilities.
- Always consider obvious semantic matches in the free text (e.g., both clearly about dog walking in the same city) even if the structured fields are sparse or empty, and score those as strong matches when appropriate.
- Do NOT ignore free-text descriptions; they are often the most accurate source of truth about what the buyer needs and what the seller offers.
- Do not invent certifications, experience, service areas, pricing, or capabilities that are not explicitly present in either the structured fields or the free text.
- If important information is missing in both structured and free-text data, reduce confidence instead of filling the gap with assumptions.
- Evaluate every seller independently against the same rubric.
- Read the buyer job fields exactly as provided in the field-by-field format.
- Then compare that job against each seller profile one by one before producing the final JSON array.

Primary rubric, in order:
1. Core service fit: does the seller clearly provide the requested service type or category?
2. Geographic fit: is the seller's service area compatible with the job location?
3. Capability fit: relevant experience, specialization, tools, licensing, insurance, reviews, references, or portfolio strength.
4. Delivery fit: urgency, timeline, availability, communication, language, and logistical constraints.
5. Commercial fit: pricing, minimum job size, and other practical constraints when relevant.

Score calibration:
- 0-10: clear mismatch; wrong category or seller obviously cannot do the work.
- 11-30: very weak fit; only loose adjacency or major missing requirements.
- 31-50: partial fit; some relevant signals, but notable gaps or uncertainty.
- 51-70: solid fit; broadly relevant with a few limitations.
- 71-85: strong fit; good alignment across the main criteria.
- 86-100: exceptional fit; highly aligned service, geography, credibility, and practical fit.

Output requirements:
- Return one object for every seller in the provided array.
- Preserve the seller's "profileIndex" exactly as provided.
- "matchScore" must be an integer from 0 to 100.
- "matchExplanation" must be 2-4 concise sentences grounded in the given fields.
- Mention the strongest positive signal and the biggest limitation or uncertainty when relevant.
- If there is a category mismatch, say that plainly and score near zero.
- Return only a JSON array with no markdown and no extra commentary.
`;

  const userPrompt = `
JOB DETAILS:
${formattedJobDetails}

SELLER PROFILES:
${formattedSellerDetails}

Reference JSON payload:
${JSON.stringify(scoringInput, null, 2)}

Return ONLY a JSON array of:
[
  {
    "profileIndex": number,
    "matchScore": number,
    "matchExplanation": string
  },
  ...
]
`;

  let scores = [];
  try {
    const completion = await openai.responses.create({
      model,
      reasoning: {
        effort: reasoningEffort,
      },
      text: {
        verbosity: responseVerbosity,
      },
      input: [
        { role: "system", content: systemPrompt.trim() },
        { role: "user", content: userPrompt.trim() },
      ],
    });

    const content = (completion.output_text || "").trim();
    const jsonMatch = content.match(/\[[\s\S]*\]/);
    scores = jsonMatch ? JSON.parse(jsonMatch[0]) : [];
  } catch (err) {
    logError("Step 5/7", "LLM scoring failed, falling back to zero scores", {
      error: err instanceof Error ? err.message : String(err),
    });
    scores = [];
  }

  const byIndex = new Map();
  if (Array.isArray(scores)) {
    for (const entry of scores) {
      const idx = Number(entry.profileIndex);
      if (Number.isNaN(idx)) continue;
      byIndex.set(idx, entry);
    }
  }

  const matchedSellers = sellersForScoring.map((seller, index) => {
    const scored = byIndex.get(index) || {};
    const scoreRaw = Number(scored.matchScore);
    const matchScore =
      Number.isFinite(scoreRaw) && scoreRaw >= 0 && scoreRaw <= 100
        ? Math.round(scoreRaw)
        : 0;

    const structured = seller.structured;
    const title =
      structured.headline ||
      (structured.serviceType ? String(structured.serviceType) : "Seller");

    const metadata = {
      location: structured.serviceArea || null,
      rate: structured.pricing || null,
    };

    return {
      profileId: seller.profileId,
      sellerName: String(title).replace(/^#+\s*/, "").replace(/\*\*/g, "").trim() || "Seller",
      profileText: structured.rawText || "",
      matchScore,
      matchExplanation:
        (scored.matchExplanation &&
          String(scored.matchExplanation).slice(0, 500)) ||
        "Match analysis not available.",
      metadata,
    };
  });

  logStep("Step 5/7", "Score result", {
    sellers: matchedSellers.length,
  });

  const sorted = matchedSellers
    .slice()
    .sort((a, b) => (b.matchScore ?? 0) - (a.matchScore ?? 0));

  logStep("Step 6/7", "Sort rankings", {
    topPreview: sorted.slice(0, 3).map((s) => ({
      profileId: s.profileId,
      sellerName: s.sellerName,
      matchScore: s.matchScore,
    })),
  });

  return {
    structuredJob,
    matchedSellers: sorted,
  };
}

export {
  cleanJobPostText,
  cleanSellerProfileText,
  buildStructuredJob,
  buildStructuredSellerProfile,
  scoreSellersForJob,
};

