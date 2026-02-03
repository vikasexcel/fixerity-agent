'use client';

import React from "react"

import { useState } from 'react';
import { X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { Job, Priority } from '@/lib/dummy-data';

interface CreateJobModalProps {
  onClose: () => void;
  onJobCreate: (job: Job) => void;
}

export function CreateJobModal({ onClose, onJobCreate }: CreateJobModalProps) {
  const [step, setStep] = useState<'basic' | 'budget' | 'priorities'>('basic');
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    minBudget: '',
    maxBudget: '',
    startDate: '',
    endDate: '',
  });

  const [priorities, setPriorities] = useState<Priority[]>([
    {
      type: 'price',
      level: 'must_have',
      value: '',
      description: 'Price limit',
    },
    {
      type: 'licensed',
      level: 'must_have',
      description: 'Must be licensed',
    },
  ]);

  const handleBasicSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (formData.title && formData.description) {
      setStep('budget');
    }
  };

  const handleBudgetSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (formData.minBudget && formData.maxBudget) {
      setStep('priorities');
    }
  };

  const handlePriorityChange = (index: number, field: string, value: string) => {
    const updated = [...priorities];
    updated[index] = { ...updated[index], [field]: value };
    setPriorities(updated);
  };

  const addPriority = () => {
    setPriorities([
      ...priorities,
      {
        type: 'references',
        level: 'nice_to_have',
        description: 'New requirement',
      },
    ]);
  };

  const removePriority = (index: number) => {
    setPriorities(priorities.filter((_, i) => i !== index));
  };

  const handleCreateJob = (e: React.FormEvent) => {
    e.preventDefault();

    const newJob: Job = {
      id: `job_${Date.now()}`,
      buyerId: 'user_1',
      title: formData.title,
      description: formData.description,
      budget: {
        min: Number(formData.minBudget),
        max: Number(formData.maxBudget),
      },
      startDate: formData.startDate,
      endDate: formData.endDate,
      priorities: priorities.filter(p => p.type && p.level),
      createdAt: new Date().toISOString().split('T')[0],
      status: 'open',
      deadline: '8 days',
    };

    onJobCreate(newJob);
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-card rounded-xl border border-border max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex justify-between items-center p-6 border-b border-border sticky top-0 bg-card">
          <h2 className="text-2xl font-bold text-foreground">Create New Job</h2>
          <button
            onClick={onClose}
            className="text-muted-foreground hover:text-foreground transition"
          >
            <X size={24} />
          </button>
        </div>

        {/* Progress */}
        <div className="px-6 pt-6 pb-4 border-b border-border">
          <div className="flex gap-2">
            {(['basic', 'budget', 'priorities'] as const).map((s, i) => (
              <div key={s} className="flex items-center">
                <div
                  className={`w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition ${
                    step === s
                      ? 'bg-primary text-primary-foreground'
                      : ['basic', 'budget', 'priorities'].indexOf(step) > i
                        ? 'bg-accent text-accent-foreground'
                        : 'bg-muted text-muted-foreground'
                  }`}
                >
                  {i + 1}
                </div>
                {i < 2 && (
                  <div
                    className={`h-1 w-8 mx-2 transition ${
                      ['basic', 'budget', 'priorities'].indexOf(step) > i
                        ? 'bg-accent'
                        : 'bg-muted'
                    }`}
                  />
                )}
              </div>
            ))}
          </div>
          <div className="flex gap-8 mt-4 text-sm">
            <span className={step === 'basic' ? 'text-foreground font-semibold' : 'text-muted-foreground'}>
              Basic Info
            </span>
            <span className={step === 'budget' ? 'text-foreground font-semibold' : 'text-muted-foreground'}>
              Budget & Timeline
            </span>
            <span className={step === 'priorities' ? 'text-foreground font-semibold' : 'text-muted-foreground'}>
              Set Priorities
            </span>
          </div>
        </div>

        {/* Form Content */}
        <div className="p-6">
          {step === 'basic' && (
            <form onSubmit={handleBasicSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Job Title</label>
                <input
                  type="text"
                  required
                  placeholder="e.g., Kitchen Renovation with Modern Design"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  className="w-full px-4 py-2 rounded-lg border border-border bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Description</label>
                <textarea
                  required
                  placeholder="Describe your project requirements, scope, and expectations..."
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  rows={5}
                  className="w-full px-4 py-2 rounded-lg border border-border bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary resize-none"
                />
              </div>

              <div className="flex gap-3 pt-6">
                <Button
                  onClick={onClose}
                  variant="outline"
                  className="flex-1 bg-transparent"
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  className="flex-1 bg-primary hover:bg-primary/90 text-primary-foreground"
                >
                  Next: Budget & Timeline
                </Button>
              </div>
            </form>
          )}

          {step === 'budget' && (
            <form onSubmit={handleBudgetSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-foreground mb-2">Min Budget ($)</label>
                  <input
                    type="number"
                    required
                    placeholder="5000"
                    value={formData.minBudget}
                    onChange={(e) => setFormData({ ...formData, minBudget: e.target.value })}
                    className="w-full px-4 py-2 rounded-lg border border-border bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-foreground mb-2">Max Budget ($)</label>
                  <input
                    type="number"
                    required
                    placeholder="25000"
                    value={formData.maxBudget}
                    onChange={(e) => setFormData({ ...formData, maxBudget: e.target.value })}
                    className="w-full px-4 py-2 rounded-lg border border-border bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-foreground mb-2">Start Date</label>
                  <input
                    type="date"
                    required
                    value={formData.startDate}
                    onChange={(e) => setFormData({ ...formData, startDate: e.target.value })}
                    className="w-full px-4 py-2 rounded-lg border border-border bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-foreground mb-2">End Date</label>
                  <input
                    type="date"
                    required
                    value={formData.endDate}
                    onChange={(e) => setFormData({ ...formData, endDate: e.target.value })}
                    className="w-full px-4 py-2 rounded-lg border border-border bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
                  />
                </div>
              </div>

              <div className="flex gap-3 pt-6">
                <Button
                  type="button"
                  onClick={() => setStep('basic')}
                  variant="outline"
                  className="flex-1"
                >
                  Back
                </Button>
                <Button
                  type="submit"
                  className="flex-1 bg-primary hover:bg-primary/90 text-primary-foreground"
                >
                  Next: Set Priorities
                </Button>
              </div>
            </form>
          )}

          {step === 'priorities' && (
            <form onSubmit={handleCreateJob} className="space-y-4">
              <div className="bg-secondary/30 border border-secondary rounded-lg p-4 mb-4">
                <p className="text-sm text-foreground font-semibold mb-2">Set Priority Levels</p>
                <p className="text-xs text-muted-foreground">
                  Must Have: Non-negotiable requirements | Nice to Have: Preferred but flexible | Bonus: Great if present
                </p>
              </div>

              {priorities.map((priority, idx) => (
                <div key={idx} className="border border-border rounded-lg p-4 space-y-3 bg-background">
                  <div className="flex items-start justify-between">
                    <select
                      value={priority.type}
                      onChange={(e) => handlePriorityChange(idx, 'type', e.target.value)}
                      className="flex-1 px-3 py-2 rounded-lg border border-border bg-card text-foreground text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                      <option value="price">Price/Budget</option>
                      <option value="startDate">Start Date</option>
                      <option value="endDate">End Date</option>
                      <option value="rating">Minimum Rating</option>
                      <option value="jobsCompleted">Jobs Completed</option>
                      <option value="licensed">Licensed</option>
                      <option value="references">References</option>
                    </select>
                    {priorities.length > 2 && (
                      <button
                        type="button"
                        onClick={() => removePriority(idx)}
                        className="ml-2 text-destructive hover:text-destructive/80 text-sm font-semibold"
                      >
                        Remove
                      </button>
                    )}
                  </div>

                  <div className="grid grid-cols-2 gap-3">
                    <select
                      value={priority.level}
                      onChange={(e) => handlePriorityChange(idx, 'level', e.target.value)}
                      className="px-3 py-2 rounded-lg border border-border bg-card text-foreground text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                      <option value="must_have">Must Have</option>
                      <option value="nice_to_have">Nice to Have</option>
                      <option value="bonus">Bonus</option>
                    </select>

                    <input
                      type="text"
                      placeholder="Value (if applicable)"
                      value={priority.value || ''}
                      onChange={(e) => handlePriorityChange(idx, 'value', e.target.value)}
                      className="px-3 py-2 rounded-lg border border-border bg-background text-foreground placeholder:text-muted-foreground text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                  </div>

                  <input
                    type="text"
                    placeholder="Description (e.g., 'Must stay under $25,000')"
                    value={priority.description}
                    onChange={(e) => handlePriorityChange(idx, 'description', e.target.value)}
                    className="w-full px-3 py-2 rounded-lg border border-border bg-background text-foreground placeholder:text-muted-foreground text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                  />
                </div>
              ))}

              <Button
                type="button"
                onClick={addPriority}
                variant="outline"
                className="w-full bg-transparent"
              >
                + Add Another Priority
              </Button>

              <div className="flex gap-3 pt-6">
                <Button
                  type="button"
                  onClick={() => setStep('budget')}
                  variant="outline"
                  className="flex-1"
                >
                  Back
                </Button>
                <Button
                  type="submit"
                  className="flex-1 bg-accent hover:bg-accent/90 text-accent-foreground"
                >
                  Create Job & Run Agent
                </Button>
              </div>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
