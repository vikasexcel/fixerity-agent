'use client';

import React from "react"

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { login, signup } from '@/lib/auth-context';

type AuthMode = 'signin' | 'signup';
type UserRole = 'buyer' | 'seller';

export default function AuthPage() {
  const [mode, setMode] = useState<AuthMode>('signin');
  const [role, setRole] = useState<UserRole>('buyer');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [name, setName] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const router = useRouter();

  const handleAuth = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      if (mode === 'signin') {
        login(email, password, role);
      } else {
        signup(email, name, password, role);
      }

      // Redirect based on role
      if (role === 'buyer') {
        router.push('/buyer/dashboard');
      } else {
        router.push('/seller/dashboard');
      }
    } catch (error) {
      console.error('Auth error:', error);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-background flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        {/* Header */}
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-foreground mb-2">AgentMatch</h1>
          <p className="text-muted-foreground">Intelligent Agent Matching Platform</p>
        </div>

        {/* Role Selection */}
        <div className="bg-card rounded-lg p-6 border border-border mb-6">
          <div className="flex gap-3">
            <button
              onClick={() => setRole('buyer')}
              className={`flex-1 py-3 px-4 rounded-lg font-medium transition-all ${
                role === 'buyer'
                  ? 'bg-primary text-primary-foreground'
                  : 'bg-secondary text-secondary-foreground border border-border hover:bg-accent'
              }`}
            >
              Buyer Agent
            </button>
            <button
              onClick={() => setRole('seller')}
              className={`flex-1 py-3 px-4 rounded-lg font-medium transition-all ${
                role === 'seller'
                  ? 'bg-primary text-primary-foreground'
                  : 'bg-secondary text-secondary-foreground border border-border hover:bg-accent'
              }`}
            >
              Seller Agent
            </button>
          </div>
        </div>

        {/* Auth Form */}
        <form onSubmit={handleAuth} className="bg-card rounded-lg p-6 border border-border">
          <div className="space-y-4">
            {/* Sign Up Fields */}
            {mode === 'signup' && (
              <div>
                <label className="text-sm font-medium text-foreground block mb-2">Full Name</label>
                <Input
                  type="text"
                  placeholder="Your name"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  required
                  className="bg-input border-border"
                />
              </div>
            )}

            {/* Common Fields */}
            <div>
              <label className="text-sm font-medium text-foreground block mb-2">Email</label>
              <Input
                type="email"
                placeholder="your@email.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                className="bg-input border-border"
              />
            </div>

            <div>
              <label className="text-sm font-medium text-foreground block mb-2">Password</label>
              <Input
                type="password"
                placeholder="••••••••"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                className="bg-input border-border"
              />
            </div>

            {/* Submit Button */}
            <Button
              type="submit"
              disabled={isLoading}
              className="w-full bg-primary hover:bg-primary/90 text-primary-foreground font-medium py-2"
            >
              {isLoading
                ? 'Loading...'
                : mode === 'signin'
                  ? 'Sign In'
                  : 'Create Account'}
            </Button>
          </div>

          {/* Toggle Mode */}
          <div className="mt-6 text-center">
            <p className="text-sm text-muted-foreground">
              {mode === 'signin' ? "Don't have an account? " : 'Already have an account? '}
              <button
                type="button"
                onClick={() => setMode(mode === 'signin' ? 'signup' : 'signin')}
                className="text-primary hover:underline font-medium"
              >
                {mode === 'signin' ? 'Sign up' : 'Sign in'}
              </button>
            </p>
          </div>
        </form>

        {/* Footer Note */}
        <div className="mt-6 text-center text-xs text-muted-foreground">
          <p>Demo credentials:</p>
          <p className="mt-1">buyer@example.com / seller@example.com</p>
        </div>
      </div>
    </div>
  );
}
