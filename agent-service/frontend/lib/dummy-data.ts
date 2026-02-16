export interface User {
  id: string;
  email: string;
  name: string;
  role: 'buyer' | 'seller';
  avatar?: string;
}

export interface Priority {
  type: 'price' | 'startDate' | 'endDate' | 'rating' | 'jobsCompleted' | 'licensed' | 'references';
  level: 'must_have' | 'nice_to_have' | 'bonus';
  value?: string | number;
  description: string;
}

export interface Job {
  id: string;
  buyerId: string;
  title: string;
  description: string;
  budget: {
    min: number;
    max: number;
  };
  startDate: string;
  endDate: string;
  priorities: Priority[];
  createdAt: string;
  deadline?: string;
  status: 'open' | 'matched' | 'completed';
  service_category_id?: number;
  sub_category_id?: number;
  lat?: number;
  long?: number;
}

export interface Agent {
  id: string;
  userId: string;
  name: string;
  type: 'buyer' | 'seller';
  rating: number;
  jobsCompleted: number;
  licensed: boolean;
  references: boolean;
  bio: string;
  avatar?: string;
  hourlyRate?: number;
  skills?: string[];
  createdAt: string;
}

export interface Deal {
  id: string;
  jobId: string;
  sellerId: string;
  /** Present when using dummy/full data; API negotiate-and-match may return sellerName + quote instead. */
  sellerAgent?: Agent;
  matchScore: number;
  matchReasons: string[];
  status: 'proposed' | 'rejected' | 'accepted';
  createdAt: string;
  job?: Job; // Optional: included when returned from match API
  /** Agreed price from negotiation (negotiate-and-match endpoint). */
  negotiatedPrice?: number;
  /** Agreed completion time in days (negotiate-and-match endpoint). */
  negotiatedCompletionDays?: number;
  /** Whether negotiation ended in accept or timeout. */
  negotiationStatus?: 'accepted' | 'timeout';
  /** Name from API when sellerAgent is not populated. */
  sellerName?: string;
  /** Provider email from SellerProfile (for contact). */
  sellerEmail?: string | null;
  /** Provider contact number from SellerProfile (for contact). */
  sellerContactNumber?: string | null;
  /** Quote from API (e.g. price, days, paymentSchedule, licensed, referencesAvailable). */
  quote?: {
    price?: number;
    days?: number;
    completionDays?: number;
    paymentSchedule?: string;
    licensed?: boolean;
    referencesAvailable?: boolean;
  };
  /** Provider's AI response message (stored from negotiate-and-match). */
  negotiationMessage?: string;
  /** Rank from seller match API (1 = best match). */
  rank?: number;
}

// Dummy Users
export const dummyUsers: User[] = [
  {
    id: 'user_1',
    email: 'buyer@example.com',
    name: 'Sarah Chen',
    role: 'buyer',
    avatar: undefined,
  },
  {
    id: 'user_2',
    email: 'seller@example.com',
    name: 'Marcus Johnson',
    role: 'seller',
    avatar: undefined,
  },
];

