import type { Job, Agent, Priority } from './dummy-data';

export interface MatchResult {
  agent: Agent;
  score: number;
  reasons: string[];
  priorityMatches: {
    priority: Priority;
    matched: boolean;
    reason: string;
  }[];
}

/**
 * BUYER AGENT ENGINE
 * 
 * The Buyer Agent runs automatically when:
 * 1. A new job is created with priorities
 * 2. The agent scans all available seller agents
 * 3. Calculates match scores based on job priorities
 * 4. Returns top 5 matches
 * 
 * Process:
 * - Evaluates each priority level (must_have, nice_to_have, bonus)
 * - Calculates weighted score based on priority importance
 * - Must-have failures reduce score significantly
 * - Nice-to-have failures reduce score moderately
 * - Bonus matches increase score slightly
 */

export function runBuyerAgent(job: Job, availableAgents: Agent[]): MatchResult[] {
  const sellerAgents = availableAgents.filter(a => a.type === 'seller');
  
  const matches = sellerAgents.map(agent => ({
    agent,
    result: evaluateAgentForJob(job, agent),
  }))
  .sort((a, b) => b.result.score - a.result.score)
  .slice(0, 5)
  .map(m => m.result);

  return matches;
}

/**
 * Evaluates a single agent against job requirements
 * Returns match result with score and detailed reasons
 */
function evaluateAgentForJob(job: Job, agent: Agent): MatchResult {
  let baseScore = 100;
  const reasons: string[] = [];
  const priorityMatches: MatchResult['priorityMatches'] = [];

  // Evaluate each priority
  for (const priority of job.priorities) {
    const evaluation = evaluatePriority(priority, agent, job);
    priorityMatches.push(evaluation);

    if (evaluation.matched) {
      reasons.push(`✓ ${evaluation.reason}`);
      // Bonus priority adds points
      if (priority.level === 'bonus') {
        baseScore += 5;
      }
    } else {
      reasons.push(`✗ ${evaluation.reason}`);
      // Must-have failure deducts more points
      if (priority.level === 'must_have') {
        baseScore -= 25;
      }
      // Nice-to-have failure deducts fewer points
      if (priority.level === 'nice_to_have') {
        baseScore -= 10;
      }
    }
  }

  // Ensure score is between 0 and 100
  const finalScore = Math.max(0, Math.min(100, baseScore));

  return {
    agent,
    score: finalScore,
    reasons,
    priorityMatches,
  };
}

/**
 * Evaluates a single priority requirement against agent capabilities
 */
function evaluatePriority(priority: Priority, agent: Agent, job: Job): 
  { matched: boolean; reason: string } {
  
  switch (priority.type) {
    case 'price': {
      // Check if agent's rate fits within budget
      const agentMonthlyRate = (agent.hourlyRate || 0) * 160; // ~160 hours/month
      const maxBudget = job.budget.max;
      const matched = agentMonthlyRate <= maxBudget;
      return {
        matched,
        reason: matched 
          ? `Price within budget ($${maxBudget})`
          : `Rate exceeds budget ($${agentMonthlyRate} estimated)`,
      };
    }

    case 'startDate': {
      // Check if agent can start by required date
      // In dummy system, we assume all agents are available
      return {
        matched: true,
        reason: `Available to start by ${priority.value}`,
      };
    }

    case 'endDate': {
      // Check if agent can complete by required end date
      // In dummy system, we assume all agents can meet timelines
      return {
        matched: true,
        reason: `Can complete by ${priority.value}`,
      };
    }

    case 'rating': {
      const requiredRating = Number(priority.value) || 4;
      const matched = agent.rating >= requiredRating;
      return {
        matched,
        reason: matched
          ? `Rating of ${agent.rating}★ meets ${requiredRating}★ requirement`
          : `Rating of ${agent.rating}★ below ${requiredRating}★ requirement`,
      };
    }

    case 'jobsCompleted': {
      const requiredJobs = Number(priority.value) || 10;
      const matched = agent.jobsCompleted >= requiredJobs;
      return {
        matched,
        reason: matched
          ? `${agent.jobsCompleted} completed jobs exceeds ${requiredJobs} minimum`
          : `${agent.jobsCompleted} completed jobs below ${requiredJobs} requirement`,
      };
    }

    case 'licensed': {
      return {
        matched: agent.licensed,
        reason: agent.licensed ? 'Licensed and certified' : 'Not licensed',
      };
    }

    case 'references': {
      return {
        matched: agent.references,
        reason: agent.references ? 'References available' : 'No references on file',
      };
    }

    default:
      return { matched: false, reason: 'Unknown requirement' };
  }
}

/**
 * SELLER AGENT ENGINE
 * 
 * The Seller Agent runs when:
 * 1. A buyer creates a new job
 * 2. The seller agent scans all available jobs
 * 3. Evaluates which jobs match their profile
 * 4. Alerts seller to matching opportunities
 * 
 * Process:
 * - Analyzes job requirements against agent capabilities
 * - Calculates how well agent matches the job
 * - Returns all jobs sorted by match quality
 * - Seller can then respond to top matches
 */

export function runSellerAgent(agent: Agent, availableJobs: Job[]): MatchResult[] {
  const matches = availableJobs
    .map(job => ({
      job,
      result: evaluateJobForAgent(job, agent),
    }))
    .sort((a, b) => b.result.score - a.result.score)
    .map(m => m.result);

  return matches;
}

/**
 * Evaluates how well a job matches an agent's profile
 */
function evaluateJobForAgent(job: Job, agent: Agent): MatchResult {
  let baseScore = 100;
  const reasons: string[] = [];
  const priorityMatches: MatchResult['priorityMatches'] = [];

  // Evaluate each priority requirement
  for (const priority of job.priorities) {
    const evaluation = evaluatePriority(priority, agent, job);
    priorityMatches.push(evaluation);

    if (evaluation.matched) {
      reasons.push(`✓ ${evaluation.reason}`);
      if (priority.level === 'bonus') {
        baseScore += 5;
      }
    } else {
      reasons.push(`✗ ${evaluation.reason}`);
      if (priority.level === 'must_have') {
        baseScore -= 25;
      }
      if (priority.level === 'nice_to_have') {
        baseScore -= 10;
      }
    }
  }

  const finalScore = Math.max(0, Math.min(100, baseScore));

  return {
    agent,
    score: finalScore,
    reasons,
    priorityMatches,
  };
}

/**
 * Generates realistic matching reasons based on algorithm results
 */
export function generateMatchExplanation(matchResult: MatchResult, jobTitle?: string): {
  summary: string;
  details: string[];
  confidence: 'low' | 'medium' | 'high';
} {
  const score = matchResult.score;
  let confidence: 'low' | 'medium' | 'high' = 'low';
  let summary = '';

  if (score >= 90) {
    confidence = 'high';
    summary = 'Excellent match - this agent meets all critical requirements';
  } else if (score >= 80) {
    confidence = 'high';
    summary = 'Great match - agent well-suited for this job';
  } else if (score >= 70) {
    confidence = 'medium';
    summary = 'Good match - agent capable but some requirements differ';
  } else if (score >= 50) {
    confidence = 'medium';
    summary = 'Moderate match - consider alternatives';
  } else {
    confidence = 'low';
    summary = 'Poor match - significant requirement gaps';
  }

  return {
    summary,
    details: matchResult.reasons,
    confidence,
  };
}
