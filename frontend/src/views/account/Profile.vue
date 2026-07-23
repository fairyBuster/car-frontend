<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import AppShell from '../../components/layout/AppShell.vue'
import {
  backLogoutHeaderActions,
  marketNoticeItems,
  standardFooterItems,
} from '../../data/mockData'
import { getAccountInfo, getBalanceStatistics, getSupportLink } from '../../services/api'
import {
  cleanSupportLinkUrl,
  normalizeSupportLink,
  SUPPORT_LINK_ID,
} from '../../utils/supportLink'

const router = useRouter()
const isLoading = ref(false)
const loadError = ref('')
const supportHref = ref('')
const state = ref({
  avatarLetter: 'U',
  memberLabel: 'User',
  inviteCode: '-',
  phone: '-',
  email: '-',
  income: '0.00',
  depositBalance: '0.00',
  totalCommission: '0.00',
  walletBalance: '0.00',
  teamMemberCount: 0,
  activeProductCount: 0,
  totalTransactions: 0,
})

function formatAmount(value) {
  const normalized = typeof value === 'string' ? value.replace(/,/g, '').trim() : value
  const amount = Number(normalized)

  if (!Number.isFinite(amount)) {
    return '0'
  }

  return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(amount)
}

function buildAvatarLetter(account) {
  const source = account?.username || account?.phone || account?.email || 'User'
  return source.trim().charAt(0).toUpperCase() || 'U'
}

function buildRevokedToken(prefix) {
  return `${prefix}_revoked_${Date.now()}_${Math.random().toString(36).slice(2, 10)}`
}

function clearAuthStorage() {
  localStorage.setItem('auth_access_token', buildRevokedToken('access'))
  localStorage.setItem('auth_refresh_token', buildRevokedToken('refresh'))
  localStorage.removeItem('auth_access_token')
  localStorage.removeItem('auth_refresh_token')
  localStorage.removeItem('auth_user')
  localStorage.removeItem('auth_phone')
  localStorage.removeItem('selected_withdraw_bank_id')
  sessionStorage.clear()
}

const resolvedSupportHref = computed(() => supportHref.value || '#')

async function loadSupportHref() {
  try {
    const { ok, data } = await getSupportLink(SUPPORT_LINK_ID)

    if (!ok) {
      supportHref.value = ''
      return
    }

    supportHref.value = cleanSupportLinkUrl(normalizeSupportLink(data)?.url)
  } catch {
    supportHref.value = ''
  }
}

async function loadProfile() {
  isLoading.value = true
  loadError.value = ''

  const [accountResult, balanceResult] = await Promise.allSettled([
    getAccountInfo(),
    getBalanceStatistics('all-time'),
  ])

  const accountData =
    accountResult.status === 'fulfilled' && accountResult.value.ok ? accountResult.value.data : null
  const balanceData =
    balanceResult.status === 'fulfilled' && balanceResult.value.ok ? balanceResult.value.data : null

  if (!accountData && !balanceData) {
    loadError.value = 'Data profil gagal dimuat.'
    isLoading.value = false
    return
  }

  state.value = {
    avatarLetter: buildAvatarLetter(accountData),
    memberLabel: 'User',
    inviteCode: accountData?.referral_code || '-',
    phone: accountData?.phone || '-',
    email: accountData?.email || '-',
    income: formatAmount(balanceData?.total_income),
    depositBalance: formatAmount(balanceData?.balance_deposit),
    totalCommission: formatAmount(balanceData?.total_commission),
    walletBalance: formatAmount(balanceData?.balance ?? accountData?.balance),
    teamMemberCount: Number(balanceData?.active_members_total_1_3) || 0,
    activeProductCount:
      Number(balanceData?.active_investments_count) || Number(accountData?.active_investments_count) || 0,
    totalTransactions: Number(balanceData?.total_withdraw_completed) || 0,
  }

  isLoading.value = false
}

