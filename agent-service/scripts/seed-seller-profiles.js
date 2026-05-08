/**
 * Seed script: Insert 20 realistic seller profiles across different domains
 * into the Pinecone seller-profile namespace for buyer agent testing.
 *
 * Usage:
 *   npm run seed:seller-profiles
 *   npm run seed:seller-profiles -- --dry-run
 *   npm run seed:seller-profiles -- --limit=5
 *
 * Requires: OPENAI_API_KEY, PINECONE_API_KEY in .env
 */

import "dotenv/config";
import {
  embedSellerProfile,
  retrieveBestCategoryForProfile,
  SELLER_PROFILE_NAMESPACE,
} from "../src/services/pinecone.js";

const SCRIPT_NAME = "SellerProfileSeed";
const DEFAULT_LIMIT = 999; // Seed all profiles by default

// ─── Logging ─────────────────────────────────────────────────────────────────
function logSection(msg) {
  console.log("\n" + "═".repeat(60));
  console.log(`[${SCRIPT_NAME}] ${msg}`);
  console.log("═".repeat(60));
}

function logInfo(msg, data = {}) {
  const dataStr = Object.keys(data).length ? " " + JSON.stringify(data) : "";
  console.log(`[${SCRIPT_NAME}] ${msg}${dataStr}`);
}

function logWarn(msg, data = {}) {
  const dataStr = Object.keys(data).length ? " " + JSON.stringify(data) : "";
  console.warn(`[${SCRIPT_NAME}] WARN: ${msg}${dataStr}`);
}

function logError(msg, data = {}) {
  const dataStr = Object.keys(data).length ? " " + JSON.stringify(data) : "";
  console.error(`[${SCRIPT_NAME}] ERROR: ${msg}${dataStr}`);
}

