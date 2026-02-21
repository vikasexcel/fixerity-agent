'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { Job } from '@/lib/dummy-data';

interface CreateJobModalProps {
  onClose: () => void;
  onJobCreate: (job: Job) => void;
}

export function CreateJobModal({ onClose }: CreateJobModalProps) {
  const router = useRouter();
  const [prompt, setPrompt] = useState('');

  const launchConversation = () => {
    const text = prompt.trim();
    if (!text) return;
    onClose();
    router.push(`/buyer/unified-chat?q=${encodeURIComponent(text)}`);
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-card rounded-xl border border-border max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div className="flex justify-between items-center p-6 border-b border-border sticky top-0 bg-card">
          <h2 className="text-2xl font-bold text-foreground">Create New Job</h2>
          <button
            onClick={onClose}
            className="text-muted-foreground hover:text-foreground transition"
            aria-label="Close"
          >
            <X size={24} />
          </button>
        </div>

        <div className="p-6 space-y-4">
          <p className="text-sm text-muted-foreground">
            Tell us what you need in your own words. The agent will ask only relevant follow-up questions and build the full job post for you.
          </p>

          <textarea
            value={prompt}
            onChange={(e) => setPrompt(e.target.value)}
            rows={8}
            placeholder="Example: I need an architect for a new 2-story house, around 3,000 sq ft. Help me define the job post."
            className="w-full px-4 py-3 rounded-lg border border-border bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary resize-none"
          />

          <div className="flex gap-3 pt-2">
            <Button onClick={onClose} variant="outline" className="flex-1 bg-transparent">
              Cancel
            </Button>
            <Button
              onClick={launchConversation}
              className="flex-1 bg-primary hover:bg-primary/90 text-primary-foreground"
              disabled={!prompt.trim()}
            >
              Start AI Job Builder
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}