async function logout() {
  clearAuthStorage()
  const loginHref = router.resolve({ name: 'login' }).href
  await router.replace({ name: 'login' })
  window.location.replace(loginHref)
}

function onHeaderAction(actionKey) {
  if (actionKey === 'logout') {
    logout()
  }
}

onMounted(() => {
  loadProfile()
  loadSupportHref()
})
</script>

<template>
  <AppShell
    body-class="profile-page-body"
    main-class="profile-main"
    :header-actions="backLogoutHeaderActions"
    :notice-items="marketNoticeItems"
    :footer-items="standardFooterItems"
    active-footer-key="profile"
    footer-class="profile-footer"
    :footer-meta="{ label: 'Kode Undangan', value: state.inviteCode }"
    @action="onHeaderAction"
  >
    <section class="profile-shell">
      <section v-if="loadError" class="deposit-alert is-error">
        <p>{{ loadError }}</p>
      </section>

      <div class="profile-top-card">
        <div class="profile-top-head">
          <div class="profile-avatar">{{ state.avatarLetter }}</div>

          <div class="profile-identity">
            <div class="profile-badge-row">
              <span class="profile-member-badge">{{ state.memberLabel }}</span>
              <span class="profile-id-badge">{{ state.inviteCode }}</span>
            </div>
            <h1>{{ state.phone }}</h1>
            <p>{{ state.email }}</p>
          </div>
        </div>

        <div class="profile-stats-row">
          <div class="profile-stat-card">
            <strong>Rp {{ state.income }}</strong>
            <span>Pendapatan saya</span>
          </div>
          <div class="profile-stat-card">
            <strong>Rp {{ state.depositBalance }}</strong>
            <span>Saldo deposit</span>
          </div>
          <div class="profile-stat-card">
            <strong>Rp {{ state.totalCommission }}</strong>
            <span>Total komisi</span>
          </div>
        </div>

        <div class="profile-balance-bar">
          <div class="profile-balance-copy">
            <span>Saldo BALANCE</span>
            <strong>Rp{{ state.walletBalance }}</strong>
          </div>
          <div class="profile-balance-actions">
            <RouterLink to="/m/pages/addbalance" class="profile-balance-btn is-primary">Deposit</RouterLink>
            <RouterLink to="/m/pages/settle" class="profile-balance-btn is-secondary">Withdraw</RouterLink>
          </div>
        </div>
      </div>

      <section class="profile-module">
        <div class="profile-module-title"><span>Fund Management</span></div>
        <div class="profile-module-grid is-six">
          <RouterLink to="/m/pages/addbalance" class="profile-module-item"><span class="profile-module-icon"><svg viewBox="0 0 24 24"><rect x="4" y="14" width="16" height="5.5" rx="1.6"></rect><path d="M12 4v10"></path><path d="M8.5 10.5L12 14l3.5-3.5"></path></svg></span><span>Deposit</span></RouterLink>
          <RouterLink to="/m/pages/settle" class="profile-module-item"><span class="profile-module-icon"><svg viewBox="0 0 24 24"><rect x="4" y="14" width="16" height="5.5" rx="1.6"></rect><path d="M12 19V9"></path><path d="M8.5 12.5L12 9l3.5 3.5"></path></svg></span><span>Withdraw</span></RouterLink>
          <RouterLink to="/m/pages/history/income" class="profile-module-item"><span class="profile-module-icon"><svg viewBox="0 0 24 24"><path d="M6 16.5h12"></path><path d="M8.5 13.5l2 2 5-5"></path><path d="M5 5.5h14v13H5z"></path></svg></span><span>Riwayat keuntungan</span></RouterLink>
          <RouterLink to="/m/pages/history/commision" class="profile-module-item"><span class="profile-module-icon"><svg viewBox="0 0 24 24"><path d="M4 8h16l-1.5 3H5.5z"></path><path d="M6 11v6h12v-6"></path><path d="M9 14c1.1-1 2.1-1 3.2 0 1.1 1 2.1 1 3.1 0"></path></svg></span><span>Riwayat komisi</span></RouterLink>
          <RouterLink to="/m/pages/history/others" class="profile-module-item"><span class="profile-module-icon"><svg viewBox="0 0 24 24"><path d="M4.5 8.5h15l-1.5 10h-12z"></path><path d="M9 8.5a3 3 0 0 1 6 0"></path><path d="M9.5 12.5h5"></path></svg></span><span>Riwayat lainnya</span></RouterLink>
          <RouterLink to="/m/pages/mycargo" class="profile-module-item"><span class="profile-module-icon"><svg viewBox="0 0 24 24"><path d="M5 14h14l-1.4 4H6.4z"></path><path d="M7.5 11.5c1.2-1 2.4-1 3.6 0 1.1 1 2.2 1 3.4 0"></path><circle cx="9" cy="8.1" r="1"></circle><circle cx="12.5" cy="6.8" r="1"></circle><circle cx="16" cy="8.2" r="1"></circle></svg></span><span>Milik saya</span></RouterLink>
        </div>
      </section>

      <section class="profile-module">
        <div class="profile-module-title"><span>Account Status</span></div>
        <div class="profile-summary-grid">
          <div class="profile-summary-card"><strong>{{ state.inviteCode }}</strong><span>Kode undangan</span></div>
          <div class="profile-summary-card"><strong>{{ state.teamMemberCount }}</strong><span>Tim aktif 1-3</span></div>
          <div class="profile-summary-card"><strong>{{ state.activeProductCount }}</strong><span>Produk aktif</span></div>
          <div class="profile-summary-card"><strong>{{ state.totalTransactions }}</strong><span>Total penarikan</span></div>
        </div>
      </section>

      <section class="profile-module">
        <div class="profile-module-title"><span>Security Service</span></div>
        <div class="profile-module-grid is-four">
          <RouterLink to="/m/pages/policy" class="profile-module-item"><span class="profile-module-icon"><svg viewBox="0 0 24 24"><path d="M12 3.5l7 3v5.2c0 4.1-2.8 7.8-7 8.8-4.2-1-7-4.7-7-8.8V6.5z"></path><path d="M9.5 12l1.7 1.7 3.3-3.7"></path></svg></span><span>Security</span></RouterLink>
          <RouterLink to="/m/pages/company" class="profile-module-item"><span class="profile-module-icon"><svg viewBox="0 0 24 24"><path d="M5 19h14"></path><path d="M7.5 19V8.5"></path><path d="M12 19V5"></path><path d="M16.5 19v-7"></path><path d="M5 8.5h5"></path><path d="M12 5h4.5"></path></svg></span><span>Company</span></RouterLink>
          <RouterLink to="/m/pages/myteam" class="profile-module-item"><span class="profile-module-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"></circle><circle cx="6.8" cy="10.2" r="2.1"></circle><circle cx="17.2" cy="10.2" r="2.1"></circle><path d="M8 18.5c0-2.1 1.8-3.9 4-3.9s4 1.8 4 3.9"></path><path d="M3.8 18.5c0-1.6 1.4-2.9 3-2.9"></path><path d="M20.2 18.5c0-1.6-1.4-2.9-3-2.9"></path></svg></span><span>Team</span></RouterLink>
          <a :href="resolvedSupportHref" target="_blank" rel="noopener noreferrer" class="profile-module-item"><span class="profile-module-icon"><svg viewBox="0 0 24 24"><path d="M21 5L3.8 11.6l5.2 2.1L18.2 7 11 14.6V19l3.2-3.1 4.4 2.9L21 5z"></path></svg></span><span>Support</span></a>
        </div>
        <button type="button" class="profile-safe-logout" @click="logout">Secure Log Out</button>
      </section>
    </section>
  </AppShell>
</template>
