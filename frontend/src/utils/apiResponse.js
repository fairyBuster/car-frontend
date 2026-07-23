const API_RESPONSE_SALT = 'KXXADFDFDF'

function tryParseJson(value) {
  if (typeof value !== 'string') {
    return value
  }

  try {
    return JSON.parse(value)
  } catch {
    return value
  }
}

function decodeBase64Utf8(value) {
  const binary = atob(value)
  const bytes = Uint8Array.from(binary, (char) => char.charCodeAt(0))

  return new TextDecoder().decode(bytes)
}

function decodeProtectedString(value) {
  if (typeof value !== 'string' || !value) {
    return null
  }

  const reversed = value.split('').reverse().join('')

  if (!reversed.endsWith(API_RESPONSE_SALT)) {
    return null
  }

  try {
    const encodedPayload = reversed.slice(0, -API_RESPONSE_SALT.length)
    return tryParseJson(decodeBase64Utf8(encodedPayload))
  } catch {
    return null
  }
}

function extractEncodedCandidate(value) {
  if (typeof value === 'string') {
    return value
  }

  if (!value || typeof value !== 'object') {
    return null
  }

  const candidateKeys = ['data', 'result', 'results', 'payload', 'response', 'content', 'encoded']

  for (const key of candidateKeys) {
    const candidate = extractEncodedCandidate(value[key])

    if (candidate) {
      return candidate
    }
  }

  const entries = Object.entries(value)

  if (entries.length === 1) {
    return extractEncodedCandidate(entries[0][1])
  }

  return null
}

function decodeApiValue(value) {
  const parsed = tryParseJson(value)

  if (!parsed || typeof parsed !== 'object') {
    return decodeProtectedString(parsed) ?? parsed
  }

  const encodedCandidate = extractEncodedCandidate(parsed)
  const decodedCandidate = decodeProtectedString(encodedCandidate)

  return decodedCandidate ?? parsed
}

export function resolveApiPayload(value) {
  const normalized = decodeApiValue(value)

  if (!normalized || typeof normalized !== 'object') {
    return normalized
  }

  if (typeof normalized.access === 'string') {
    return normalized
  }

  const candidateKeys = ['data', 'result', 'results', 'payload', 'response']

  for (const key of candidateKeys) {
    const candidate = normalized[key]

    if (candidate && typeof candidate === 'object' && !Array.isArray(candidate)) {
      const resolved = resolveApiPayload(candidate)

      if (resolved) {
        return resolved
      }
    }
  }

  return normalized
}

export async function readApiResponse(response) {
  const raw = await response.text()

  return {
    raw,
    data: resolveApiPayload(raw),
  }
}
