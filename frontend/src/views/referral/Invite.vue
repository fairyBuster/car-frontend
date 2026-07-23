<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import AppShell from '../../components/layout/AppShell.vue'
import {
  backProfileHeaderActions,
  marketNoticeItems,
  standardFooterItems,
} from '../../data/mockData'
import { getAccountInfo, getDownlineStats } from '../../services/api'

const copiedValue = ref('')
const isLoading = ref(false)
const loadError = ref('')
const state = ref({
  qrUrl: '',
  inviteCode: '-',
  inviteUrl: '',
  memberCount: 0,
  activeCount: 0,
  totalTeamCommission: '0.00',
})

function formatAmount(value) {
  const amount = Number(value)
  return Number.isFinite(amount) ? amount.toFixed(2) : '0.00'
}

function buildInviteUrl(referralCode) {
  if (!referralCode) {
    return ''
  }

  return `https://car-gowize.com/m/pages/register/${encodeURIComponent(referralCode)}`
}

function buildQrUrl(inviteUrl) {
  if (!inviteUrl) {
    return ''
  }

  const qrParams = new URLSearchParams({
    size: '220x220',
    data: inviteUrl,
  })

  return `https://api.qrserver.com/v1/create-qr-code/?${qrParams.toString()}`
}

function getLevelOneToThree(levels) {
  if (!Array.isArray(levels)) {
    return []
  }

  return levels.filter((item) => Number(item?.level) >= 1 && Number(item?.level) <= 3)
}

function sumLevelMetric(levels, fieldName) {
  return getLevelOneToThree(levels).reduce((total, item) => total + (Number(item?.[fieldName]) || 0), 0)
}

function sumTeamCommission(levels) {
  return getLevelOneToThree(levels).reduce((total, item) => {
    const profitCommission = Number(item?.profit_commission_amount) || 0
    const purchaseCommission = Number(item?.purchase_commission_amount) || 0
    return total + profitCommission + purchaseCommission
  }, 0)
}

async function loadReferralData() {
  isLoading.value = true
  loadError.value = ''

  try {
    const [accountInfoResponse, downlineStatsResponse] = await Promise.all([
      getAccountInfo(),
      getDownlineStats(),
    ])

    if (!accountInfoResponse.ok || !accountInfoResponse.data || typeof accountInfoResponse.data !== 'object') {
      loadError.value = 'Data referral gagal dimuat.'
      return
    }

    if (
      !downlineStatsResponse.ok ||
      !downlineStatsResponse.data ||
      typeof downlineStatsResponse.data !== 'object'
    ) {
      loadError.value = 'Statistik downline gagal dimuat.'
      return
    }

    const inviteUrl = buildInviteUrl(accountInfoResponse.data.referral_code)
    const levels = downlineStatsResponse.data.levels

    state.value = {
      qrUrl: buildQrUrl(inviteUrl),
      inviteCode: accountInfoResponse.data.referral_code || '-',
      inviteUrl,
      memberCount: sumLevelMetric(levels, 'members_total'),
      activeCount: sumLevelMetric(levels, 'members_active'),
      totalTeamCommission: formatAmount(sumTeamCommission(levels)),
    }
  } catch (error) {
    loadError.value =
      error instanceof Error ? error.message : 'Tidak bisa terhubung ke server account info.'
  } finally {
    isLoading.value = false
  }
}

async function copyValue(value) {
  if (!value) {
    return
  }

  try {
    await navigator.clipboard.writeText(value)
    copiedValue.value = value
    window.setTimeout(() => {
      copiedValue.value = ''
    }, 1400)
  } catch {
    copiedValue.value = ''
  }
}

onMounted(() => {
  loadReferralData()
})
</script>

