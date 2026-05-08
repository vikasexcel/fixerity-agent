/**
 * Standard Service Provider Profile Questions.
 * Based on client-provided document: "Standard Service Provider Profile Questions.docx"
 * 
 * exactQuestion: used for extraction/classification.
 * shortPrompt: natural, concise way to ask the seller (no long lists).
 * Answers/skips are tracked in state.profileAnswers.
 */

export const SELLER_PROFILE_QUESTIONS = [
  {
    id: "serviceType",
    label: "Service Type",
    shortPrompt: "What type of services do you offer?",
    exactQuestion:
      "What type of services do you offer? Specify all that apply: Professional services (architect, engineer, etc.), General Handyman Services, Specialized Trade (Plumbing, Electrical, HVAC, etc.), General Labor, or specify your own.",
  },
  {
    id: "projectArrangement",
    label: "Project Arrangement",
    shortPrompt: "Is this full-time professional work or part-time/side business?",
    exactQuestion:
      "Project Arrangement: Full-time professional or Part-time/Side business?",
  },
  {
    id: "licensing",
    label: "Licensing & Certification",
    shortPrompt: "Are you licensed for this work?",
    exactQuestion: "Are you licensed? (Yes/No). If yes, please specify the license type or number.",
  },
  {
    id: "businessStructure",
    label: "Business Structure",
    shortPrompt: "Are you operating as an individual or a corporation/partnership?",
    exactQuestion:
      "Business Structure: Individual, Corporation, or Partnership?",
  },
  {
    id: "insurance",
    label: "Insurance Coverage",
    shortPrompt: "Do you carry liability insurance?",
    exactQuestion: "Do you carry liability insurance? (Yes/No). If yes, coverage amount?",
  },
  {
    id: "availability",
    label: "Availability",
    shortPrompt: "What's your typical availability — standard hours, evenings, weekends?",
    exactQuestion:
      "Availability: Standard working hours (e.g., Mon-Fri 8am-5pm)? Available for evening work? (Yes/No) Available for weekend work? (Yes/No) Emergency/urgent service availability? (Yes/No)",
  },
  {
    id: "experience",
    label: "Experience",
    shortPrompt: "How many years of experience do you have, and roughly how many projects have you completed?",
    exactQuestion:
      "Experience: Years of professional experience (for individuals) or Years in business (for companies)? Number of completed projects (approximate)?",
  },
  {
    id: "serviceArea",
    label: "Service Area",
    shortPrompt: "What's your primary service area, and how far are you willing to travel?",
    exactQuestion:
      "Service Area: Primary city/metro area served? Willing to travel? Maximum distance? State(s)? Country?",
  },
  {
    id: "pricingStructure",
    label: "Pricing Structure",
    shortPrompt: "How do you charge — per hour, flat rate per job, or something else?",
    exactQuestion:
      "Pricing Structure: Per hour, Flat rate per job, or Other (please specify)? Typical hourly rate or price range?",
  },
  {
    id: "paymentTerms",
    label: "Payment Terms",
    shortPrompt: "Do you require a deposit or retainer upfront?",
    exactQuestion:
      "Payment Terms: Deposit/retainer required? (Yes/No) If yes, percentage or amount?",
  },
  {
    id: "paymentMethods",
    label: "Accepted Payment Methods",
    shortPrompt: "What payment methods do you accept?",
    exactQuestion:
      "Accepted Payment Methods (Select all that apply): Cash, Check, Credit/Debit Card, PayPal, Venmo, Zelle, Wire Transfer, Financing available, Other (specify)?",
  },
  {
    id: "references",
    label: "Background & References",
    shortPrompt: "Are you willing to provide references?",
    exactQuestion: "Willing to provide references? (Yes/No) If yes, how many?",
  },
  {
    id: "specializations",
    label: "Specializations/Certifications",
    shortPrompt: "Do you have any special certifications, training, or areas of expertise?",
    exactQuestion:
      "List any special certifications, training, or areas of expertise you have.",
  },
  {
    id: "minimumJobSize",
    label: "Minimum Job Size",
    shortPrompt: "Do you have a minimum project size or fee?",
    exactQuestion:
      "Do you have a minimum project size or fee? If yes, what is it?",
  },
  {
    id: "materialsEquipment",
    label: "Materials & Equipment",
    shortPrompt: "Do you provide your own tools and materials, or does the client provide them?",
    exactQuestion:
      "Do you provide your own tools and equipment? (Yes/No) Do you source materials, or does client provide them? (Both/Either/Negotiable – can provide materials)",
  },
  {
    id: "warranty",
    label: "Warranty/Guarantee",
    shortPrompt: "Do you offer a warranty or guarantee on your work?",
    exactQuestion:
      "Do you offer a warranty or guarantee on your work? (Yes/No) If yes, duration?",
  },
  {
    id: "portfolio",
    label: "Portfolio/Photos",
    shortPrompt: "Do you have photos of completed work you can share?",
    exactQuestion:
      "Upload any photos of completed work. Do you have a portfolio to share?",
  },
  {
    id: "reviews",
    label: "Reviews & Ratings",
    shortPrompt: "Do you have any online reviews or ratings you'd like to share?",
    exactQuestion:
      "Link to external reviews (Google, Yelp, Angie's List, etc.) if any.",
  },
  {
    id: "languages",
    label: "Languages Spoken",
    shortPrompt: "What languages can you communicate in?",
    exactQuestion: "List all languages you can communicate in.",
  },
  {
    id: "additionalInfo",
    label: "Additional Information",
    shortPrompt: "Anything else you'd like potential clients to know about your business?",
    exactQuestion:
      "Additional Information (Free form, up to 300 words): Tell potential clients anything else about your business, work philosophy, or what sets you apart.",
  },
];

export const SELLER_PROFILE_QUESTION_IDS = SELLER_PROFILE_QUESTIONS.map((q) => q.id);
