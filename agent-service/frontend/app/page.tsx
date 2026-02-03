'use client';

import { useRouter } from 'next/navigation';
import { Target, Star, Zap, User, Wrench, Sparkles } from 'lucide-react';
import { Button } from '@/components/ui/button';

export default function Home() {
  const router = useRouter();

  return (
    <div className="min-h-screen bg-background text-foreground">
      {/* Header */}
      <header className="sticky top-0 bg-card border-b border-border z-40">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex justify-between items-center">
            <div className="text-2xl font-bold text-primary">AgentMatch</div>
            <Button
              onClick={() => router.push('/auth')}
              className="bg-primary hover:bg-primary/90 text-primary-foreground"
            >
              Get Started
            </Button>
          </div>
        </div>
      </header>

      {/* Hero Section */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
        <div className="text-center mb-12">
          <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold mb-4 text-pretty">
            Intelligent Agent Matching Platform
          </h1>
          <p className="text-lg sm:text-xl text-muted-foreground mb-8 max-w-2xl mx-auto text-balance">
            Connect qualified agents with the perfect opportunities using AI-powered priority matching.
            Get your top 5 matches in seconds.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Button
              onClick={() => router.push('/auth')}
              className="bg-primary hover:bg-primary/90 text-primary-foreground text-lg px-8 py-6"
            >
              Start as Buyer
            </Button>
            <Button
              onClick={() => router.push('/auth')}
              variant="outline"
              className="text-lg px-8 py-6 border-border hover:bg-secondary"
            >
              Start as Seller
            </Button>
          </div>
        </div>

        {/* Features Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mt-16">
          {/* Feature 1 */}
          <div className="bg-card border border-border rounded-lg p-8">
            <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
              <Target size={20} />
            </div>
            <h3 className="text-xl font-bold mb-2">Priority-Based Matching</h3>
            <p className="text-muted-foreground">
              Buyers set priorities (Must Have, Nice to Have, Bonus) to get agents perfectly aligned with their needs.
            </p>
          </div>

          {/* Feature 2 */}
          <div className="bg-card border border-border rounded-lg p-8">
            <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
              <Star size={20} />
            </div>
            <h3 className="text-xl font-bold mb-2">Smart Rating System</h3>
            <p className="text-muted-foreground">
              Agents are matched based on their rating, experience, licensing, and references. Quality guaranteed.
            </p>
          </div>

          {/* Feature 3 */}
          <div className="bg-card border border-border rounded-lg p-8">
            <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
              <Zap size={20} />
            </div>
            <h3 className="text-xl font-bold mb-2">Instant Matches</h3>
            <p className="text-muted-foreground">
              Get your top 5 matched agents instantly. No manual search required. Just set priorities and go.
            </p>
          </div>
        </div>

        {/* How It Works */}
        <div className="mt-24">
          <h2 className="text-3xl sm:text-4xl font-bold text-center mb-12">How It Works</h2>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-12">
            {/* Buyer Flow */}
            <div>
              <h3 className="text-xl font-bold mb-6 flex items-center gap-2">
                <span className="bg-primary text-primary-foreground w-8 h-8 rounded-full flex items-center justify-center">
                  <User size={18} />
                </span>
                For Buyers
              </h3>
              <div className="space-y-4">
                {[
                  { step: '1', title: 'Post a Job', desc: 'Describe what you need and your budget' },
                  {
                    step: '2',
                    title: 'Set Priorities',
                    desc: 'Choose must-haves, nice-to-haves, and bonuses',
                  },
                  {
                    step: '3',
                    title: 'Receive Matches',
                    desc: 'Get top 5 agents matched to your requirements',
                  },
                  {
                    step: '4',
                    title: 'Review & Connect',
                    desc: 'Compare profiles and contact your favorite agent',
                  },
                ].map((item) => (
                  <div key={item.step} className="flex gap-4">
                    <div className="bg-primary text-primary-foreground w-10 h-10 rounded-lg flex items-center justify-center font-bold flex-shrink-0">
                      {item.step}
                    </div>
                    <div>
                      <p className="font-semibold text-foreground">{item.title}</p>
                      <p className="text-sm text-muted-foreground">{item.desc}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Seller Flow */}
            <div>
              <h3 className="text-xl font-bold mb-6 flex items-center gap-2">
                <span className="bg-accent text-accent-foreground w-8 h-8 rounded-full flex items-center justify-center">
                  <Wrench size={18} />
                </span>
                For Sellers
              </h3>
              <div className="space-y-4">
                {[
                  { step: '1', title: 'Create Profile', desc: 'Showcase your skills and experience' },
                  { step: '2', title: 'Set Your Rate', desc: 'Define your hourly or project rates' },
                  {
                    step: '3',
                    title: 'Get Matched',
                    desc: 'See jobs that fit your profile automatically',
                  },
                  {
                    step: '4',
                    title: 'Contact Buyers',
                    desc: 'Reach out to interested project owners',
                  },
                ].map((item) => (
                  <div key={item.step} className="flex gap-4">
                    <div className="bg-accent text-accent-foreground w-10 h-10 rounded-lg flex items-center justify-center font-bold flex-shrink-0">
                      {item.step}
                    </div>
                    <div>
                      <p className="font-semibold text-foreground">{item.title}</p>
                      <p className="text-sm text-muted-foreground">{item.desc}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* Example Requirements */}
        <div className="mt-24 bg-card border border-border rounded-lg p-8">
          <h2 className="text-2xl font-bold mb-6">Example: Job Requirements Setup</h2>
          <div className="bg-secondary/30 rounded-lg p-6">
            <p className="font-semibold text-foreground mb-4">Kitchen Renovation Project</p>
            <div className="space-y-3 text-sm">
              <div className="flex gap-3 items-start">
                <span className="text-primary mt-0.5 flex-shrink-0"><Star size={16} /></span>
                <div>
                  <p className="font-medium text-foreground">MUST HAVE: Price less than $25,000</p>
                  <p className="text-xs text-muted-foreground">Agent must be able to complete within budget</p>
                </div>
              </div>
              <div className="flex gap-3 items-start">
                <span className="text-primary mt-0.5 flex-shrink-0"><Star size={16} /></span>
                <div>
                  <p className="font-medium text-foreground">MUST HAVE: Start by Feb 15, finish by Mar 30</p>
                  <p className="text-xs text-muted-foreground">Timeline must be achievable for project</p>
                </div>
              </div>
              <div className="flex gap-3 items-start">
                <span className="text-primary mt-0.5 flex-shrink-0"><Star size={16} /></span>
                <div>
                  <p className="font-medium text-foreground">MUST HAVE: Licensed professional</p>
                  <p className="text-xs text-muted-foreground">Required for contractor work</p>
                </div>
              </div>
              <div className="flex gap-3 items-start">
                <span className="text-accent mt-0.5 flex-shrink-0"><Sparkles size={16} /></span>
                <div>
                  <p className="font-medium text-foreground">NICE TO HAVE: Rating 4.5 or higher</p>
                  <p className="text-xs text-muted-foreground">Quality indicator from past work</p>
                </div>
              </div>
              <div className="flex gap-3 items-start">
                <span className="text-primary mt-0.5 flex-shrink-0"><Target size={16} /></span>
                <div>
                  <p className="font-medium text-foreground">BONUS: 30+ completed projects</p>
                  <p className="text-xs text-muted-foreground">Significant experience is a bonus</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="bg-card border-t border-border py-16 sm:py-24">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-3xl sm:text-4xl font-bold mb-4">Ready to Get Started?</h2>
          <p className="text-lg text-muted-foreground mb-8">
            Join thousands of agents making better matches every day.
          </p>
          <Button
            onClick={() => router.push('/auth')}
            className="bg-primary hover:bg-primary/90 text-primary-foreground text-lg px-8 py-6"
          >
            Get Started Now
          </Button>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-secondary border-t border-border py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center">
            <p className="text-muted-foreground text-sm">© 2025 AgentMatch. All rights reserved.</p>
            <div className="flex gap-6 text-sm text-muted-foreground">
              <a href="#" className="hover:text-foreground">
                Privacy
              </a>
              <a href="#" className="hover:text-foreground">
                Terms
              </a>
              <a href="#" className="hover:text-foreground">
                Contact
              </a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
}