// Dummy Agents
export const dummyAgents: Agent[] = [
  {
    id: 'agent_1',
    userId: 'user_1',
    name: 'Sarah Chen',
    type: 'buyer',
    rating: 4.8,
    jobsCompleted: 24,
    licensed: true,
    references: true,
    bio: 'Experienced buyer agent specializing in construction and renovation projects.',
    avatar: undefined,
    createdAt: '2024-01-15',
  },
  {
    id: 'agent_2',
    userId: 'user_2',
    name: 'Marcus Johnson',
    type: 'seller',
    rating: 4.6,
    jobsCompleted: 42,
    licensed: true,
    references: true,
    bio: 'Expert in residential and commercial projects with 10+ years experience.',
    avatar: undefined,
    hourlyRate: 85,
    skills: ['Construction', 'Project Management', 'Electrical', 'Plumbing'],
    createdAt: '2023-06-20',
  },
  {
    id: 'agent_3',
    userId: 'user_3',
    name: 'Elena Rodriguez',
    type: 'seller',
    rating: 4.9,
    jobsCompleted: 38,
    licensed: true,
    references: true,
    bio: 'Specialized in high-end interior design and renovation.',
    avatar: undefined,
    hourlyRate: 120,
    skills: ['Interior Design', 'Renovation', 'Project Management'],
    createdAt: '2023-03-10',
  },
  {
    id: 'agent_4',
    userId: 'user_4',
    name: 'David Park',
    type: 'seller',
    rating: 4.5,
    jobsCompleted: 28,
    licensed: true,
    references: false,
    bio: 'Professional contractor with focus on kitchen and bathroom upgrades.',
    avatar: undefined,
    hourlyRate: 75,
    skills: ['Kitchen Design', 'Bathroom Renovation', 'Carpentry'],
    createdAt: '2023-09-05',
  },
  {
    id: 'agent_5',
    userId: 'user_5',
    name: 'Jessica Wong',
    type: 'seller',
    rating: 4.7,
    jobsCompleted: 55,
    licensed: true,
    references: true,
    bio: 'Expert project manager for large-scale commercial renovations.',
    avatar: undefined,
    hourlyRate: 150,
    skills: ['Commercial Projects', 'Management', 'Consulting'],
    createdAt: '2023-01-12',
  },
  {
    id: 'agent_6',
    userId: 'user_6',
    name: 'Tom Bradley',
    type: 'seller',
    rating: 4.3,
    jobsCompleted: 15,
    licensed: false,
    references: true,
    bio: 'Emerging contractor with strong customer reviews.',
    avatar: undefined,
    hourlyRate: 55,
    skills: ['General Construction', 'Painting', 'Handyman'],
    createdAt: '2024-02-01',
  },
];

// Dummy Jobs
export const dummyJobs: Job[] = [
  {
    id: 'job_1',
    buyerId: 'user_1',
    title: 'Kitchen Renovation - Modern Design',
    description: 'Complete kitchen renovation including new cabinets, countertops, flooring, and appliances. Looking for professional with interior design expertise.',
    budget: {
      min: 15000,
      max: 25000,
    },
    startDate: '2025-02-15',
    endDate: '2025-03-30',
    deadline: '8 days',
    status: 'open',
    priorities: [
      {
        type: 'price',
        level: 'must_have',
        value: 25000,
        description: 'Must stay under $25,000',
      },
      {
        type: 'startDate',
        level: 'must_have',
        value: '2025-02-15',
        description: 'Must start by February 15',
      },
      {
        type: 'rating',
        level: 'nice_to_have',
        value: 4.5,
        description: 'Prefer rating above 4.5',
      },
      {
        type: 'licensed',
        level: 'must_have',
        description: 'Must be licensed',
      },
      {
        type: 'references',
        level: 'nice_to_have',
        description: 'Nice to have references available',
      },
      {
        type: 'jobsCompleted',
        level: 'bonus',
        value: 30,
        description: 'Bonus if 30+ jobs completed',
      },
    ],
    createdAt: '2025-02-03',
  },
  {
    id: 'job_2',
    buyerId: 'user_1',
    title: 'Bathroom Upgrade Project',
    description: 'Update two bathrooms with new fixtures, tiles, and lighting. Mid-range budget, quality finish preferred.',
    budget: {
      min: 8000,
      max: 12000,
    },
    startDate: '2025-02-20',
    endDate: '2025-03-15',
    deadline: '10 days',
    status: 'open',
    priorities: [
      {
        type: 'price',
        level: 'must_have',
        value: 12000,
        description: 'Budget limit $12,000',
      },
      {
        type: 'endDate',
        level: 'must_have',
        value: '2025-03-15',
        description: 'Complete by March 15',
      },
      {
        type: 'jobsCompleted',
        level: 'must_have',
        value: 15,
        description: 'Minimum 15 completed jobs',
      },
      {
        type: 'rating',
        level: 'nice_to_have',
        value: 4.4,
        description: 'Prefer 4.4+ rating',
      },
      {
        type: 'licensed',
        level: 'bonus',
        description: 'Bonus if licensed',
      },
    ],
    createdAt: '2025-02-03',
  },
  {
    id: 'job_3',
    buyerId: 'user_1',
    title: 'Living Room & Hallway Flooring',
    description: 'Install hardwood flooring in living room and hallway, approximately 450 sq ft. Need expertise in finishing and installation.',
    budget: {
      min: 4500,
      max: 7000,
    },
    startDate: '2025-03-01',
    endDate: '2025-03-15',
    deadline: '3 days',
    status: 'open',
    priorities: [
      {
        type: 'price',
        level: 'nice_to_have',
        value: 6000,
        description: 'Prefer under $6,000',
      },
      {
        type: 'rating',
        level: 'must_have',
        value: 4.6,
        description: 'Need 4.6+ rating for quality',
      },
      {
        type: 'jobsCompleted',
        level: 'nice_to_have',
        value: 25,
        description: 'Nice to have 25+ completed jobs',
      },
      {
        type: 'references',
        level: 'must_have',
        description: 'Must have references',
      },
      {
        type: 'licensed',
        level: 'must_have',
        description: 'Must be licensed',
      },
    ],
    createdAt: '2025-02-02',
  },
];

