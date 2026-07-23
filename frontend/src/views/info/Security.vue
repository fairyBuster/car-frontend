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
    body-class="security-page-body"
    main-class="security-main"
    :header-actions="backLogoutHeaderActions"
    :notice-items="marketNoticeItems"
    :footer-items="infoFooterItems"
    active-footer-key=""
  >
    <section class="security-hero" aria-label="Security introduction">
      <span class="security-kicker">Privacy & Fund Safety</span>
      <h1>Security</h1>
      <p>
        We are committed to protecting personal data, securing account activity, and reducing operational risk
        through layered controls designed around confidentiality, integrity, lawful processing, and financial safety.
      </p>

      <div class="security-pill-row" aria-hidden="true">
        <span class="security-pill">Data minimization</span>
        <span class="security-pill">Controlled access</span>
        <span class="security-pill">Activity monitoring</span>
      </div>
    </section>

    <section class="security-grid" aria-label="Security details">
      <article class="security-card">
        <div class="security-card-top">
          <span class="security-card-icon tone-blue" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M12 3.5l7 3v5.2c0 4.1-2.8 7.8-7 8.8-4.2-1-7-4.7-7-8.8V6.5z"></path><path d="M9.5 12l1.7 1.7 3.3-3.7"></path></svg>
          </span>
          <h2>Personal Data Protection</h2>
        </div>
        <p>Personal data is handled in line with applicable data protection principles such as lawfulness, transparency, purpose limitation, data minimization, accuracy, storage limitation, and confidentiality.</p>
        <ul class="security-list">
          <li>Only the information required for account operation, verification, support, and transaction processing is collected.</li>
          <li>Access to personal data is restricted to authorized personnel and limited to legitimate business need.</li>
          <li>Administrative, technical, and procedural safeguards are used to reduce unauthorized access, misuse, loss, or disclosure.</li>
          <li>Retention periods are reviewed so data is not kept longer than necessary for legal, regulatory, or operational purposes.</li>
        </ul>
      </article>

      <article class="security-card">
        <div class="security-card-top">
          <span class="security-card-icon tone-green" aria-hidden="true">
            <svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7.5A4 4 0 0 1 12 3.5a4 4 0 0 1 4 4V10"></path><circle cx="12" cy="15" r="1.1"></circle></svg>
          </span>
          <h2>Account & Session Security</h2>
        </div>
        <p>Account safety depends on both platform-side controls and responsible user behavior. Our approach focuses on access control, session integrity, and secure account recovery workflows.</p>
        <ul class="security-list">
          <li>Session handling, login protection, and account checks are used to reduce unauthorized access attempts.</li>
          <li>Yousitive actions are reviewed through validation steps designed to prevent misuse and account takeover.</li>
          <li>Internal access is limited by role and monitored to support accountability and traceability.</li>
          <li>Users should also maintain strong passwords, protect their devices, and avoid sharing credentials.</li>
        </ul>
      </article>

      <article class="security-card">
        <div class="security-card-top">
          <span class="security-card-icon tone-blue" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4.5 8.5h15l-1.5 10h-12z"></path><path d="M9 8.5a3 3 0 0 1 6 0"></path><path d="M7.5 13.5h9"></path></svg>
          </span>
          <h2>Fund Safety Controls</h2>
        </div>
        <p>Fund protection is supported by layered operational controls intended to reduce fraud risk, processing errors, and unauthorized movement of value.</p>
        <ul class="security-list">
          <li>Critical payment and withdrawal activity is subject to review rules, verification checks, and transaction monitoring.</li>
          <li>Operational workflows are structured to reduce the risk of unauthorized fund instructions and suspicious activity.</li>
          <li>Records are maintained to support reconciliation, investigation, and dispute handling when needed.</li>
          <li>Risk controls are reviewed over time and updated as platform, legal, or operational requirements evolve.</li>
        </ul>
      </article>

      <article class="security-card">
        <div class="security-card-top">
          <span class="security-card-icon tone-green" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M6 18h12"></path><path d="M8 18v-4"></path><path d="M12 18v-7"></path><path d="M16 18v-10"></path><path d="M5 8.5l4-3 3 2 6-4"></path></svg>
          </span>
          <h2>Monitoring, Backup & Response</h2>
        </div>
        <p>Security is not a one-time setup. It depends on monitoring, review, backup discipline, and incident response preparation.</p>
        <ul class="security-list">
          <li>Operational events and suspicious patterns may be monitored to support detection and response.</li>
          <li>Backup and recovery procedures are used to improve resilience and continuity.</li>
          <li>System changes, permissions, and higher-risk actions should be reviewed through controlled processes.</li>
          <li>If an incident is identified, containment, review, and corrective action procedures help reduce impact.</li>
        </ul>
      </article>
    </section>

    <section class="security-commitment" aria-label="User rights">
      <h2>User Rights & Transparency</h2>
      <p>Subject to applicable law, users may request access to their personal data, ask for correction of inaccurate information, and request deletion or restriction where legally available. Some information may need to be retained for fraud prevention, financial record-keeping, legal obligations, or dispute resolution.</p>
      <p>No digital service can promise zero risk. Our goal is to reduce risk with practical safeguards, timely review, and continuous improvement while keeping privacy, account safety, and fund protection as core priorities.</p>
      <div class="security-note">This page is an overview of our security approach and should be read together with platform rules, account procedures, and applicable legal requirements.</div>
    </section>
  </AppShell>