<template>
  <AppShell
    body-class="invite-page-body"
    main-class="invite-main"
    :header-actions="backProfileHeaderActions"
    :notice-items="marketNoticeItems"
    :footer-items="standardFooterItems"
    active-footer-key="invite"
  >
    <section class="invite-hero">
      <div class="invite-copy">
        <span class="invite-pill">Invite Friends</span>
        <h1>Share Your Car-gowise Link</h1>
        <p>Friends can scan the QR code or use your invite code to register directly.</p>
      </div>

      <div class="invite-qr-card">
        <img v-if="state.qrUrl" :src="state.qrUrl" alt="Invite QR code" class="invite-qr-image" />
        <p v-else class="invite-qr-note">QR referral belum tersedia.</p>
        <p class="invite-qr-note">
          Friends can scan this QR code and go straight to the sign-up page.
        </p>
      </div>
    </section>

    <section class="invite-panel">
      <div class="invite-code-card">
        <span>Your Invite Code</span>
        <strong>{{ state.inviteCode }}</strong>
        <button type="button" class="invite-copy-btn" @click="copyValue(state.inviteCode)">
          {{ copiedValue === state.inviteCode ? 'Copied' : 'Copy Code' }}
        </button>
      </div>

      <div class="invite-link-card">
        <span>Your Invite Link</span>
        <input type="text" :value="state.inviteUrl" readonly />
        <button type="button" class="invite-copy-btn" @click="copyValue(state.inviteUrl)">
          {{ copiedValue === state.inviteUrl ? 'Copied' : 'Copy Link' }}
        </button>
      </div>
    </section>

    <section v-if="isLoading" class="invite-summary">
      <article class="invite-summary-card">
        <span>Referral Data</span>
        <strong>Memuat...</strong>
      </article>
    </section>

    <section v-else-if="loadError" class="invite-summary">
      <article class="invite-summary-card">
        <span>Referral Data</span>
        <strong>{{ loadError }}</strong>
      </article>
    </section>

    <section v-else class="invite-summary">
      <article class="invite-summary-card">
        <span>Total Invites</span>
        <strong>{{ state.memberCount }}</strong>
      </article>
      <article class="invite-summary-card">
        <span>Active</span>
        <strong>{{ state.activeCount }}</strong>
      </article>
    </section>

    <section class="invite-award-card">
      <div class="invite-award-head">
        <div>
          <h2>My Invite Rewards</h2>
          <p>Track how much your invited users have earned and how much they earned for you.</p>
        </div>
        <RouterLink :to="{ name: 'team' }" class="invite-secondary-link">View My Invites</RouterLink>
      </div>

      <div class="invite-award-grid">
        <article class="invite-award-box">
          <span>Team Commission</span>
          <strong>Rp{{ state.totalTeamCommission }}</strong>
        </article>
      </div>
    </section>

    <section class="invite-rules-card">
      <h2>Invite Rules</h2>
      <div class="invite-rules-list">
        <p>1. Your invite QR and invite code stay valid and can be shared anytime.</p>
        <p>2. When your referred users deposit, you earn 25% from Team A, 2% from Team B, 1% from Team C</p>
      </div>
    </section>
  </AppShell>
</template>

<style>
.invite-page-body {
  min-height: 100vh;
}

.invite-main {
  align-items: stretch;
  gap: 12px;
}

.invite-hero,
.invite-panel,
.invite-summary,
.invite-award-card,
.invite-rules-card {
  width: 100%;
  box-sizing: border-box;
}

.invite-hero,
.invite-award-card,
.invite-rules-card {
  padding: 18px 16px;
  border-radius: 28px;
  border: 1px solid rgba(18, 112, 134, 0.12);
  background:
    radial-gradient(circle at top right, rgba(131, 231, 216, 0.17), transparent 34%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(241, 250, 252, 0.98) 100%);
  box-shadow: 0 18px 34px rgba(11, 79, 98, 0.1);
}

.invite-copy {
  display: grid;
  gap: 10px;
}

.invite-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  min-height: 30px;
  padding: 0 14px;
  border-radius: 999px;
  background: linear-gradient(180deg, #e9f9fb 0%, #def4f8 100%);
  color: #176980;
  font-size: 0.74rem;
  font-weight: 800;
}

.invite-copy h1,
.invite-award-head h2,
.invite-rules-card h2 {
  margin: 0;
  color: #123045;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  line-height: 1.08;
}