// Dummy Deals
export const dummyDeals: Deal[] = [
  {
    id: 'deal_1',
    jobId: 'job_1',
    sellerId: 'user_3',
    sellerAgent: dummyAgents[2],
    matchScore: 98,
    matchReasons: [
      'Price within budget ($22,500)',
      'Licensed and experienced',
      'Excellent interior design expertise',
      '4.9 rating matches requirement',
      'Has strong references',
    ],
    status: 'proposed',
    createdAt: '2025-02-03',
  },
  {
    id: 'deal_2',
    jobId: 'job_1',
    sellerId: 'user_4',
    sellerAgent: dummyAgents[3],
    matchScore: 85,
    matchReasons: [
      'Price competitive ($20,000)',
      'Licensed and available',
      '4.5 rating matches requirement',
      'Bathroom & kitchen specialist',
      'No references on file',
    ],
    status: 'proposed',
    createdAt: '2025-02-03',
  },
  {
    id: 'deal_3',
    jobId: 'job_1',
    sellerId: 'user_5',
    sellerAgent: dummyAgents[4],
    matchScore: 92,
    matchReasons: [
      'Excellent 4.7 rating',
      '55 completed projects',
      'Licensed and certified',
      'Strong project management',
      'Available start date',
    ],
    status: 'proposed',
    createdAt: '2025-02-03',
  },
  {
    id: 'deal_4',
    jobId: 'job_2',
    sellerId: 'user_4',
    sellerAgent: dummyAgents[3],
    matchScore: 88,
    matchReasons: [
      'Bathroom specialist',
      'Price acceptable ($10,500)',
      'Licensed professional',
      'Available in timeframe',
      '4.5 rating sufficient',
    ],
    status: 'proposed',
    createdAt: '2025-02-03',
  },
  {
    id: 'deal_5',
    jobId: 'job_3',
    sellerId: 'user_2',
    sellerAgent: dummyAgents[1],
    matchScore: 94,
    matchReasons: [
      '4.6 rating meets requirement',
      'Licensed and certified',
      '42 completed projects',
      'Strong references',
      'Price within range ($6,200)',
    ],
    status: 'proposed',
    createdAt: '2025-02-02',
  },
  {
    id: 'deal_6',
    jobId: 'job_3',
    sellerId: 'user_3',
    sellerAgent: dummyAgents[2],
    matchScore: 90,
    matchReasons: [
      'Exceptional 4.9 rating',
      'Licensed and trusted',
      'Premium references',
      'High-quality finish',
      'Price premium ($6,800)',
    ],
    status: 'proposed',
    createdAt: '2025-02-02',
  },
];