// ─── Seller profiles (realistic, across diverse domains) ──────────────────────
const SELLER_PROFILES = [
  {
    domain: "Home Cleaning",
    profile: `**Residential House Cleaning Professional**

Service Type: General house cleaning and general labor — residential focus. Regular weekly, biweekly, monthly, and deep cleaning services.
Project Arrangement: Part-time side business with several regular clients.
Licensing: No license required for cleaning in my area; I'm bonded.
Business Structure: Individual, sole proprietor.
Insurance: Yes, liability insurance with $500K coverage. Details on request.
Availability: Weekends and evenings; weekdays by arrangement. Emergency same-day available for extra fee.
Experience: 6 years residential cleaning; hundreds of homes completed.
Service Area: Greater Seattle area, King County. Up to 25 miles travel. Based in Seattle, WA.
Pricing Structure: Flat rate per visit based on size and scope. Typical $130–200 per clean. Rate: $45–55/hr for hourly jobs.
Payment Terms: Payment after each visit; no deposit for regular clients. 50% deposit for deep cleans over $300.
Payment Methods: Cash, Venmo, Zelle, or check.
References: Yes, 3–5 references from current clients available.
Specializations: Eco-friendly products; move-in/out; post-construction cleaning; pet-friendly homes.
Minimum Job Size: $90 minimum per visit for small apartments.
Materials & Equipment: I provide vacuum, mop, and basic supplies; client can provide specialty products.
Warranty: Satisfaction guaranteed; I'll return and fix anything missed at no charge.
Portfolio: Before/after photos available; no formal portfolio.
Reviews: Google reviews 4.8/5; happy to share link.
Languages: English and Spanish.
Additional Info: Reliable, detail-oriented. I focus on kitchens, bathrooms, floors, dusting. Can add laundry or organizing for extra fee. I leave homes spotless.`,
  },
  {
    domain: "Plumbing",
    profile: `**Licensed Master Plumber**

Service Type: Specialized trade — plumbing. Residential and light commercial. Repairs, installations, drain cleaning, water heaters, repipes.
Project Arrangement: Full-time professional — plumbing is my primary business.
Licensing: Yes, Master Plumber license #MP-88234, state certified.
Business Structure: LLC, registered and bonded.
Insurance: Yes, $1M liability, workers comp. Certificate of insurance available.
Availability: Mon–Fri 7am–6pm; weekends and evenings for emergencies; 24/7 emergency line.
Experience: 18 years; thousands of jobs — repairs, new construction, remodels.
Service Area: Portland metro and surrounding suburbs. Up to 40 miles. Location: Portland, OR.
Pricing Structure: Hourly $125; flat rate for common jobs. Water heater install from $1,200. Rate: $125/hr.
Payment Terms: Deposit for parts over $500; balance on completion. Emergency calls require call-out fee.
Payment Methods: Cash, check, credit cards, Venmo.
References: Yes, many satisfied customers.
Specializations: Trenchless sewer repair, tankless water heaters, repiping, slab leaks, gas lines.
Minimum Job Size: $75 service call minimum.
Materials & Equipment: Full truck with tools. Can provide materials or use client-supplied; client choice.
Warranty: 1-year labor warranty on all work; manufacturer warranty on parts.
Portfolio: Photos of repipes, water heater installs, bathroom remodels.
Reviews: Angi (Angie's List) and Google; 4.9 stars.
Languages: English.
Additional Info: Clean, respectful of your home. I explain what I'm doing and give honest advice. Same-day service for most repairs.`,
  },
  {
    domain: "Electrical",
    profile: `**Licensed Electrician — Residential & Commercial**

Service Type: Specialized trade — electrical. Panel upgrades, rewiring, EV charger installs, lighting, repairs.
Project Arrangement: Full-time professional electrician.
Licensing: Yes, journeyman electrician license #JE-4412; bonded.
Business Structure: Corporation.
Insurance: $1M liability; workers comp. COI on request.
Availability: Mon–Fri 8am–5pm; evenings and weekends by appointment. Emergency service available.
Experience: 12 years; residential and light commercial. Hundreds of panel upgrades, whole-house rewires.
Service Area: Denver metro, Boulder, Fort Collins. Up to 50 miles. Location: Denver, CO.
Pricing Structure: Hourly $95; flat rate for common jobs. Panel upgrade from $1,800. Fee: $95/hr typical.
Payment Terms: 30% deposit for larger projects; payment on completion for smaller jobs.
Payment Methods: Check, credit card, Zelle, Venmo.
References: Yes, many references from homeowners and contractors.
Specializations: EV charger installation, solar tie-in, smart home wiring, pool/spa wiring, generator installs.
Minimum Job Size: $150 minimum (includes first hour).
Materials & Equipment: All tools provided; materials quoted per job.
Warranty: 2-year labor warranty; parts per manufacturer.
Portfolio: Photos of panel upgrades, EV chargers, lighting projects.
Reviews: Google and Yelp; 4.8 stars.
Languages: English and Spanish.
Additional Info: Code-compliant work; permits pulled when required. I leave your home clean and explain what was done.`,
  },
  {
    domain: "HVAC",
    profile: `**HVAC Technician — Heating, Cooling, Air Quality**

Service Type: Specialized trade — HVAC. Installations, repairs, maintenance, ductless mini-splits.
Project Arrangement: Full-time professional.
Licensing: EPA certified for refrigerant; state HVAC contractor license.
Business Structure: LLC.
Insurance: Liability and workers comp. $1M coverage.
Availability: Mon–Sat 7am–7pm; 24/7 emergency for no-heat/no-cool.
Experience: 15 years; thousands of systems installed and serviced.
Service Area: Phoenix metro, Scottsdale, Tempe, Mesa. Up to 35 miles. Location: Phoenix, AZ.
Pricing Structure: Flat rate for common repairs; hourly for diagnostics. New AC install from $4,500. Rate: $110/hr.
Payment Terms: Deposit for equipment; balance on completion. Financing available for installations.
Payment Methods: Cash, check, all major credit cards, financing.
References: Yes, extensive reference list.
Specializations: Heat pumps, ductless systems, humidifiers, air purifiers, smart thermostats.
Minimum Job Size: $99 service call.
Materials & Equipment: Full truck stock; provide all equipment and parts.
Warranty: 1-year labor; 5–10 year parts per manufacturer.
Portfolio: Install photos; before/after of ductwork.
Reviews: Google 4.9; BBB A+ rating.
Languages: English.
Additional Info: Honest diagnostics; no pushing unnecessary work. Same-day service when possible. Maintenance plans available.`,
  },
  {
    domain: "Landscaping",
    profile: `**Professional Landscaping & Lawn Care**

Service Type: Landscaping and outdoor services. Lawn mowing, edging, mulching, pruning, design, seasonal cleanup.
Project Arrangement: Full-time; seasonal crew of 4.
Licensing: Landscape contractor license where required.
Business Structure: LLC.
Insurance: Liability and workers comp.
Availability: Mon–Fri 7am–6pm; Saturdays in peak season. Seasonal scheduling.
Experience: 10 years; hundreds of residential and small commercial properties.
Service Area: Austin metro, Round Rock, Cedar Park. Up to 30 miles. Location: Austin, TX.
Pricing Structure: Flat rate per visit for mowing; project-based for design/install. Mowing from $35/visit. Rate: $50/hr for project work.
Payment Terms: Monthly billing for recurring; 50% deposit for large projects.
Payment Methods: Check, Venmo, Zelle, credit card.
References: Yes, many residential clients.
Specializations: Xeriscaping, native plants, irrigation repair, sod installation, tree trimming.
Minimum Job Size: $40 per mow; $200 for design projects.
Materials & Equipment: Own mowers, trimmers, tools. Mulch/plants sourced or client-provided.
Warranty: 1-year on plant material for installs.
Portfolio: Before/after of lawn transformations, garden designs.
Reviews: Google and Nextdoor; 4.7 stars.
Languages: English and Spanish.
Additional Info: Eco-conscious; we use electric equipment where possible. Reliable weekly service.`,
  },
  {
    domain: "Web Development",
    profile: `**Freelance Web Developer — React & Node.js**

Service Type: Professional services — software and web development. Websites, web apps, APIs.
Project Arrangement: Full-time freelancer; part-time availability for smaller projects.
Licensing: N/A.
Business Structure: Sole proprietor.
Insurance: Professional liability (E&O) $500K.
Availability: Flexible; typically Mon–Fri, some evenings. Remote only.
Experience: 8 years; 80+ websites and 40+ web apps delivered.
Service Area: Remote — serve clients nationwide. Based in San Francisco, CA.
Pricing Structure: Hourly $120; fixed price for defined scope. Typical project $2K–15K. Rate: $120/hr.
Payment Terms: 50% to start; 50% on delivery. Milestone billing for larger projects.
Payment Methods: PayPal, Venmo, wire, ACH, credit card.
References: Yes, client references and case studies.
Specializations: React, Next.js, Node.js, WordPress, e-commerce, SEO.
Minimum Job Size: $500 minimum; $1.5K typical smallest project.
Materials & Equipment: N/A — all digital.
Warranty: 30-day bug fixes included; ongoing support available.
Portfolio: Portfolio site with 15+ projects; GitHub.
Reviews: Upwork 5.0; LinkedIn recommendations.
Languages: English.
Additional Info: Clean code, on-time delivery. I communicate clearly and set expectations. Great for startups and SMBs.`,
  },
  {
    domain: "Photography",
    profile: `**Event & Portrait Photographer**

Service Type: Creative and media — photography. Events, portraits, headshots, product.
Project Arrangement: Part-time; weekends and evenings primary.
Licensing: N/A.
Business Structure: Sole proprietor.
Insurance: Liability insurance for events.
Availability: Weekends, some weekdays. Book 2–4 weeks in advance for events.
Experience: 6 years; 200+ events, countless portrait sessions.
Service Area: Chicago metro and suburbs. Will travel up to 60 miles for events. Location: Chicago, IL.
Pricing Structure: Packages or hourly. Events from $1,200; portraits from $350. Rate: $150/hr portrait.
Payment Terms: 50% deposit to hold date; balance 7 days before event.
Payment Methods: Venmo, Zelle, check, credit card.
References: Yes, past clients and venues.
Specializations: Weddings, corporate events, family portraits, newborn, headshots.
Minimum Job Size: $250 for portrait sessions.
Materials & Equipment: Full gear; deliver digital files. Prints available.
Warranty: Reshoot if technical failure; satisfaction guarantee.
Portfolio: Full portfolio online; Instagram.
Reviews: Google, The Knot, WeddingWire — 4.9 stars.
Languages: English.
Additional Info: Candid, natural style. Quick turnaround on deliverables. Easy to work with.`,
  },
  {
    domain: "Moving",
    profile: `**Local & Long-Distance Moving Services**

Service Type: Transportation and logistics — residential and small office moves.
Project Arrangement: Full-time; crew of 3–5.
Licensing: Licensed and insured mover (state and federal if crossing state lines).
Business Structure: LLC.
Insurance: Full moving insurance; valuation coverage available.
Availability: Mon–Sat 7am–6pm. Book 1–2 weeks ahead for peak season.
Experience: 12 years; thousands of local and long-distance moves.
Service Area: NYC metro, NJ, CT. Local and interstate. Location: Brooklyn, NY.
Pricing Structure: Hourly for local ($120–150/hr); weight/mile for long-distance. Local 2BR from $400. Rate: $135/hr local.
Payment Terms: Deposit to hold date; balance on delivery. Payment before unload.
Payment Methods: Cash, check, credit card, Venmo.
References: Yes, many happy customers.
Specializations: Piano moves, delicate items, packing services, storage.
Minimum Job Size: 2-hour minimum local; 3-hour minimum with packing.
Materials & Equipment: Trucks, dollies, pads, boxes (sold or provided).
Warranty: Damaged items covered per valuation; professional handling.
Portfolio: Photos of large moves, specialty items.
Reviews: Google, Yelp — 4.8 stars.
Languages: English, Spanish.
Additional Info: Careful, efficient crews. We treat your belongings like our own. Same-day quotes.`,
  },
  {
    domain: "Roofing",
    profile: `**Licensed Roofing Contractor**

Service Type: Specialized trade — roofing. Repairs, replacements, inspections.
Project Arrangement: Full-time; crews of 5–8.
Licensing: Licensed roofing contractor; bonded.
Business Structure: Corporation.
Insurance: $2M liability; workers comp.
Availability: Mon–Fri 7am–5pm; emergency tarp/repairs same day when possible.
Experience: 20 years; 2,000+ roofs installed. Shingle, metal, flat, tile.
Service Area: Atlanta metro, north Georgia. Up to 50 miles. Location: Atlanta, GA.
Pricing Structure: Per square for replacement; flat rate for repairs. Typical roof $8K–25K. Rate: $4.50/sq ft shingle.
Payment Terms: Deposit to order materials; progress payments; final on completion.
Payment Methods: Check, credit card, financing available.
References: Yes, hundreds of homeowners.
Specializations: Storm damage, insurance claims, metal roofing, flat roofs, skylights.
Minimum Job Size: $500 for repairs.
Materials & Equipment: Provide all materials and crew.
Warranty: Manufacturer warranty on materials; 10-year workmanship.
Portfolio: Photos of completed roofs, before/after storm damage.
Reviews: Google, BBB — 4.9 stars.
Languages: English, Spanish.
Additional Info: Free inspections. We work with insurance. Clean job site daily.`,
  },
  {
    domain: "Carpentry",
    profile: `**Custom Carpenter — Cabinets, Trim, Finish Work**

Service Type: Specialized trade — carpentry. Custom cabinets, trim, doors, built-ins, decks.
Project Arrangement: Full-time professional.
Licensing: General contractor license for larger jobs; carpentry focus.
Business Structure: LLC.
Insurance: Liability and workers comp.
Availability: Mon–Fri 8am–5pm; some Saturdays.
Experience: 14 years; custom homes, remodels, commercial fit-outs.
Service Area: Minneapolis–St. Paul metro. Up to 40 miles. Location: Minneapolis, MN.
Pricing Structure: Hourly $85; flat rate for defined projects. Typical cabinet job $3K–15K. Rate: $85/hr.
Payment Terms: 30% to start; draws during project; final on completion.
Payment Methods: Check, Venmo, Zelle.
References: Yes, builders and homeowners.
Specializations: Custom cabinets, crown molding, wainscoting, stair rails, deck framing.
Minimum Job Size: $300 minimum.
Materials & Equipment: Provide tools; materials quoted per job. Can source or client provides.
Warranty: 1-year on labor; materials per supplier.
Portfolio: Photos of cabinets, trim, decks.
Reviews: Houzz, Google — 4.8 stars.
Languages: English.
Additional Info: Precision work. I show up on time and finish on schedule. Clean and respectful.`,
  },
  {
    domain: "Painting",
    profile: `**Residential & Commercial Painter**

Service Type: General handyman and specialized — interior and exterior painting.
Project Arrangement: Full-time; crew of 2–4.
Licensing: Licensed painter where required.
Business Structure: LLC.
Insurance: Liability; workers comp.
Availability: Mon–Fri 7am–5pm; weekends for exterior when weather permits.
Experience: 11 years; 500+ homes painted.
Service Area: Miami, Fort Lauderdale, South Florida. Up to 35 miles. Location: Miami, FL.
Pricing Structure: Per square foot or per room. Interior from $3/sq ft; exterior from $2.50/sq ft. Rate: $45/hr touch-ups.
Payment Terms: 30% deposit; balance on completion. Progress for large jobs.
Payment Methods: Cash, check, Venmo, Zelle.
References: Yes, many homeowners and property managers.
Specializations: Cabinet refinishing, faux finishes, stucco, deck staining, pressure washing.
Minimum Job Size: $400 minimum.
Materials & Equipment: Provide all tools; paint quoted (can use client's).
Warranty: 2-year warranty on exterior; 1-year interior.
Portfolio: Before/after photos of interiors and exteriors.
Reviews: Google, Yelp — 4.7 stars.
Languages: English, Spanish.
Additional Info: We prep thoroughly — masking, sanding, priming. Clean job site daily.`,
  },
  {
    domain: "Dog Walking / Pet Care",
    profile: `**Professional Dog Walker & Pet Sitter**

Service Type: Pet services — dog walking, pet sitting, overnight care.
Project Arrangement: Full-time; my main business.
Licensing: N/A; insured and bonded.
Business Structure: Sole proprietor.
Insurance: Pet care liability insurance; bonded.
Availability: 7am–8pm daily; some holidays. Flexible scheduling.
Experience: 5 years; 100+ regular clients; thousands of walks.
Service Area: Boston metro, Cambridge, Somerville, Brookline. Location: Boston, MA.
Pricing Structure: Per walk ($25–35) or package. Overnight $75/night. Rate: $28/walk typical.
Payment Terms: Payment after each walk or weekly for recurring. Deposit for overnight.
Payment Methods: Venmo, Zelle, check, PayPal.
References: Yes, many pet parent references.
Specializations: Puppies, senior dogs, multiple dogs, medication administration, cats.
Minimum Job Size: $25 per 30-min walk.
Materials & Equipment: Leashes, bags; client provides keys/access.
Warranty: Satisfaction guaranteed; will redo visit if needed.
Portfolio: Photos of happy pups (with permission). Instagram.
Reviews: Rover, Google — 5.0 stars.
Languages: English.
Additional Info: Reliable, loving care. GPS-tracked walks; photo updates. Great with anxious or reactive dogs.`,
  },
  {
    domain: "Tutoring",
    profile: `**Math & Science Tutor — K–12 & College**

Service Type: Professional services — tutoring and education.
Project Arrangement: Part-time; evenings and weekends.
Licensing: N/A.
Business Structure: Sole proprietor.
Insurance: N/A.
Availability: Mon–Thu 4pm–9pm; Sat 10am–2pm. Summer more flexible.
Experience: 7 years; 150+ students; AP, SAT, ACT, college math.
Service Area: San Diego metro. In-home or remote. Location: San Diego, CA.
Pricing Structure: Hourly. $60–80/hr depending on level. Rate: $70/hr typical.
Payment Terms: Pay per session or monthly package. 24hr cancel policy.
Payment Methods: Venmo, Zelle, check.
References: Yes, parent and student references.
Specializations: Algebra, geometry, calculus, physics, chemistry, test prep.
Minimum Job Size: 1-hour minimum per session.
Materials & Equipment: N/A.
Warranty: N/A — results vary by student effort.
Portfolio: Test score improvements; parent testimonials.
Reviews: Google, thumbtack — 4.9 stars.
Languages: English.
Additional Info: Patient, clear explanations. I help students build confidence. In-person or Zoom.`,
  },
  {
    domain: "Event Planning / Wedding",
    profile: `**Wedding & Event Coordinator**

Service Type: Event and hospitality — full-service wedding and event planning.
Project Arrangement: Full-time; 15–20 events per year.
Licensing: N/A.
Business Structure: LLC.
Insurance: Event liability insurance.
Availability: Year-round; weekends for weddings; weekdays for meetings.
Experience: 9 years; 120+ weddings; corporate events, parties.
Service Area: Dallas–Fort Worth metro. Will travel to Austin, Houston for events. Location: Dallas, TX.
Pricing Structure: Percentage of budget or flat fee. Full planning from $3,500; day-of from $1,200. Rate: $1,500–4,000 typical.
Payment Terms: 50% deposit; remainder 2 weeks before event.
Payment Methods: Check, Venmo, Zelle, credit card.
References: Yes, past couples and venues.
Specializations: Outdoor weddings, destination, cultural weddings, intimate elopements.
Minimum Job Size: $800 for day-of coordination.
Materials & Equipment: Provide planning tools; client pays vendors.
Warranty: Satisfaction focus; we work until you're happy.
Portfolio: Full wedding galleries; featured in local blogs.
Reviews: The Knot, WeddingWire — 5.0 stars.
Languages: English, Spanish.
Additional Info: Stress-free planning. I handle details so you enjoy the day. Responsive and organized.`,
  },
  {
    domain: "Catering",
    profile: `**Full-Service Caterer — Corporate & Private Events**

Service Type: Event and hospitality — catering. Drop-off, full-service, drop-off.
Project Arrangement: Full-time; team of 8–15 for events.
Licensing: Health department permitted; food handler certified.
Business Structure: LLC.
Insurance: Liability; food service insurance.
Availability: 7 days; events booked 2–4 weeks ahead typically.
Experience: 12 years; 500+ events — corporate, weddings, private parties.
Service Area: Philadelphia metro, NJ, DE. Travel up to 60 miles. Location: Philadelphia, PA.
Pricing Structure: Per person; varies by menu. Buffet from $25/person; plated from $45/person. Rate: $35/person typical.
Payment Terms: 50% deposit; balance 1 week before event.
Payment Methods: Check, credit card, wire.
References: Yes, corporate and private clients.
Specializations: Farm-to-table, dietary accommodations, cocktail hour, dessert bars.
Minimum Job Size: $500 minimum; 20-person minimum for full-service.
Materials & Equipment: Provide all food, serving, staff; client provides venue.
Warranty: We make it right if there's an issue.
Portfolio: Event photos; sample menus.
Reviews: Google, Yelp — 4.8 stars.
Languages: English.
Additional Info: Creative menus. We work with allergies and preferences. Professional staff.`,
  },
  {
    domain: "Legal (Document Prep)",
    profile: `**Legal Document Preparation Services**

Service Type: Business/legal services — document preparation, notary, filing assistance. Not a lawyer; prepare forms per your instructions.
Project Arrangement: Part-time; by appointment.
Licensing: Notary public; document preparer registered in state.
Business Structure: Sole proprietor.
Insurance: E&O insurance.
Availability: Tue–Sat by appointment; some evenings.
Experience: 8 years; 1,000+ documents prepared — LLCs, divorces, wills, deeds.
Service Area: Las Vegas, Henderson, North Las Vegas. Location: Las Vegas, NV.
Pricing Structure: Flat fee per document type. LLC from $150; simple will from $200. Rate: $150–400 typical.
Payment Terms: Payment at appointment; half upfront for complex.
Payment Methods: Cash, check, Venmo, Zelle.
References: Yes, past clients (confidential).
Specializations: Business formation, divorce forms, wills, deeds, name changes.
Minimum Job Size: $75 minimum.
Materials & Equipment: N/A.
Warranty: Accuracy; we correct errors at no charge.
Portfolio: N/A — confidential.
Reviews: Google — 4.9 stars.
Languages: English, Spanish.
Additional Info: I don't give legal advice. I help you complete forms correctly. Fast turnaround.`,
  },
  {
    domain: "Accounting / Bookkeeping",
    profile: `**Small Business Bookkeeper & Tax Prep**

Service Type: Financial services — bookkeeping, tax preparation, payroll.
Project Arrangement: Full-time; mix of recurring and seasonal.
Licensing: Enrolled Agent (IRS); QuickBooks ProAdvisor.
Business Structure: Sole proprietor.
Insurance: E&O insurance.
Availability: Mon–Fri 9am–5pm; extended hours Jan–Apr.
Experience: 11 years; 80+ small business clients.
Service Area: Remote — nationwide. Based in Nashville, TN. Location: Nashville, TN (remote).
Pricing Structure: Monthly retainer for bookkeeping ($200–800); per-return for taxes. Rate: $50–75/hr.
Payment Terms: Monthly for retainer; due at filing for taxes.
Payment Methods: ACH, check, credit card.
References: Yes, business client references.
Specializations: S-corps, LLCs, contractors, restaurants, e-commerce.
Minimum Job Size: $150/month minimum retainer.
Materials & Equipment: Client provides access to books; I use QBO, Xero.
Warranty: Accuracy; we amend if error on our part.
Portfolio: Case studies (anonymized).
Reviews: Google — 4.9 stars.
Languages: English.
Additional Info: I keep you compliant and organized. Clean books, on-time filings. Responsive.`,
  },
  {
    domain: "Graphic Design",
    profile: `**Freelance Graphic Designer — Branding & Print**

Service Type: Creative and media — graphic design. Logos, brochures, social media, packaging.
Project Arrangement: Full-time freelancer.
Licensing: N/A.
Business Structure: Sole proprietor.
Insurance: N/A.
Availability: Mon–Fri; flexible. 1–2 week turnaround typical.
Experience: 6 years; 200+ projects for SMBs and startups.
Service Area: Remote. Based in Austin, TX. Location: Austin, TX (remote).
Pricing Structure: Per project or hourly. Logo from $400; brand kit from $1,200. Rate: $75/hr.
Payment Terms: 50% to start; 50% on delivery.
Payment Methods: PayPal, Venmo, Zelle, invoice.
References: Yes, client testimonials.
Specializations: Brand identity, print design, social templates, packaging, signage.
Minimum Job Size: $300 minimum.
Materials & Equipment: N/A.
Warranty: 2 rounds of revisions included; more available.
Portfolio: Full portfolio online; Behance, Dribbble.
Reviews: Google, Upwork — 4.9 stars.
Languages: English.
Additional Info: Clean, modern aesthetic. I deliver print-ready files and source files. Fast communication.`,
  },
  {
    domain: "Auto Repair",
    profile: `**Independent Auto Repair & Maintenance**

Service Type: Automotive services — repairs, maintenance, diagnostics.
Project Arrangement: Full-time; shop with 2 techs.
Licensing: ASE certified; state inspected.
Business Structure: LLC.
Insurance: Shop liability; garage keepers.
Availability: Mon–Fri 8am–6pm; Sat 9am–2pm.
Experience: 16 years; thousands of vehicles serviced.
Service Area: Houston metro, Katy, Sugar Land. Location: Houston, TX.
Pricing Structure: Hourly labor $95; parts at cost + markup. Typical brake job $300–500. Rate: $95/hr labor.
Payment Terms: Payment on completion. For large jobs, deposit for parts.
Payment Methods: Cash, check, credit card.
References: Yes, long-standing customers.
Specializations: Asian and European imports, brakes, suspension, engine, transmission, diagnostics.
Minimum Job Size: $50 diagnostic fee.
Materials & Equipment: Full shop; OEM and quality aftermarket parts.
Warranty: 12-month/12K-mile warranty on most repairs.
Portfolio: N/A.
Reviews: Google — 4.8 stars.
Languages: English, Spanish.
Additional Info: Honest diagnostics. We don't upsell. Loaner car for larger jobs.`,
  },
  {
    domain: "Home Remodeling / General Contractor",
    profile: `**General Contractor — Kitchens, Baths, Additions**

Service Type: Professional services — general contracting. Kitchens, baths, additions, whole-house remodels.
Project Arrangement: Full-time; crew and subs.
Licensing: Licensed general contractor; bonded.
Business Structure: Corporation.
Insurance: $2M liability; workers comp.
Availability: Mon–Fri 7am–5pm; scheduling 4–8 weeks out for large projects.
Experience: 22 years; 400+ major remodels.
Service Area: Los Angeles, San Fernando Valley, South Bay. Up to 45 miles. Location: Los Angeles, CA.
Pricing Structure: Cost plus or fixed bid. Kitchen remodel $25K–80K; bath $15K–40K. Rate: Project-based.
Payment Terms: Draw schedule — 30% start; milestones; final on completion.
Payment Methods: Check, wire, credit card for materials.
References: Yes, many homeowners.
Specializations: Custom kitchens, master baths, ADUs, seismic retrofit.
Minimum Job Size: $5,000 minimum project.
Materials & Equipment: Source all materials; manage subs.
Warranty: 1-year workmanship; manufacturer on materials.
Portfolio: Before/after of kitchens, baths, additions.
Reviews: Yelp, Houzz — 4.9 stars.
Languages: English, Spanish.
Additional Info: Licensed, insured, permitted. We communicate clearly and stick to timeline. Quality craftsmanship.`,
  },
  // ─── Additional Financial ───────────────────────────────────────────────────
  {
    domain: "Tax Preparation",
    profile: `**Tax Preparation Specialist — Individual & Small Business**

Service Type: Financial services — tax preparation and filing.
Project Arrangement: Full-time during tax season; part-time off-season.
Licensing: Enrolled Agent (IRS); PTIN holder.
Business Structure: Sole proprietor.
Insurance: E&O insurance.
Availability: Jan–Apr: Mon–Sat 8am–8pm; May–Dec: by appointment.
Experience: 9 years; 500+ returns per season.
Service Area: Remote — nationwide. Based in Charlotte, NC. Location: Charlotte, NC (remote).
Pricing Structure: Per return. Simple 1040 from $150; itemized from $250; business from $400. Rate: $100–350 per return.
Payment Terms: Payment at completion; deposit for complex returns.
Payment Methods: Check, Venmo, Zelle, ACH.
References: Yes, repeat clients.
Specializations: Self-employed, rental income, crypto, multi-state, amendments.
Minimum Job Size: $100 minimum.
Materials & Equipment: Secure portal for documents; e-file.
Warranty: Accuracy; free amendment if error on our part.
Portfolio: N/A.
Reviews: Google — 4.9 stars.
Languages: English.
Additional Info: I maximize your refund and minimize stress. Organized, responsive. Extension and amendment support.`,
  },
  {
    domain: "Financial Planning",
    profile: `**Fee-Only Financial Planner — Retirement & Investment**

Service Type: Financial services — financial planning, retirement, investment advice.
Project Arrangement: Full-time; fee-only (no commissions).
Licensing: CFP® certified; Series 65.
Business Structure: LLC.
Insurance: E&O; fiduciary bond.
Availability: Mon–Fri 9am–5pm; some evenings by appointment.
Experience: 12 years; 200+ client households.
Service Area: Remote and in-person. Denver metro. Location: Denver, CO.
Pricing Structure: Hourly $200; flat-fee plans $1,500–5,000; AUM for ongoing. Rate: $200/hr.
Payment Terms: 50% to start for plans; monthly for AUM.
Payment Methods: ACH, check, wire.
References: Yes, client references (with permission).
Specializations: Retirement planning, 401(k) rollovers, tax-efficient investing, estate basics.
Minimum Job Size: $500 for one-time consultation.
Materials & Equipment: N/A.
Warranty: Fiduciary standard; we act in your best interest.
Portfolio: N/A — confidential.
Reviews: XY Planning Network, Google — 4.9 stars.
Languages: English.
Additional Info: No product sales. Transparent fees. I help you build a clear plan and stick to it.`,
  },
  {
    domain: "Payroll Services",
    profile: `**Small Business Payroll & HR Support**

Service Type: Financial and business services — payroll processing, tax filings, HR support.
Project Arrangement: Full-time; recurring clients.
Licensing: CPP (Certified Payroll Professional).
Business Structure: LLC.
Insurance: E&O insurance.
Availability: Mon–Fri 8am–6pm; payroll runs by schedule.
Experience: 10 years; 60+ businesses.
Service Area: Remote — nationwide. Based in Columbus, OH. Location: Columbus, OH (remote).
Pricing Structure: Per payroll run ($35–75) or monthly flat ($100–300). Rate: $50/payroll typical.
Payment Terms: Monthly billing; due by 1st.
Payment Methods: ACH, check, credit card.
References: Yes, business client references.
Specializations: Multi-state, contractors, PTO tracking, year-end W-2s, new hire reporting.
Minimum Job Size: $75/month minimum.
Materials & Equipment: Integrate with QBO, Xero, Gusto; direct deposit.
Warranty: Accuracy; we fix errors and cover penalties if our fault.
Portfolio: N/A.
Reviews: Google — 4.8 stars.
Languages: English.
Additional Info: On-time, accurate payroll. Tax deposits and filings handled. Responsive support.`,
  },
  // ─── Additional Pet Care ─────────────────────────────────────────────────────
  {
    domain: "Pet Grooming",
    profile: `**Professional Pet Groomer — Dogs & Cats**

Service Type: Pet services — grooming, bathing, nail trims, de-shedding.
Project Arrangement: Full-time; salon and mobile.
Licensing: Certified groomer; state business license.
Business Structure: Sole proprietor.
Insurance: Liability; care custody control.
Availability: Tue–Sat 8am–5pm; by appointment.
Experience: 8 years; thousands of grooms.
Service Area: Tampa Bay area, St. Petersburg, Clearwater. Mobile up to 15 miles. Location: Tampa, FL.
Pricing Structure: By breed/size. Small dog from $45; large from $75; cats from $55. Rate: $50–90 typical.
Payment Terms: Payment at pickup.
Payment Methods: Cash, card, Venmo.
References: Yes, many repeat clients.
Specializations: Difficult dogs, matted coats, breed cuts, senior pets, de-shedding.
Minimum Job Size: $35 minimum.
Materials & Equipment: Full salon; hypoallergenic products available.
Warranty: Satisfaction; will redo if needed.
Portfolio: Before/after photos (with permission).
Reviews: Google, Yelp — 4.9 stars.
Languages: English, Spanish.
Additional Info: Gentle, patient. I work with anxious pets. Mobile option for seniors or multiple pets.`,
  },
  {
    domain: "Pet Boarding & Daycare",
    profile: `**Pet Boarding & Daycare — Home-Based**

Service Type: Pet services — overnight boarding, daycare, pet sitting.
Project Arrangement: Full-time; home-based facility.
Licensing: Licensed and inspected; pet care certified.
Business Structure: LLC.
Insurance: Liability; care custody control.
Availability: 7 days; drop-off 7am–10am; pickup 4pm–7pm.
Experience: 6 years; 500+ pets boarded.
Service Area: Raleigh-Durham metro. Location: Raleigh, NC.
Pricing Structure: Daycare $35/day; boarding $45–55/night. Multi-pet discount. Rate: $45/night typical.
Payment Terms: Deposit to hold; balance at pickup.
Payment Methods: Cash, Venmo, Zelle, card.
References: Yes, many repeat clients.
Specializations: Small dogs only (under 25 lbs), cats, medication administration, special diets.
Minimum Job Size: $35/day.
Materials & Equipment: Indoor/outdoor play; climate controlled; cameras for owners.
Warranty: We treat your pet like family; vet on call.
Portfolio: Photos of facility and happy pets.
Reviews: Google, Rover — 5.0 stars.
Languages: English.
Additional Info: Small, intimate setting. No kennels — pets roam in safe areas. Daily photo updates.`,
  },
  {
    domain: "Dog Training",
    profile: `**Certified Dog Trainer — Obedience & Behavior**

Service Type: Pet services — dog training, behavior modification.
Project Arrangement: Full-time professional.
Licensing: CPDT-KA certified; member of APDT.
Business Structure: Sole proprietor.
Insurance: Liability insurance.
Availability: Mon–Sat; flexible scheduling. Group classes weekends.
Experience: 7 years; 400+ dogs trained.
Service Area: Seattle metro, Eastside, Snohomish. In-home or my facility. Location: Seattle, WA.
Pricing Structure: Private session $120; 6-pack $600; group class $200 for 6 weeks. Rate: $120/session.
Payment Terms: Payment per session or package upfront.
Payment Methods: Venmo, Zelle, check, card.
References: Yes, client and vet references.
Specializations: Puppy basics, leash pulling, reactivity, recall, aggression, service dog prep.
Minimum Job Size: $120 per private session.
Materials & Equipment: Treats, leash; client provides collar/harness.
Warranty: Progress guaranteed with follow-through; support between sessions.
Portfolio: Video testimonials; before/after behavior.
Reviews: Google, Yelp — 4.9 stars.
Languages: English.
Additional Info: Force-free, positive reinforcement. I work with you and your dog as a team. Patient and clear.`,
  },
];

