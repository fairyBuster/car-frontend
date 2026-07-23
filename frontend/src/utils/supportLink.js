export const SUPPORT_LINK_ID = 9

export function normalizeSupportLink(payload) {
  if (Array.isArray(payload)) {
    return payload[0] ?? null
  }

  if (!payload || typeof payload !== 'object') {
    return null
  }

  if (Array.isArray(payload.results)) {
    return payload.results[0] ?? null
  }

  return payload
}

export function cleanSupportLinkUrl(value) {
  const raw = String(value || '').trim()

  if (!raw) {
    return ''
  }

  return raw.replace(/[`'"]/g, '').trim()
}

export function formatSupportPlatformLabel(value) {
  const label = String(value || '').trim()

  if (!label) {
    return 'Support'
  }

  return label.charAt(0).toUpperCase() + label.slice(1)
}
