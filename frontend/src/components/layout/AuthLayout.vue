<script setup>
import { onBeforeUnmount, watchEffect } from 'vue'

defineProps({
  title: {
    type: String,
    required: true,
  },
})

watchEffect(() => {
  document.body.className = 'auth-body'
})

onBeforeUnmount(() => {
  document.body.className = ''
})
</script>

<template>
  <main class="register-shell">
    <header class="register-head">
      <h1>{{ title }}</h1>
    </header>

    <section class="logo-area" aria-label="Brand logo area">
      <img src="/map/cargowize.png" alt="Site logo" class="logo-image" />
    </section>

    <slot />
  </main>
</template>

<style>
.register-shell {
  width: 100%;
  max-width: 430px;
  border-radius: 28px;
  padding: 24px 20px 18px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.93) 0%, #f9ffff 100%);
  border: 1px solid rgba(27, 126, 145, 0.18);
  box-shadow: var(--card-shadow);
  animation: shell-rise 0.55s ease-out both;
  position: relative;
  overflow: hidden;
}

.register-shell::before {
  content: '';
  position: absolute;
  top: -70px;
  left: -50px;
  width: 160px;
  height: 160px;
  background: radial-gradient(circle, rgba(72, 169, 197, 0.26) 0%, transparent 70%);
  pointer-events: none;
}

.register-shell::after {
  content: '';
  position: absolute;
  right: -80px;
  bottom: -92px;
  width: 220px;
  height: 220px;
  background: radial-gradient(circle, rgba(66, 174, 121, 0.24) 0%, transparent 70%);
  pointer-events: none;
}

.register-head {
  display: flex;
  justify-content: flex-start;
  align-items: center;
}

.register-head h1 {
  margin: 0;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  letter-spacing: 0.3px;
  font-size: 2rem;
}

.logo-area {
  text-align: center;
  margin: 14px 0 20px;
}

.logo-image {
  display: block;
  margin: 0 auto;
  width: min(180px, 62vw);
  height: auto;
  max-height: 140px;
  object-fit: contain;
  animation: logo-float 3.2s ease-in-out infinite;
}

.alert {
  border-radius: 14px;
  padding: 10px 12px;
  margin-bottom: 12px;
  font-size: 0.92rem;
}

.alert p {
  margin: 4px 0;
}

.alert-error {
  border: 1px solid rgba(181, 53, 76, 0.25);
  background: rgba(230, 88, 114, 0.09);
  color: var(--danger);
}

.alert-success {
  border: 1px solid rgba(44, 138, 95, 0.25);
  background: rgba(53, 168, 113, 0.09);
  color: var(--success);
}

.register-form {
  display: grid;
  gap: 12px;
}

.form-field {
  position: relative;
  opacity: 0;
  transform: translateY(8px);
  animation: field-enter 0.4s ease-out forwards;
}

.form-field:nth-child(2) {
  animation-delay: 0.08s;
}

.form-field:nth-child(3) {
  animation-delay: 0.14s;
}

.form-field:nth-child(4) {
  animation-delay: 0.2s;
}

.form-field:nth-child(5) {
  animation-delay: 0.24s;
}

.form-field:nth-child(6) {
  animation-delay: 0.28s;
}

.form-field input {
  width: 100%;
  height: 54px;
  border-radius: 14px;
  border: 1px solid rgba(17, 112, 131, 0.2);
  background: var(--sand-100);
  padding: 0 14px;
  font-size: 1rem;
  color: var(--text-main);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-field input::placeholder {
  color: #9aacb3;
}

.form-field input:focus {
  outline: none;
  border-color: rgba(28, 153, 181, 0.72);
  box-shadow: 0 0 0 4px rgba(89, 187, 216, 0.24);
}

.password-wrap input {
  padding-right: 48px;
}

.toggle-password {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  border: 0;
  background: transparent;
  width: 30px;
  height: 30px;
  cursor: pointer;
  padding: 3px;
}

.toggle-password svg {
  width: 24px;
  height: 24px;
  stroke: var(--lake-700);
  fill: none;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.toggle-password .eye-slash {
  opacity: 0;
  transition: opacity 0.16s ease;
}

.toggle-password.is-visible .eye-slash {
  opacity: 1;
}

.captcha-field input {
  padding-right: 126px;
}

.captcha-image {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  width: 108px;
  height: 34px;
  border-radius: 6px;
  border: 1px solid rgba(17, 112, 131, 0.18);
  background: #ffffff;
  cursor: pointer;
}

.submit-button {
  margin-top: 8px;
  height: 56px;
  border-radius: 15px;
  border: 0;
  color: white;
  font-size: 1.1rem;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-weight: 700;
  background: linear-gradient(90deg, #1592b3 0%, #2fae73 100%);
  cursor: pointer;
  box-shadow: 0 12px 24px rgba(19, 142, 130, 0.28);
  transition: transform 0.16s ease, box-shadow 0.16s ease;
}

.submit-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 16px 26px rgba(19, 142, 130, 0.34);
}

.submit-button:active {
  transform: translateY(1px);
}

.register-foot {
  margin-top: 16px;
  padding-top: 15px;
  border-top: 1px solid rgba(16, 124, 144, 0.15);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}

.register-foot p {
  margin: 0;
  color: var(--text-soft);
  font-size: 0.95rem;
}

.register-foot a {
  text-decoration: none;
  color: var(--lake-700);
  font-weight: 700;
  border: 1px solid rgba(15, 109, 135, 0.2);
  border-radius: 10px;
  padding: 8px 14px;
  background: #ffffff;
}

@keyframes shell-rise {
  from {
    opacity: 0;
    transform: translateY(16px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes field-enter {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes logo-float {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-5px);
  }
}

@media (max-width: 380px) {
  .register-shell {
    border-radius: 22px;
    padding: 20px 14px 16px;
  }

  .register-head h1 {
    font-size: 1.8rem;
  }

  .form-field input {
    height: 52px;
  }
}
</style>