</template>

<style>
.security-page-body {
  min-height: 100vh;
}

.security-main {
  align-items: stretch;
}

.security-hero,
.security-grid,
.security-commitment {
  width: 100%;
  max-width: 430px;
}

.security-hero {
  padding: 11px 14px;
  border-radius: 22px;
  background:
    radial-gradient(circle at top right, rgba(131, 214, 235, 0.28), transparent 42%),
    linear-gradient(135deg, rgba(9, 89, 114, 0.96) 0%, rgba(14, 118, 126, 0.94) 52%, rgba(45, 160, 112, 0.92) 100%);
  box-shadow: 0 18px 34px rgba(12, 77, 95, 0.18);
  color: #effcff;
}

.security-kicker {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(224, 252, 255, 0.22);
  font-size: 0.66rem;
  font-weight: 700;
  letter-spacing: 0.12px;
}

.security-hero h1 {
  margin: 9px 0 5px;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.48rem;
  line-height: 1;
  color: #ffffff;
}

.security-hero p {
  margin: 0;
  color: rgba(240, 252, 255, 0.9);
  font-size: 0.8rem;
  line-height: 1.38;
}

.security-pill-row {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 9px;
}

.security-pill {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 9px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  color: #f6ffff;
  font-size: 0.66rem;
  font-weight: 700;
}

.security-grid {
  display: grid;
  gap: 12px;
}

.security-card {
  padding: 15px 14px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(20, 108, 130, 0.12);
  box-shadow: 0 12px 26px rgba(18, 92, 113, 0.1);
}

.security-card-top {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.security-card-icon {
  width: 42px;
  height: 42px;
  border-radius: 13px;
  display: grid;
  place-items: center;
  flex: 0 0 auto;
}

.security-card-icon.tone-blue {
  background: linear-gradient(180deg, #e1f5fb 0%, #d9eef8 100%);
  border: 1px solid rgba(23, 129, 164, 0.16);
}

.security-card-icon.tone-green {
  background: linear-gradient(180deg, #e4f8f0 0%, #daf1e7 100%);
  border: 1px solid rgba(42, 151, 110, 0.16);
}

.security-card-icon svg {
  width: 20px;
  height: 20px;
  fill: none;
  stroke-width: 1.9;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.security-card-icon.tone-blue svg {
  stroke: #117ea1;
}

.security-card-icon.tone-green svg {
  stroke: #2b9167;
}

.security-card h2,
.security-commitment h2 {
  margin: 0;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.05rem;
  line-height: 1.15;
  color: #123045;
}

.security-card p,
.security-commitment p {
  margin: 0;
  color: #4e6a74;
  font-size: 0.84rem;
  line-height: 1.55;
}

.security-card p + .security-list,
.security-commitment p + p {
  margin-top: 10px;
}

.security-list {
  margin: 0;
  padding-left: 18px;
  display: grid;
  gap: 7px;
  color: #4e6a74;
  font-size: 0.8rem;
  line-height: 1.5;
}

.security-commitment {
  padding: 16px 14px;
  border-radius: 22px;
  background:
    radial-gradient(circle at top right, rgba(176, 228, 245, 0.22), transparent 36%),
    rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(20, 108, 130, 0.12);
  box-shadow: 0 12px 26px rgba(18, 92, 113, 0.1);
  display: grid;
  gap: 10px;
}

.security-note {
  padding: 12px 13px;
  border-radius: 16px;
  background: linear-gradient(90deg, rgba(18, 126, 153, 0.08) 0%, rgba(42, 155, 102, 0.08) 100%);
  border: 1px solid rgba(18, 126, 153, 0.12);
  color: #32535d;
  font-size: 0.76rem;
  line-height: 1.45;
}

@media (max-width: 380px) {
  .security-hero {
    padding: 10px 12px;
    border-radius: 18px;
  }

  .security-hero h1 {
    margin: 8px 0 5px;
    font-size: 1.32rem;
  }

  .security-hero p {
    font-size: 0.76rem;
    line-height: 1.34;
  }

  .security-pill {
    min-height: 22px;
    font-size: 0.6rem;
  }

  .security-card {
    padding: 14px 12px;
    border-radius: 18px;
  }

  .security-card-icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
  }

  .security-card h2,
  .security-commitment h2 {
    font-size: 0.96rem;
  }

  .security-card p,
  .security-commitment p,
  .security-list {
    font-size: 0.76rem;
  }

  .security-commitment {
    padding: 14px 12px;
    border-radius: 18px;
  }

  .security-note {
    font-size: 0.72rem;
  }
}
</style>
