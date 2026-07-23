import { readApiResponse } from '../utils/apiResponse'

function buildHeaders(headers = {}) {
  const accessToken = localStorage.getItem('auth_access_token')

  return {
    ...(accessToken ? { Authorization: `Bearer ${accessToken}` } : {}),
    ...headers,
  }
}

function buildUrl(path, query = {}) {
  const searchParams = new URLSearchParams()

  for (const [key, value] of Object.entries(query)) {
    if (value !== undefined && value !== null && value !== '') {
      searchParams.set(key, String(value))
    }
  }

  const queryString = searchParams.toString()
  return queryString ? `${path}?${queryString}` : path
}

async function request(path, options = {}) {
  const response = await fetch(path, options)
  const { data, raw } = await readApiResponse(response)

  return {
    ok: response.ok,
    status: response.status,
    data,
    raw,
  }
}

function post(path, body) {
  return request(path, {
    method: 'POST',
    headers: buildHeaders({
      'Content-Type': 'application/json',
    }),
    body: JSON.stringify(body),
  })
}

function get(path, query) {
  return request(buildUrl(path, query), {
    method: 'GET',
    headers: buildHeaders(),
  })
}

export function login(payload) {
  return post('/api/auth/jwt/login/', payload)
}

export function register(payload) {
  return post('/api/auth/register/', payload)
}

export function getProducts(query) {
  return get('/api/products/', query)
}

export function getAccountInfo() {
  return get('/api/auth/account-info/')
}

export function getBalanceStatistics(period = 'all-time') {
  return get(`/api/auth/balance-statistics/${period}/`)
}

export function getDownlineStats() {
  return get('/api/auth/downline-stats/')
}

export function getDepositTransactions(query) {
  return get('/api/deposits/transactions/', query)
}

export function initiateSiTransferHubDeposit(payload) {
  return post('/api/deposits/sitransferhub/initiate/', payload)
}

export function initiatePpayProsDeposit(payload) {
  return post('/api/deposits/ppaypros/initiate/', payload)
}

export function getWithdrawals(query) {
  return get('/api/withdraw/', query)
}

export function getTransactions(query) {
  return get('/api/transactions/', query)
}

export function getNews(query) {
  return get('/api/news/', query)
}

export function getInvestments(query) {
  return get('/api/investments/', query)
}

export function getInvestmentInterestTransactions(id, query) {
  return get(`/api/investments/${id}/interest-transactions/`, query)
}

export function getDownlineOverview(query) {
  return get('/api/auth/downline-overview/', query)
}

export function getBanks(query) {
  return get('/api/banks/', query)
}

export function getUserBanks() {
  return get('/api/banks/user/')
}

export function addUserBank(payload) {
  return post('/api/banks/user/', payload)
}

export function requestWithdrawal(payload) {
  return post('/api/withdraw/', payload)
}

export function purchaseProduct(payload) {
  return post('/api/products/purchase/', payload)
}

export function getSupportLink(id) {
  return get(`/api/support/links/${id}/`)
}
