<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const STORAGE_KEY = 'deposit_qris_overview'

function readStoredOverview() {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY)
    if (!raw) {
      return null
    }

    const parsed = JSON.parse(raw)
    return parsed && typeof parsed === 'object' ? parsed : null
  } catch {
    return null
  }
}

const payment = computed(() => readStoredOverview())
const qrisLogoSrc = '/map/7b1e587eb74663d00bca3d7b32d0914b6ce7146e.png'
const footerBannerSrc = '/map/30c2f86c4756cf151567c834ee18f1d19a733af7.png'

function backToDeposit() {
  router.replace({ name: 'deposit' })
}

function openDepositPayment() {
  router.push({ name: 'deposit-payment' })
}
</script>

<template>
  <main class="deposit-overview-main">
    <section v-if="payment" class="payment-details-section" aria-label="Payment details">
      <div class="payment-details-card">
        <div class="payment-transaction-no">No: {{ payment.orderNo || '-' }}</div>
        <div class="payment-divider"></div>
        <div class="payment-amount">Rp {{ payment.amount || '0' }}</div>
      </div>
    </section>

    <section v-if="payment" class="payment-method-section" aria-label="Payment method">
      <div class="payment-method-card">
        <div class="payment-method-header">
          <div class="payment-method-title">{{ payment.channel || 'QRIS' }}</div>
        </div>

        <div class="payment-method-body">
          <button
            type="button"
            class="payment-method-logo-button"
            aria-label="Buka pembayaran QRIS"
            @click="openDepositPayment"
          >
            <img :src="qrisLogoSrc" alt="QRIS Logo" class="payment-method-logo" />
          </button>
         
        </div>
      </div>
    </section>

    <section v-if="payment" id="footer" class="deposit-payment-footer">
      <img :src="footerBannerSrc" alt="Footer Pay" class="deposit-payment-footer-img" />
    </section>

    <section v-else class="deposit-alert is-error deposit-overview-error" aria-label="Deposit overview error">
      <p>Data QR deposit tidak ditemukan.</p>
      <button type="button" class="deposit-submit-btn" @click="backToDeposit">Kembali ke deposit</button>
    </section>
  </main>
</template>

<style scoped>
.deposit-overview-main {
  background: #d8d548;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-height: 100vh;
  min-height: 100dvh;
  width: 100%;
  padding: 0;
  box-sizing: border-box;
  gap: 0;
}

.payment-details-section,
.payment-method-section,
.deposit-payment-footer {
  width: 100%;
  max-width: 412px;
  margin: 0 auto;
  box-sizing: border-box;
  background: #d8d548;
}

.payment-details-section {
  padding: 28px 17px 19px;
}

.payment-details-card {
  background: #ffffff;
  min-height: 125px;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 28px 16px 20px;
  box-sizing: border-box;
}

.payment-transaction-no {
  color: #656565;
  font-size: 14px;
  line-height: 17px;
  text-align: center;
}

.payment-divider {
  width: 100%;
  height: 1px;
  background: rgba(0, 0, 0, 0.08);
  margin: 18px 0;
}

.payment-amount {
  color: #000000;
  font-size: 16px;
  font-weight: 700;
  line-height: 18px;
}

.payment-method-section {
  padding: 0 17px 24px;
}

.payment-method-card {
  background: #ffffff;
  overflow: hidden;
}

.payment-method-header {
  background: #d8d8d8;
  min-height: 42px;
  padding: 14px 20px 10px;
  box-sizing: border-box;
}

.payment-method-title {
  color: #7f7f7f;
  font-size: 14px;
  line-height: 17px;
}

.payment-method-body {
  padding: 14px 17px 18px;
  box-sizing: border-box;
}

.payment-method-logo {
  width: 128px;
  height: 52px;
  border: 1px solid #bfbfbf;
  display: block;
  box-sizing: border-box;
  object-fit: contain;
  background: #ffffff;
}

.payment-method-logo-button {
  padding: 0;
  border: 0;
  background: transparent;
  cursor: pointer;
}

.payment-method-qr {
  display: block;
  width: min(100%, 280px);
  margin: 16px auto 0;
  background: #ffffff;
  border-radius: 12px;
  padding: 8px;
}

.payment-method-instruction {
  margin: 16px 0 0;
  color: #3e3e3e;
  font-size: 0.86rem;
  line-height: 1.5;
}

.payment-method-expired {
  margin: 10px 0 0;
  color: #5f5f5f;
  font-size: 0.8rem;
}

.deposit-payment-footer {
  margin-top: auto;
  min-height: 77px;
  display: flex;
  flex-shrink: 0;
}

.deposit-payment-footer-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.deposit-overview-error {
  width: 100%;
  max-width: 412px;
  margin: auto auto 0;
}
</style>
