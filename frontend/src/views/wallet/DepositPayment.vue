<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const STORAGE_KEY = 'deposit_qris_overview'

function readStoredPayment() {
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

const payment = computed(() => readStoredPayment())
const qrisLogoSrc = '/map/7b1e587eb74663d00bca3d7b32d0914b6ce7146e.png'
const supportedAppsSrc = '/map/cc718159136d726ab271c1854c56aa58ae973d7e.png'
const footerImgSrc = '/map/30c2f86c4756cf151567c834ee18f1d19a733af7.png'

function goBack() {
  router.replace({ name: 'deposit-overview' })
}
</script>

<template>
  <main class="deposit-payment-main">
    <template v-if="payment">
      <section id="payment-details" class="deposit-payment-section">
        <div class="deposit-payment-card deposit-payment-main-card">
          <div class="deposit-payment-transaction-no">No: {{ payment.orderNo || '-' }}</div>
          <hr class="deposit-payment-divider-thin" />
          <div class="deposit-payment-amount-large">Rp {{ payment.amount || '0' }}</div>

          <div class="deposit-payment-status-banner">
            <img :src="qrisLogoSrc" alt="QRIS Logo" class="deposit-payment-qris-logo" />
            <span class="deposit-payment-status-text">Menunggu Pembayaran Anda</span>
          </div>

          <img :src="payment.qrisImage" alt="QR Code" class="deposit-payment-qr-code" />

          <div class="deposit-payment-jumlah-label">Jumlah</div>
          <div class="deposit-payment-amount-small">Rp {{ payment.amount || '0' }}</div>

          <button type="button" class="deposit-payment-status-btn" @click="goBack">Status</button>
        </div>
      </section>

      <section id="supported-apps" class="deposit-payment-section">
        <div class="deposit-payment-card deposit-payment-apps-card">
          <div class="deposit-payment-supported-title">Mendukung APP berikut</div>
          <hr class="deposit-payment-divider-blue" />
          <img :src="supportedAppsSrc" alt="Supported Apps" class="deposit-payment-apps-logos" />
          <div class="deposit-payment-disclaimer">
            QRIS dapat di-scan dan dibayar melalui semua aplikasi penyelenggara yang diberi izin oleh
            Bank Indonesia
          </div>
        </div>
      </section>

      <section id="footer" class="deposit-payment-footer">
        <img :src="footerImgSrc" alt="Footer Pay" class="deposit-payment-footer-img" />
      </section>
    </template>

    <section v-else class="deposit-alert is-error deposit-payment-error" aria-label="Deposit payment error">
      <p>Data pembayaran QRIS tidak ditemukan.</p>
      <button type="button" class="deposit-submit-btn" @click="goBack">Kembali</button>
    </section>
  </main>
</template>

<style scoped>
.deposit-payment-main {
  background: #d8d548;
  min-height: 100vh;
  min-height: 100dvh;
  width: 100%;
  padding: 0;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0;
}

.deposit-payment-section,
.deposit-payment-footer {
  width: 100%;
  max-width: 412px;
  margin: 0 auto;
  box-sizing: border-box;
}

.deposit-payment-section {
  display: flex;
  justify-content: center;
}

.deposit-payment-card {
  background: #ffffff;
  width: 381px;
  max-width: 92%;
  display: flex;
  flex-direction: column;
  align-items: center;
  box-sizing: border-box;
}

.deposit-payment-main-card {
  margin-top: 27px;
  padding-top: 28px;
  padding-bottom: 14px;
}

.deposit-payment-transaction-no {
  color: #7f7f7f;
  font-size: 14px;
  text-align: center;
}

.deposit-payment-divider-thin {
  width: 348px;
  max-width: 90%;
  height: 0;
  border: none;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
  margin-top: 17px;
  margin-bottom: 15px;
}

.deposit-payment-amount-large {
  color: #000000;
  font-size: 20px;
  font-weight: 700;
  text-align: center;
}

.deposit-payment-status-banner {
  background: #fef6e3;
  width: 342px;
  max-width: 90%;
  min-height: 45px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 14px;
}

.deposit-payment-qris-logo {
  width: 66px;
  height: 27px;
  object-fit: contain;
}

.deposit-payment-status-text {
  color: #725000;
  font-size: 12px;
  font-weight: 700;
}

.deposit-payment-qr-code {
  width: 249px;
  height: 251px;
  margin-top: 9px;
  object-fit: contain;
}

.deposit-payment-jumlah-label {
  color: #7f7f7f;
  font-size: 12px;
  margin-top: 9px;
}

.deposit-payment-amount-small {
  color: #000000;
  font-size: 14px;
  font-weight: 700;
  margin-top: 7px;
}

.deposit-payment-status-btn {
  background: #84b7f9;
  color: #ffffff;
  width: 122px;
  height: 33px;
  border-radius: 5px;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  margin-top: 8px;
  cursor: pointer;
}

.deposit-payment-apps-card {
  margin-top: 18px;
  padding-top: 12px;
  padding-bottom: 20px;
}

.deposit-payment-supported-title {
  color: #84b7f9;
  font-size: 14px;
  font-weight: 700;
  text-align: center;
}

.deposit-payment-divider-blue {
  width: 348px;
  max-width: 90%;
  height: 0;
  border: none;
  border-top: 2px solid #84b7f9;
  margin-top: 14px;
}

.deposit-payment-apps-logos {
  width: 361px;
  max-width: 95%;
  height: 181px;
  object-fit: contain;
  margin-top: 25px;
}

.deposit-payment-disclaimer {
  color: #3e3e3e;
  font-size: 11px;
  text-align: center;
  width: 370px;
  max-width: 95%;
  margin-top: 30px;
  line-height: 1.4;
}

.deposit-payment-footer {
  margin-top: 97px;
  display: flex;
}

.deposit-payment-footer-img {
  width: 100%;
  height: auto;
  display: block;
}

.deposit-payment-error {
  width: 100%;
  max-width: 412px;
  margin: auto auto 0;
}
</style>