.invite-copy h1 {
  font-size: 1.64rem;
}

.invite-copy p,
.invite-award-head p,
.invite-qr-note,
.invite-rules-list p {
  margin: 0;
  color: #5f7984;
  line-height: 1.55;
}

.invite-copy p {
  font-size: 0.9rem;
}

.invite-qr-card {
  display: grid;
  justify-items: center;
  gap: 12px;
  margin-top: 16px;
  padding: 16px 14px;
  border-radius: 24px;
  border: 1px solid rgba(18, 112, 134, 0.1);
  background: linear-gradient(180deg, rgba(246, 252, 254, 0.96) 0%, rgba(233, 247, 250, 0.96) 100%);
}

.invite-qr-image {
  width: 172px;
  height: 172px;
  border-radius: 20px;
  border: 1px solid rgba(18, 112, 134, 0.1);
  background: #ffffff;
  padding: 10px;
  box-sizing: border-box;
}

.invite-qr-note {
  text-align: center;
  font-size: 0.8rem;
}

.invite-panel {
  display: grid;
  gap: 10px;
}

.invite-code-card,
.invite-link-card {
  padding: 15px 14px;
  border-radius: 24px;
  border: 1px solid rgba(18, 112, 134, 0.1);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(239, 249, 252, 0.98) 100%);
  box-shadow: 0 14px 28px rgba(17, 82, 104, 0.08);
}

.invite-code-card span,
.invite-link-card span,
.invite-summary-card span,
.invite-award-box span {
  display: block;
  color: #708a94;
  font-size: 0.72rem;
  font-weight: 700;
}

.invite-code-card strong,
.invite-summary-card strong,
.invite-award-box strong {
  display: block;
  margin-top: 6px;
  color: #123046;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.32rem;
  line-height: 1;
}

.invite-link-card input {
  width: 100%;
  min-height: 48px;
  margin-top: 10px;
  padding: 0 14px;
  box-sizing: border-box;
  border: 1px solid rgba(18, 112, 134, 0.12);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.94);
  color: #15364b;
  font-size: 0.84rem;
}

.invite-copy-btn,
.invite-secondary-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 44px;
  margin-top: 12px;
  padding: 0 16px;
  border: 0;
  border-radius: 16px;
  background: linear-gradient(135deg, #19cbb6 0%, #1b91d8 100%);
  color: #ffffff;
  text-decoration: none;
  font-size: 0.84rem;
  font-weight: 800;
  box-shadow: 0 14px 24px rgba(22, 146, 170, 0.18);
  cursor: pointer;
}

.invite-summary {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.invite-summary-card,
.invite-award-box {
  padding: 15px 14px;
  border-radius: 24px;
  border: 1px solid rgba(18, 112, 134, 0.1);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(239, 249, 252, 0.98) 100%);
  box-shadow: 0 14px 28px rgba(17, 82, 104, 0.08);
}

.invite-award-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}

.invite-award-head p {
  margin-top: 8px;
  font-size: 0.84rem;
}

.invite-award-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin-top: 14px;
}

.invite-rules-card h2 {
  font-size: 1.12rem;
}

.invite-rules-list {
  display: grid;
  gap: 8px;
  margin-top: 12px;
}

.invite-rules-list p {
  font-size: 0.82rem;
}

@media (max-width: 640px) {
  .invite-hero,
  .invite-award-card,
  .invite-rules-card,
  .invite-code-card,
  .invite-link-card,
  .invite-summary-card,
  .invite-award-box {
    border-radius: 24px;
  }

  .invite-hero,
  .invite-award-card,
  .invite-rules-card {
    padding: 16px 13px;
  }

  .invite-qr-image {
    width: 154px;
    height: 154px;
  }

  .invite-summary,
  .invite-award-grid {
    grid-template-columns: 1fr;
  }

  .invite-award-head {
    flex-direction: column;
  }

  .invite-secondary-link,
  .invite-copy-btn {
    width: 100%;
  }
}
</style>
