/**
 * Customer Service Request Profile Questions.
 * exactQuestion: used for extraction/classification.
 * shortPrompt: natural, concise way to ask the buyer (no long lists).
 * Answers/skips are tracked in state.profileAnswers.
 */

export const PROFILE_QUESTIONS = [
  {
    id: "serviceCategory",
    label: "Service Category Needed",
    shortPrompt: "What type of service is this — home repair, professional, healthcare, legal?",
    exactQuestion:
      "What type of service do you need? For example: Home Repair/Maintenance (plumbing, electrical, HVAC, roofing, etc.), Construction/Renovation, Legal Services (specify type: divorce, real estate, corporate, etc.), Medical/Healthcare Services (specify: plastic surgery, dental, therapy, etc.), Professional Services (accounting, consulting, etc.), Other (please specify).",
  },
  {
    id: "specificServiceType",
    label: "Specific Service Type",
    shortPrompt: "What would you call this specific service in a few words?",
    exactQuestion:
      'Describe the specific service needed. Examples: "Roof leak repair," "Divorce attorney," "Rhinoplasty consultation."',
  },
  {
    id: "urgency",
    label: "Project Urgency",
    shortPrompt: "How urgent is this — emergency, within a week, or flexible?",
    exactQuestion:
      "Emergency (need service within 24 hours), Urgent (within 1 week), Soon (within 2-4 weeks), Flexible (within 1-3 months), Planning ahead (3+ months).",
  },
  {
    id: "scopeComplexity",
    label: "Project Scope/Complexity",
    shortPrompt: "Would you say this is a small job, medium, or large?",
    exactQuestion:
      "Small/Simple (e.g., fix a leaky faucet), Medium (e.g., bathroom remodel, uncontested divorce), Large/Complex (e.g., full roof replacement, major surgery, contested legal case), Not sure (need professional assessment).",
  },
  {
    id: "detailedDescription",
    label: "Detailed Description",
    shortPrompt: "Can you give me more details about what needs to be done?",
    exactQuestion:
      "Please describe what you need done in detail (300-500 words). What is the problem or goal? What has been tried already (if applicable)? Any specific requirements or preferences?",
  },
  {
    id: "location",
    label: "Location",
    shortPrompt: "Where are you located?",
    exactQuestion:
      "Where is the service needed? City, state, ZIP, or whatever location details you can provide. Also: Is the service location important for this job, or can it be done remotely/virtually? (Yes/No for remote acceptable — for applicable services.)",
  },
  {
    id: "budgetRange",
    label: "Budget Range",
    shortPrompt: "What's your budget, or would you rather get estimates?",
    exactQuestion:
      "What is your budget for this project? Under $500, $500-$1,000, $1,000-$5,000, $5,000-$10,000, $10,000-$25,000, $25,000-$50,000, $50,000+, Not sure/Need estimate. Is this budget flexible? (Yes/No/Somewhat). You can skip this one if you prefer to get estimates from providers.",
  },
  {
    id: "timelineSchedule",
    label: "Timeline/Schedule",
    shortPrompt: "When do you need this to start and finish?",
    exactQuestion: "When do you need the work to start? When do you need it completed?",
  },
  {
    id: "availability",
    label: "Availability for Consultation/Work",
    shortPrompt: "What days and times work best for you?",
    exactQuestion:
      "Best days for service (Select all that apply): Weekdays, Weekends, Either. Best time of day: Morning (8am-12pm), Afternoon (12pm-5pm), Evening (5pm-8pm), Flexible.",
  },
  {
    id: "photosDocumentation",
    label: "Photos/Documentation",
    shortPrompt: "Do you have any photos or documents that would help?",
    exactQuestion:
      "Upload up to 10 photos or a short video showing the issue or area (for applicable services). Upload any relevant documents (construction permits, medical records, legal documents, plans, etc.).",
  },
  {
    id: "specialRequirements",
    label: "Special Requirements",
    shortPrompt: "Any special requirements — language, parking, pets, equipment?",
    exactQuestion:
      "Language preference for service provider. Special equipment or materials required? Pets on premises? (Yes/No). Parking available? (Yes/No - for in-person services).",
  },
  {
    id: "licensingCredentials",
    label: "Licensing/Credentials Required",
    shortPrompt: "Do they need to be licensed and insured?",
    exactQuestion:
      "Must be licensed? (Yes/No/Prefer but not required). Must be insured? (Yes/No/Prefer but not required).",
  },
  {
    id: "decisionTimeline",
    label: "Decision Timeline",
    shortPrompt: "How soon are you looking to hire someone?",
    exactQuestion:
      "How soon do you plan to hire someone? Immediately (within 2 days), This week, Within 2 weeks, Within a month, Just researching/comparing options.",
  },
  {
    id: "referencesReviews",
    label: "References/Reviews Important?",
    shortPrompt: "Are reviews or references important to you?",
    exactQuestion: "Must have reviews/references? (Yes/No/Preferred).",
  },
  {
    id: "additionalComments",
    label: "Additional Comments",
    shortPrompt: "Anything else you want providers to know?",
    exactQuestion:
      "Additional Comments (Free form, up to 300 words). Anything else providers should know about your project or requirements?",
  },
];

export const PROFILE_QUESTION_IDS = PROFILE_QUESTIONS.map((q) => q.id);
