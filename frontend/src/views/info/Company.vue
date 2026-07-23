<script setup>
import AppShell from '../../components/layout/AppShell.vue'
import {
  backLogoutHeaderActions,
  infoFooterItems,
  marketNoticeItems,
} from '../../data/mockData'
</script>

<template>
  <AppShell
    body-class="company-page-body"
    main-class="company-main"
    :header-actions="backLogoutHeaderActions"
    :notice-items="marketNoticeItems"
    :footer-items="infoFooterItems"
    active-footer-key=""
  >
    <section class="company-hero" aria-label="Company overview">
      <span class="company-kicker">Enterprise Introduction</span>
      <h1>Company</h1>
      <p>
        Car-GoWise is a digital cargo and logistics platform designed to simplify domestic and international shipping. The platform combines shipment management, real-time tracking, and streamlined order processing to provide a fast, secure, and reliable logistics experience for businesses and individuals.
      </p>

      <div class="company-chip-row" aria-hidden="true">
        <span class="company-chip">Fast Delivery</span>
        <span class="company-chip">Real-time Tracking</span>
        <span class="company-chip">Secure Logistics</span>
      </div>
    </section>

    <section class="company-grid" aria-label="Company details">
      <article class="company-card">
        <div class="company-card-top">
          <span class="company-card-icon tone-blue" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M5 18h14"></path><path d="M7 18V8"></path><path d="M12 18V5"></path><path d="M17 18v-9"></path><path d="M5 8h4"></path><path d="M12 5h4"></path></svg>
          </span>
          <h2>Who We Are</h2>
        </div>
        <p>Car-GoWise is committed to providing modern logistics solutions that make shipping more efficient. We help customers manage deliveries with transparent tracking, simplified booking, and dependable transportation services.</p>
      </article>

      <article class="company-card">
        <div class="company-card-top">
          <span class="company-card-icon tone-green" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 18h16"></path><path d="M6.5 18V7.5L12 4l5.5 3.5V18"></path><path d="M9.5 11h5"></path><path d="M9.5 14h5"></path></svg>
          </span>
          <h2>Market Focus</h2>
        </div>
        <p>Our services support individuals, online businesses, SMEs, and enterprises that require reliable cargo transportation. We focus on domestic freight, cross-region distribution, and international logistics with an emphasis on speed, security, and operational efficiency.</p>
      </article>

      <article class="company-card">
        <div class="company-card-top">
          <span class="company-card-icon tone-blue" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4.5 7.5h15"></path><path d="M6.5 11.5h11"></path><path d="M8.5 15.5h7"></path><circle cx="12" cy="7.5" r="3.5"></circle></svg>
          </span>
          <h2>Operating Model</h2>
        </div>
        <p>Car-GoWise integrates shipment booking, package management, warehouse coordination, transportation, and delivery tracking into one digital platform. Every shipment is monitored throughout its journey to ensure timely and secure delivery</p>
      </article>
    </section>
  </AppShell>
</template>

<style>
.company-page-body {
  min-height: 100vh;
}

.company-main {
  align-items: stretch;
}

.company-hero,
.company-grid {
  width: 100%;
  max-width: 430px;
}

.company-hero {
  padding: 11px 14px;
  border-radius: 20px;
  background:
    radial-gradient(circle at top right, rgba(131, 214, 235, 0.2), transparent 40%),
    linear-gradient(135deg, rgba(11, 88, 113, 0.96) 0%, rgba(17, 122, 132, 0.94) 54%, rgba(46, 159, 115, 0.92) 100%);
  box-shadow: 0 16px 30px rgba(12, 77, 95, 0.16);
  color: #effcff;
}

.company-kicker {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(224, 252, 255, 0.2);
  font-size: 0.64rem;
  font-weight: 700;
}

.company-hero h1 {
  margin: 8px 0 5px;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.46rem;
  line-height: 1;
  color: #ffffff;
}

.company-hero p {
  margin: 0;
  color: rgba(240, 252, 255, 0.9);
  font-size: 0.78rem;
  line-height: 1.4;
}

.company-chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 9px;
}

.company-chip {
  display: inline-flex;
  align-items: center;
  min-height: 22px;
  padding: 0 9px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  color: #f6ffff;
  font-size: 0.62rem;
  font-weight: 700;
}

.company-grid {
  display: grid;
  gap: 10px;
}

.company-card {
  padding: 13px 12px;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(20, 108, 130, 0.12);
  box-shadow: 0 12px 24px rgba(18, 92, 113, 0.09);
}

.company-card-top {
  display: flex;
  align-items: center;
  gap: 9px;
  margin-bottom: 8px;
}

.company-card-icon {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  flex: 0 0 auto;
}

.company-card-icon.tone-blue {
  background: linear-gradient(180deg, #e1f5fb 0%, #d9eef8 100%);
  border: 1px solid rgba(23, 129, 164, 0.16);
}

.company-card-icon.tone-green {
  background: linear-gradient(180deg, #e4f8f0 0%, #daf1e7 100%);
  border: 1px solid rgba(42, 151, 110, 0.16);
}

.company-card-icon svg {
  width: 18px;
  height: 18px;
  fill: none;
  stroke-width: 1.9;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.company-card-icon.tone-blue svg {
  stroke: #117ea1;
}

.company-card-icon.tone-green svg {
  stroke: #2b9167;
}

.company-card h2 {
  margin: 0;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.98rem;
  line-height: 1.15;
  color: #123045;
}

.company-card p {
  margin: 0;
  color: #4e6a74;
  font-size: 0.78rem;
  line-height: 1.48;
}

@media (max-width: 380px) {
  .company-hero {
    padding: 10px 12px;
    border-radius: 18px;
  }

  .company-hero h1 {
    font-size: 1.3rem;
  }

  .company-hero p {
    font-size: 0.74rem;
    line-height: 1.36;
  }

  .company-chip {
    font-size: 0.58rem;
  }

  .company-card {
    padding: 12px 11px;
    border-radius: 16px;
  }

  .company-card-icon {
    width: 34px;
    height: 34px;
    border-radius: 11px;
  }

  .company-card h2 {
    font-size: 0.9rem;
  }

  .company-card p {
    font-size: 0.74rem;
  }
}
</style>
