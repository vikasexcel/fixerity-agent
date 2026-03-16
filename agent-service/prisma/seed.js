import "dotenv/config";
import { prisma } from "../src/lib/prisma.js";

/**
 * Seed the database with example seller profiles across multiple domains.
 * Each profile uses the labeled markdown format expected by buildStructuredSellerProfile.
 */
async function main() {
  const existingSellerCount = await prisma.sellerProfile.count();
  const existingJobCount = await prisma.job.count();

  // SellerProfile seeding can be skipped/overridden with FORCE_SEED.
  // Job records are NEVER deleted; job seeding is always additive.
  if (existingSellerCount > 0 && process.env.FORCE_SEED !== "true") {
    console.log(
      `[Seed] SellerProfile table already has ${existingSellerCount} records. Skipping SellerProfile seed. Set FORCE_SEED=true to override.`
    );
  }

  if (process.env.FORCE_SEED === "true") {
    console.log(
      "[Seed] FORCE_SEED=true, clearing existing SellerProfile records (Job records are preserved)."
    );
    await prisma.sellerProfile.deleteMany();
  }

  const sellers = [
    {
      threadId: "seed-plumbing-1",
      sellerProfile: `
**Headline:** Licensed Residential & Commercial Plumber
**Bio:** I am a licensed master plumber with over 15 years of experience handling everything from small residential repairs to large commercial build-outs.
**Service Type:** Plumbing
**Business Structure:** LLC
**Licensing & Insurance:** State-licensed, fully insured and bonded
**Experience:** 15+ years in residential and commercial plumbing projects
**Availability:** Monday–Saturday, 7am–6pm; 24/7 for emergencies
**Service Area:** Downtown and surrounding suburbs within 30 miles
**Pricing:** Transparent hourly rate plus flat-fee packages for common jobs
**Payment Methods:** Credit card, cash, bank transfer
**References:** Available on request; repeat commercial clients over 10+ years
**Specializations:** Leak detection, repiping, water heater installation, fixture upgrades
**Minimum Job Size:** No minimum for local residential; $500+ for commercial
**Materials & Equipment:** I supply all materials and professional-grade tools
**Warranty/Guarantee:** 1-year labor warranty on all work
**Portfolio:** Completed multi-unit repipes and restaurant build-outs
**Reviews:** Consistently 5-star rated for responsiveness and cleanliness
**Languages:** English, Spanish
**Additional Info:** Same-day service available for most repair calls.
`.trim(),
    },
    {
      threadId: "seed-electrical-1",
      sellerProfile: `
**Headline:** Certified Residential Electrician for Safe Upgrades
**Bio:** Detail-oriented electrician focusing on safe, code-compliant electrical upgrades and troubleshooting.
**Service Type:** Electrical
**Business Structure:** Sole Proprietor
**Licensing & Insurance:** Licensed electrician, fully insured
**Experience:** 10 years specializing in older homes and panel upgrades
**Availability:** Weekdays 8am–5pm, limited weekend slots
**Service Area:** City core and nearby neighborhoods
**Pricing:** Flat pricing for common services and transparent estimates
**Payment Methods:** Credit card, cash, digital wallets
**References:** Homeowners, property managers, and realtors
**Specializations:** Panel upgrades, lighting design, EV charger installs
**Minimum Job Size:** $250 project minimum
**Materials & Equipment:** All materials provided unless client prefers fixtures
**Warranty/Guarantee:** 2-year warranty on workmanship
**Portfolio:** Lighting redesigns and EV charging installations
**Reviews:** Strong reviews for clear communication and tidy work
**Languages:** English
**Additional Info:** Ideal for buyers needing safety inspections and upgrades.
`.trim(),
    },
    {
      threadId: "seed-roofing-1",
      sellerProfile: `
**Headline:** Roofing Contractor for Repairs & Full Replacements
**Bio:** Family-owned roofing company handling both asphalt shingle repairs and full roof replacements.
**Service Type:** Roofing
**Business Structure:** Corporation
**Licensing & Insurance:** Licensed roofing contractor, insured and bonded
**Experience:** 20 years in residential and light commercial roofing
**Availability:** Monday–Friday, weather-permitting; emergency tarp service
**Service Area:** Entire metro region within 50 miles
**Pricing:** Free inspections, detailed written estimates
**Payment Methods:** Credit card, check, financing options
**References:** Insurance adjusters and property management companies
**Specializations:** Storm damage repair, re-roofing, attic ventilation
**Minimum Job Size:** $750 minimum for repair work
**Materials & Equipment:** We source all roofing materials and dispose of debris
**Warranty/Guarantee:** Manufacturer warranty plus 5-year labor warranty
**Portfolio:** Before/after photos for hail and wind damage projects
**Reviews:** High ratings for reliability and clean-up
**Languages:** English
**Additional Info:** Experienced working with insurance claims.
`.trim(),
    },
    {
      threadId: "seed-landscaping-1",
      sellerProfile: `
**Headline:** Full-Service Landscaping & Outdoor Maintenance
**Bio:** We design, install, and maintain beautiful outdoor spaces for residential clients.
**Service Type:** Landscaping
**Business Structure:** LLC
**Licensing & Insurance:** Fully insured landscaping company
**Experience:** 8 years in lawn care, design, and seasonal cleanups
**Availability:** 7 days a week during peak season
**Service Area:** North, West, and central neighborhoods
**Pricing:** Recurring service packages and one-time projects available
**Payment Methods:** Credit card, cash, online invoicing
**References:** Ongoing contracts with HOA communities
**Specializations:** Lawn maintenance, garden design, hardscaping, irrigation
**Minimum Job Size:** $150 for one-time visits
**Materials & Equipment:** All tools and plants supplied
**Warranty/Guarantee:** Plant health and workmanship guarantee on new installs
**Portfolio:** Completed outdoor living spaces and garden makeovers
**Reviews:** Praised for reliability and proactive communication
**Languages:** English, Spanish
**Additional Info:** Ideal for ongoing maintenance after move-in.
`.trim(),
    },
    {
      threadId: "seed-cleaning-1",
      sellerProfile: `
**Headline:** Professional Move-In & Deep Cleaning Services
**Bio:** We specialize in thorough deep cleans for move-in, move-out, and renovation projects.
**Service Type:** Cleaning
**Business Structure:** LLC
**Licensing & Insurance:** Fully insured cleaning company
**Experience:** 6 years of residential and small office cleaning
**Availability:** 7 days a week, including evenings
**Service Area:** Entire city and close suburbs
**Pricing:** Flat-rate packages for move-in and deep cleans
**Payment Methods:** Credit card, cash, digital wallets
**References:** Realtors and property managers
**Specializations:** Move-in deep cleaning, post-renovation dust removal
**Minimum Job Size:** $200 per visit
**Materials & Equipment:** We provide eco-friendly supplies and equipment
**Warranty/Guarantee:** Satisfaction guarantee with re-clean policy
**Portfolio:** Before/after images of kitchen and bath deep cleans
**Reviews:** Known for attention to detail and flexibility
**Languages:** English
**Additional Info:** Great option when a buyer needs a fresh start before moving.
`.trim(),
    },
    {
      threadId: "seed-hvac-1",
      sellerProfile: `
**Headline:** HVAC Technician for Heating & Cooling Systems
**Bio:** Certified HVAC technician offering maintenance, repair, and replacement services.
**Service Type:** HVAC
**Business Structure:** LLC
**Licensing & Insurance:** Licensed and insured HVAC contractor
**Experience:** 12 years working with residential systems
**Availability:** Weekdays plus emergency after-hours support
**Service Area:** City and nearby towns
**Pricing:** Flat diagnostic fee and upfront repair pricing
**Payment Methods:** Credit card, financing for major replacements
**References:** Long-term maintenance clients and property managers
**Specializations:** System tune-ups, furnace/AC replacements, air quality
**Minimum Job Size:** $150 service visit minimum
**Materials & Equipment:** We provide all parts and equipment
**Warranty/Guarantee:** Manufacturer warranty and 1-year labor coverage
**Portfolio:** Documented upgrades from old to high-efficiency systems
**Reviews:** Highly rated for clear explanations and punctuality
**Languages:** English
**Additional Info:** Seasonal maintenance plans available for new homeowners.
`.trim(),
    },
    {
      threadId: "seed-painting-1",
      sellerProfile: `
**Headline:** Interior & Exterior Painting Specialist
**Bio:** Professional painting crew focused on clean lines, proper prep, and durable finishes.
**Service Type:** Painting
**Business Structure:** Corporation
**Licensing & Insurance:** Fully insured, lead-safe certified
**Experience:** 9 years in residential and small commercial painting
**Availability:** Monday–Saturday, flexible scheduling
**Service Area:** Metro area within 40 miles
**Pricing:** Written estimates with itemized scope
**Payment Methods:** Credit card, check
**References:** Real estate agents and repeat homeowners
**Specializations:** Move-in repaints, accent walls, exterior refreshes
**Minimum Job Size:** $300 minimum project size
**Materials & Equipment:** We supply paints and protective materials
**Warranty/Guarantee:** 2-year warranty on interior work
**Portfolio:** Photo gallery of interior and exterior projects
**Reviews:** Known for professionalism and minimal disruption
**Languages:** English
**Additional Info:** Fast turnaround for pre-listing and post-purchase projects.
`.trim(),
    },
    {
      threadId: "seed-handyman-1",
      sellerProfile: `
**Headline:** General Handyman for Small Repairs & Punch Lists
**Bio:** Versatile handyman helping buyers tackle punch lists and small fixes.
**Service Type:** Handyman
**Business Structure:** Sole Proprietor
**Licensing & Insurance:** Insured handyman service
**Experience:** 7 years handling small residential projects
**Availability:** Weekdays and some evenings
**Service Area:** Central districts and nearby suburbs
**Pricing:** Hourly rate with 2-hour minimum
**Payment Methods:** Cash, credit card, digital wallets
**References:** Landlords and property management companies
**Specializations:** Minor carpentry, fixture installs, small repairs
**Minimum Job Size:** 2-hour minimum booking
**Materials & Equipment:** Client can supply materials or I provide them
**Warranty/Guarantee:** 90-day guarantee on workmanship
**Portfolio:** Miscellaneous punch list and repair work
**Reviews:** Appreciated for versatility and problem-solving
**Languages:** English
**Additional Info:** Great for “honey-do” lists after inspection reports.
`.trim(),
    },
    {
      threadId: "seed-it-1",
      sellerProfile: `
**Headline:** Home Office & Network Setup Specialist
**Bio:** IT tech helping homeowners set up reliable Wi-Fi, workstations, and smart devices.
**Service Type:** IT Services
**Business Structure:** Sole Proprietor
**Licensing & Insurance:** Insured technology services provider
**Experience:** 8 years in residential IT and small business support
**Availability:** Evenings and weekends, ideal for busy professionals
**Service Area:** Entire metro and nearby commuter towns
**Pricing:** Package pricing for common home office setups
**Payment Methods:** Credit card, bank transfer
**References:** Remote workers and small business clients
**Specializations:** Wi-Fi optimization, printer setup, smart home devices
**Minimum Job Size:** $200 project minimum
**Materials & Equipment:** Client provides hardware; I supply configuration tools
**Warranty/Guarantee:** 30-day follow-up support on configurations
**Portfolio:** Home office and streaming setups
**Reviews:** Praised for patience and clear explanations
**Languages:** English
**Additional Info:** Ideal for buyers turning a spare room into an office.
`.trim(),
    },
    {
      threadId: "seed-remodeling-1",
      sellerProfile: `
**Headline:** Kitchen & Bathroom Remodeling Contractor
**Bio:** Licensed remodeling contractor focused on kitchens, baths, and interior updates.
**Service Type:** Remodeling
**Business Structure:** LLC
**Licensing & Insurance:** Fully licensed general contractor, insured and bonded
**Experience:** 14 years in residential remodels
**Availability:** Weekdays, schedule booked 4–6 weeks out
**Service Area:** City and nearby suburbs
**Pricing:** Detailed proposals with itemized materials and labor
**Payment Methods:** Credit card, bank transfer, staged payments
**References:** Designers and repeat homeowners
**Specializations:** Kitchen and bath remodels, flooring, interior layout changes
**Minimum Job Size:** $5,000 project minimum
**Materials & Equipment:** We manage material sourcing and trades
**Warranty/Guarantee:** 1-year workmanship warranty
**Portfolio:** Extensive photo gallery of remodel projects
**Reviews:** Strong reviews for project management and quality
**Languages:** English
**Additional Info:** Ideal for buyers planning bigger upgrades after closing.
`.trim(),
    },
    {
      threadId: "seed-inspection-1",
      sellerProfile: `
**Headline:** Certified Home Inspector for Detailed Reports
**Bio:** I provide thorough pre-purchase and pre-listing home inspections with clear, photo-rich reports.
**Service Type:** Home Inspection
**Business Structure:** LLC
**Licensing & Insurance:** State-licensed inspector, E&O insured
**Experience:** 9 years in residential home inspections
**Availability:** Weekdays and limited weekends
**Service Area:** Entire region within 60 miles
**Pricing:** Flat-rate inspections based on property size
**Payment Methods:** Credit card, online payment links
**References:** Realtors and repeat buyer clients
**Specializations:** Pre-purchase inspections, new construction walkthroughs
**Minimum Job Size:** Standard full inspection
**Materials & Equipment:** Professional-grade tools and reporting software
**Warranty/Guarantee:** Clear report delivery within 24 hours
**Portfolio:** Hundreds of completed inspection reports
**Reviews:** Known for thoroughness and easy-to-understand reports
**Languages:** English
**Additional Info:** Ideal for buyers seeking second opinions or follow-up inspections.
`.trim(),
    },
  ];

  if (existingSellerCount === 0 || process.env.FORCE_SEED === "true") {
    await prisma.sellerProfile.createMany({
      data: sellers.map((s) => ({
        threadId: s.threadId,
        sellerProfile: s.sellerProfile,
      })),
    });
    console.log(`[Seed] Inserted ${sellers.length} seller profiles.`);
  }

  const jobs = [
    {
      threadId: "seed-job-plumbing-1",
      jobPost:
        "Need a licensed plumber to replace an old water heater in a single-family home and inspect for any leaks.",
      jobMetadata: {
        type: "Plumbing",
        budget: 1200,
        urgency: "medium",
        location: "Downtown",
      },
    },
    {
      threadId: "seed-job-electrical-1",
      jobPost:
        "Looking for a certified electrician to install recessed lighting in living room and upgrade the electrical panel.",
      jobMetadata: {
        type: "Electrical",
        budget: 2500,
        urgency: "high",
        location: "City core",
      },
    },
    {
      threadId: "seed-job-roofing-1",
      jobPost:
        "Roof inspection and repair for missing shingles after a recent storm on a 2-story home.",
      jobMetadata: {
        type: "Roofing",
        budget: 1800,
        urgency: "high",
        location: "Suburbs",
      },
    },
    {
      threadId: "seed-job-landscaping-1",
      jobPost:
        "Full yard clean-up, new sod installation in the front yard, and basic planting for curb appeal.",
      jobMetadata: {
        type: "Landscaping",
        budget: 1500,
        urgency: "low",
        location: "North neighborhood",
      },
    },
    {
      threadId: "seed-job-cleaning-1",
      jobPost:
        "Move-in deep clean for a 3-bedroom, 2-bath home including inside cabinets, appliances, and windows.",
      jobMetadata: {
        type: "Cleaning",
        budget: 600,
        urgency: "medium",
        location: "Central district",
      },
    },
    {
      threadId: "seed-job-hvac-1",
      jobPost:
        "Need HVAC technician to service existing furnace and AC and provide recommendations on efficiency upgrades.",
      jobMetadata: {
        type: "HVAC",
        budget: 700,
        urgency: "low",
        location: "City + nearby towns",
      },
    },
    {
      threadId: "seed-job-painting-1",
      jobPost:
        "Interior repaint of entire 2-bedroom condo before move-in, neutral colors with low-VOC paint.",
      jobMetadata: {
        type: "Painting",
        budget: 2300,
        urgency: "medium",
        location: "Metro area",
      },
    },
    {
      threadId: "seed-job-handyman-1",
      jobPost:
        "Handyman needed to handle a punch list: fix loose railings, patch drywall, install new light fixtures and curtain rods.",
      jobMetadata: {
        type: "Handyman",
        budget: 800,
        urgency: "medium",
        location: "Central + nearby suburbs",
      },
    },
    {
      threadId: "seed-job-it-1",
      jobPost:
        "Set up home office with reliable Wi‑Fi, dual monitor workstation, printer, and basic smart home integrations.",
      jobMetadata: {
        type: "IT Services",
        budget: 900,
        urgency: "low",
        location: "Metro + commuter towns",
      },
    },
    {
      threadId: "seed-job-remodeling-1",
      jobPost:
        "Kitchen and primary bathroom remodel with new cabinets, countertops, fixtures, and updated lighting.",
      jobMetadata: {
        type: "Remodeling",
        budget: 45000,
        urgency: "low",
        location: "City and close suburbs",
      },
    },
  ];

  await prisma.job.createMany({
    data: jobs.map((j) => ({
      threadId: j.threadId,
      jobPost: j.jobPost,
      jobMetadata: j.jobMetadata,
      status: "created",
    })),
  });
  console.log(
    `[Seed] Inserted ${jobs.length} jobs (Job table had ${existingJobCount} existing records; none were deleted).`
  );
}

main()
  .catch((err) => {
    console.error("[Seed] Error while seeding seller profiles:", err);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });

