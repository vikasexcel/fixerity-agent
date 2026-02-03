import {
  customerLogin,
  customerRegister,
  providerLogin,
  providerRegister,
  type AuthLoginPayload,
  type CustomerRegisterPayload,
  type ProviderRegisterPayload,
} from './auth-api';

export interface AuthSession {
  user: {
    id: string;
    email: string;
    name: string;
    role: 'buyer' | 'seller';
    avatar?: string;
  } | null;
  isLoading: boolean;
}

const TOKEN_KEY = 'agentmatch_access_token';
const USER_KEY = 'agentmatch_user';

// In-memory storage for auth state
let currentSession: AuthSession = {
  user: null,
  isLoading: false,
};

function persistToken(token: string): void {
  if (typeof window !== 'undefined') {
    try {
      window.localStorage.setItem(TOKEN_KEY, token);
    } catch {
      // ignore
    }
  }
}

function persistUser(user: AuthSession['user']): void {
  if (typeof window !== 'undefined' && user) {
    try {
      window.localStorage.setItem(USER_KEY, JSON.stringify(user));
    } catch {
      // ignore
    }
  }
}

function clearPersisted(): void {
  if (typeof window !== 'undefined') {
    try {
      window.localStorage.removeItem(TOKEN_KEY);
      window.localStorage.removeItem(USER_KEY);
    } catch {
      // ignore
    }
  }
}

export function getAuthSession(): AuthSession {
  return currentSession;
}

export function setAuthSession(session: AuthSession): void {
  currentSession = session;
}

/**
 * Returns the stored access token for authenticated API calls (e.g. Authorization: Bearer &lt;token&gt;).
 */
export function getAccessToken(): string | null {
  if (typeof window !== 'undefined') {
    try {
      return window.localStorage.getItem(TOKEN_KEY);
    } catch {
      return null;
    }
  }
  return null;
}

/**
 * Login via Laravel API. Buyer → customer/login, Seller → on-demand/login.
 * Throws on API error (status !== 1 or network error); message is API message when available.
 */
export async function login(
  email: string,
  password: string,
  role: 'buyer' | 'seller',
  options?: { select_country_code?: string; select_currency?: string; select_language?: string }
): Promise<void> {
  const payload: AuthLoginPayload = {
    email,
    password,
    ...(options?.select_country_code != null && { select_country_code: options.select_country_code }),
    ...(options?.select_currency != null && { select_currency: options.select_currency }),
    ...(options?.select_language != null && { select_language: options.select_language }),
  };
  if (role === 'buyer') {
    const result = await customerLogin(payload);
    const user = {
      id: String(result.user_id),
      email: result.email ?? email,
      name: result.user_name ?? '',
      role: 'buyer' as const,
      avatar: undefined,
    };
    currentSession = { user, isLoading: false };
    persistToken(result.access_token);
    persistUser(user);
  } else {
    const result = await providerLogin(payload);
    const user = {
      id: String(result.provider_id),
      email: result.email ?? email,
      name: result.provider_name ?? '',
      role: 'seller' as const,
      avatar: undefined,
    };
    currentSession = { user, isLoading: false };
    persistToken(result.access_token);
    persistUser(user);
  }
}

/**
 * Signup via Laravel API. Buyer → customer/register, Seller → on-demand/register.
 * Throws on API error; message is API message when available.
 */
export async function signup(
  email: string,
  name: string,
  password: string,
  role: 'buyer' | 'seller',
  options: {
    contact_number: string;
    gender?: 1 | 2;
    select_country_code?: string;
    select_currency?: string;
    select_language?: string;
    refer_code?: string;
  }
): Promise<void> {
  if (role === 'buyer') {
    const payload: CustomerRegisterPayload = {
      email,
      password,
      full_name: name,
      contact_number: options.contact_number,
      ...(options.select_country_code != null && { select_country_code: options.select_country_code }),
      ...(options.select_currency != null && { select_currency: options.select_currency }),
      ...(options.select_language != null && { select_language: options.select_language }),
      ...(options.refer_code != null && options.refer_code !== '' && { refer_code: options.refer_code }),
    };
    const result = await customerRegister(payload);
    const user = {
      id: String(result.user_id),
      email: result.email ?? email,
      name: result.user_name ?? name,
      role: 'buyer' as const,
      avatar: undefined,
    };
    currentSession = { user, isLoading: false };
    persistToken(result.access_token);
    persistUser(user);
  } else {
    const gender = options.gender ?? 1;
    const payload: ProviderRegisterPayload = {
      email,
      password,
      full_name: name,
      contact_number: options.contact_number,
      gender,
      ...(options.select_country_code != null && { select_country_code: options.select_country_code }),
      ...(options.select_currency != null && { select_currency: options.select_currency }),
      ...(options.select_language != null && { select_language: options.select_language }),
    };
    const result = await providerRegister(payload);
    const user = {
      id: String(result.provider_id),
      email: result.email ?? email,
      name: result.provider_name ?? name,
      role: 'seller' as const,
      avatar: undefined,
    };
    currentSession = { user, isLoading: false };
    persistToken(result.access_token);
    persistUser(user);
  }
}

export function logout(): void {
  currentSession = {
    user: null,
    isLoading: false,
  };
  clearPersisted();
}
