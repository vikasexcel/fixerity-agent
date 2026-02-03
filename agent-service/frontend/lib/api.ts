/**
 * Base API client for Laravel backend.
 * POST to NEXT_PUBLIC_API_URL/api/{path} with JSON body and optional headers.
 */

const getBaseUrl = (): string => {
  const url = process.env.NEXT_PUBLIC_API_URL;
  if (!url) {
    throw new Error('NEXT_PUBLIC_API_URL is not set. Add it to .env.local (see .env.example).');
  }
  return url.replace(/\/$/, '');
};

export function getApiBaseUrl(): string {
  return getBaseUrl();
}

/**
 * POST to api/{path}. Sends JSON body. Optionally pass extra headers (e.g. select-time-zone for auth).
 */
export async function apiPost<T = unknown>(
  path: string,
  body: Record<string, unknown>,
  headers?: Record<string, string>
): Promise<T> {
  const base = getBaseUrl();
  const url = `${base}/api/${path.replace(/^\//, '')}`;
  const timeZone = typeof Intl !== 'undefined' && Intl.DateTimeFormat
    ? Intl.DateTimeFormat().resolvedOptions().timeZone
    : 'UTC';
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'select-time-zone': timeZone,
      ...headers,
    },
    body: JSON.stringify(body),
  });
  const data = (await res.json().catch(() => ({}))) as T & { status?: number; message?: string };
  if (!res.ok) {
    const message = (data as { message?: string }).message ?? res.statusText;
    throw new Error(message);
  }
  return data as T;
}