// ─── Main ────────────────────────────────────────────────────────────────────
function parseArgs(argv) {
  const result = { dryRun: false, limit: DEFAULT_LIMIT };
  for (const arg of argv) {
    if (arg === "--dry-run") result.dryRun = true;
    else if (arg.startsWith("--limit=")) result.limit = parseInt(arg.split("=")[1], 10) || DEFAULT_LIMIT;
  }
  return result;
}

function validateEnv() {
  const missing = ["OPENAI_API_KEY", "PINECONE_API_KEY"].filter((k) => !process.env[k]);
  if (missing.length > 0) {
    throw new Error(`Missing required env: ${missing.join(", ")}`);
  }
}

async function main() {
  const args = parseArgs(process.argv.slice(2));
  const profiles = SELLER_PROFILES.slice(0, args.limit);

  logSection("Starting seller profile seed");
  logInfo("Configuration", {
    profilesToSeed: profiles.length,
    namespace: SELLER_PROFILE_NAMESPACE,
    dryRun: args.dryRun,
    limit: args.limit,
  });

  validateEnv();

  if (args.dryRun) {
    logInfo("Dry run — skipping Pinecone embed. Profiles that would be seeded:");
    profiles.forEach((p, i) => logInfo(`  ${i + 1}. ${p.domain}`, { preview: p.profile.slice(0, 80) + "..." }));
    return;
  }

  let successCount = 0;
  let failCount = 0;

  for (let i = 0; i < profiles.length; i++) {
    const { domain, profile } = profiles[i];
    const threadId = `seed-seller-${i + 1}-${domain.toLowerCase().replace(/\s+/g, "-")}`;

    logInfo(`Embedding profile ${i + 1}/${profiles.length}`, { domain, threadId });

    try {
      const categoryResult = await retrieveBestCategoryForProfile(profile);
      const result = await embedSellerProfile(profile, threadId, categoryResult);

      if (result.embeddingId && result.chunkCount > 0) {
        successCount++;
        logInfo(`  ✓ Embedded`, {
          profileId: result.embeddingId,
          chunkCount: result.chunkCount,
          metadata: result.profileMetadata,
          service: categoryResult.service?.name ?? null,
        });
      } else {
        failCount++;
        logWarn(`  ✗ Embed returned no ID or zero chunks`, { result });
      }
    } catch (err) {
      failCount++;
      logError(`  ✗ Embed failed for ${domain}`, { message: err.message });
    }
  }

  logSection("Seed complete");
  logInfo("Summary", {
    total: profiles.length,
    success: successCount,
    failed: failCount,
    namespace: SELLER_PROFILE_NAMESPACE,
  });
}

main().catch((err) => {
  logError("Fatal error", { message: err.message });
  if (err.stack) console.error(err.stack);
  process.exitCode = 1;
});
