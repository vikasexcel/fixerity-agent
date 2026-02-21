/**
 * Structured JSON logging for the provider profile flow.
 * Each log line is a single JSON object for easy parsing and filtering.
 */

function formatLog(scope, event, data) {
  const payload = {
    scope,
    event,
    ts: new Date().toISOString(),
    ...data,
  };
  return JSON.stringify(payload, null, 2);
}

export function logProviderProfile(event, data = {}) {
  console.log(formatLog('ProviderProfile', event, data));
}

export function logProviderConversation(event, data = {}) {
  console.log(formatLog('ProviderConversation', event, data));
}

export function logProviderTools(event, data = {}) {
  console.log(formatLog('ProviderTools', event, data));
}

export function logSellerAgent(event, data = {}) {
  console.log(formatLog('SellerAgent', event, data));
}
