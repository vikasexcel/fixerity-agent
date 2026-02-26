'use client';

import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import {
  customerLogin,
  customerRegister,
  providerLogin,
  providerRegister,
  type AuthLoginPayload,
  type CustomerRegisterPayload,
  type ProviderRegisterPayload,
} from './auth-api';

const STORAGE_KEY = 'agentmatch_session';

export type UserRole = 'buyer' | 'seller';

export type SessionUser = {
  role: UserRole;
  email: string;
  name: string;
  access_token: string;
  user_id?: number;
  provider_id?: number;
  [key: string]: unknown;
};

type Session = {
  isLoading: boolean;
  user: SessionUser | null;
};

type AuthContextValue = {
  session: Session;
  login: (
    email: string,
    password: string,
    role: UserRole,
    options?: Partial<AuthLoginPayload>
  ) => Promise<void>;
  signup: (
    email: string,
    name: string,
    password: string,
    role: UserRole,
    options?: Partial<CustomerRegisterPayload & ProviderRegisterPayload>
  ) => Promise<void>;
  logout: () => void;
};

function loadStoredSession(): SessionUser | null {
  if (typeof window === 'undefined') return null;
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return null;
    const data = JSON.parse(raw) as Record<string, unknown>;
    if (data?.role !== 'buyer' && data?.role !== 'seller') return null;
    return data as SessionUser;
  } catch {
    return null;
  }
}

function saveSession(user: SessionUser | null): void {
  if (typeof window === 'undefined') return;
  if (user) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(user));
  } else {
    localStorage.removeItem(STORAGE_KEY);
  }
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [session, setSession] = useState<Session>({
    isLoading: true,
    user: null,
  });

  useEffect(() => {
    const user = loadStoredSession();
    setSession({ isLoading: false, user });
  }, []);

  const login = useCallback(
    async (
      email: string,
      password: string,
      role: UserRole,
      options?: Partial<AuthLoginPayload>
    ) => {
      const payload = { email, password, ...options };
      if (role === 'buyer') {
        const result = await customerLogin(payload);
        const user: SessionUser = {
          ...result,
          role: 'buyer',
          email: result.email,
          name: result.user_name ?? '',
          access_token: result.access_token,
          user_id: result.user_id,
        };
        saveSession(user);
        setSession((s) => ({ ...s, user }));
      } else {
        const result = await providerLogin(payload);
        const user: SessionUser = {
          ...result,
          role: 'seller',
          email: result.email,
          name: result.provider_name ?? '',
          access_token: result.access_token,
          provider_id: result.provider_id,
        };
        saveSession(user);
        setSession((s) => ({ ...s, user }));
      }
    },
    []
  );

  const signup = useCallback(
    async (
      email: string,
      name: string,
      password: string,
      role: UserRole,
      options?: Partial<CustomerRegisterPayload & ProviderRegisterPayload>
    ) => {
      if (role === 'buyer') {
        const result = await customerRegister({
          email,
          password,
          full_name: name,
          contact_number: (options?.contact_number as string) ?? '',
          ...options,
        });
        const user: SessionUser = {
          ...result,
          role: 'buyer',
          email: result.email,
          name: result.user_name ?? name,
          access_token: result.access_token,
          user_id: result.user_id,
        };
        saveSession(user);
        setSession((s) => ({ ...s, user }));
      } else {
        const result = await providerRegister({
          email,
          password,
          full_name: name,
          contact_number: (options?.contact_number as string) ?? '',
          gender: (options?.gender as 1 | 2) ?? 1,
          ...options,
        });
        const user: SessionUser = {
          ...result,
          role: 'seller',
          email: result.email,
          name: result.provider_name ?? name,
          access_token: result.access_token,
          provider_id: result.provider_id,
        };
        saveSession(user);
        setSession((s) => ({ ...s, user }));
      }
    },
    []
  );

  const logout = useCallback(() => {
    saveSession(null);
    setSession((s) => ({ ...s, user: null }));
  }, []);

  const value: AuthContextValue = {
    session,
    login,
    signup,
    logout,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
