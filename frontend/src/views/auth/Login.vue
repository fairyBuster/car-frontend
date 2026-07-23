<script setup>
import { computed, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import AuthLayout from '../../components/layout/AuthLayout.vue'
import { login } from '../../services/api'

const route = useRoute()
const router = useRouter()

const phone = ref('')
const password = ref('')
const verificationCode = ref('')
const isPasswordVisible = ref(false)
const captchaCode = ref(generateCaptchaCode())
const errors = ref([])
const isSubmitting = ref(false)

const successMessage = computed(() =>
  route.query.registered === '1'
    ? 'Registration Succesfull You can sign in now'
    : '',
)

const passwordFieldType = computed(() => (isPasswordVisible.value ? 'text' : 'password'))
const captchaSrc = computed(() => createCaptchaDataUrl(captchaCode.value))

function generateCaptchaCode() {
  const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'
  return Array.from({ length: 4 }, () => alphabet[Math.floor(Math.random() * alphabet.length)]).join('')
}

function createCaptchaDataUrl(code) {
  const svg = `
    <svg xmlns="http://www.w3.org/2000/svg" width="108" height="34" viewBox="0 0 108 34">
      <defs>
        <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#f7fdff"/>
          <stop offset="100%" stop-color="#e8f8ee"/>
        </linearGradient>
      </defs>
      <rect width="108" height="34" rx="6" fill="url(#bg)"/>
      <path d="M4 24C16 14 24 28 36 18s20-1 28-6 16-8 26 1 10 2 14 0" fill="none" stroke="#b9ddea" stroke-width="1.3"/>
      <path d="M2 10c8 6 13-2 21 3s16 9 27 2 20-10 31-6 17 3 25-3" fill="none" stroke="#c9ecd7" stroke-width="1.1"/>
      <text x="54" y="22" text-anchor="middle" fill="#0f6d87" font-family="Manrope, Segoe UI, sans-serif" font-size="18" font-weight="700" letter-spacing="4">${code}</text>
    </svg>
  `

  return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`
}

function togglePassword() {
  isPasswordVisible.value = !isPasswordVisible.value
}

function refreshCaptcha() {
  captchaCode.value = generateCaptchaCode()
}

function normalizePhoneNumber(value) {
  const digits = value.replace(/\D/g, '')

  if (!digits) {
    return ''
  }

  if (digits.startsWith('62')) {
    return `+${digits}`
  }

  if (digits.startsWith('0')) {
    return `+62${digits.slice(1)}`
  }

  return `+62${digits}`
}

function resolveRedirectTarget(value) {
  return typeof value === 'string' && value.startsWith('/') ? value : '/m/pages/dashboard'
}

async function submitForm() {
  errors.value = []
  const normalizedPhone = normalizePhoneNumber(phone.value)

  if (verificationCode.value.trim().toUpperCase() !== captchaCode.value) {
    errors.value = ['Verification code tidak sesuai.']
    refreshCaptcha()
    verificationCode.value = ''
    return
  }

  if (!normalizedPhone || normalizedPhone === '+62') {
    errors.value = ['Nomor phone wajib diisi dengan format yang valid.']
    return
  }

  isSubmitting.value = true

  try {
    const { ok, status, data: payload, raw } = await login({
      phone: normalizedPhone,
      password: password.value,
    })

    if (!ok) {
      if (status === 401) {
        errors.value = ['Login gagal. Phone atau password tidak valid.']
        return
      }

      errors.value = [payload?.detail || payload?.message || 'Terjadi kesalahan saat login.']
      return
    }

    if (!payload?.access) {
      errors.value = [raw || 'Login berhasil tetapi token akses tidak ditemukan.']
      return
    }

    localStorage.setItem('auth_access_token', payload.access)

    if (payload?.refresh) {
      localStorage.setItem('auth_refresh_token', payload.refresh)
    }

    localStorage.setItem('auth_phone', payload?.user?.phone || normalizedPhone)

    if (payload?.user) {
      localStorage.setItem('auth_user', JSON.stringify(payload.user))
    } else {
      localStorage.setItem(
        'auth_user',
        JSON.stringify({
          phone: normalizedPhone,
        }),
      )
    }

    const redirectTarget = resolveRedirectTarget(route.query.redirect)
    const navigationFailure = await router.replace(redirectTarget)

    if (navigationFailure || router.currentRoute.value.fullPath !== redirectTarget) {
      window.location.assign(redirectTarget)
    }
  } catch (error) {
    errors.value = [error instanceof Error ? error.message : 'Tidak bisa terhubung ke server login.']
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <AuthLayout title="Sign In">
    <div v-if="successMessage" class="alert alert-success">
      <p>{{ successMessage }}</p>
    </div>

    <div v-if="errors.length" class="alert alert-error">
      <p v-for="error in errors" :key="error">{{ error }}</p>
    </div>

    <form class="register-form" novalidate @submit.prevent="submitForm">
      <div class="form-field">
        <input
          id="phone"
          v-model="phone"
          name="phone"
          type="tel"
          placeholder="Phone"
          autocomplete="tel"
          required
        />
      </div>

      <div class="form-field password-wrap">
        <input
          id="password"
          v-model="password"
          name="password"
          :type="passwordFieldType"
          placeholder="Password"
          autocomplete="current-password"
          required
        />
        <button
          type="button"
          class="toggle-password"
          :class="{ 'is-visible': isPasswordVisible }"
          aria-label="Show or hide password"
          @click="togglePassword"
        >
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path>
            <circle cx="12" cy="12" r="3.25"></circle>
            <path class="eye-slash" d="M4 4l16 16"></path>
          </svg>
        </button>
      </div>

      <div class="form-field captcha-field">
        <input
          id="verification_code"
          v-model="verificationCode"
          name="verification_code"
          type="text"
          placeholder="Verification code"
          autocomplete="off"
          maxlength="4"
          required
        />
        <img
          :src="captchaSrc"
          alt="Verification code"
          class="captcha-image"
          title="Click to refresh"
          @click="refreshCaptcha"
        />
      </div>

      <button class="submit-button" type="submit" :disabled="isSubmitting">
        {{ isSubmitting ? 'Signing In...' : 'Sign In' }}
      </button>
    </form>

    <footer class="register-foot">
      <p>No account yet?</p>
      <RouterLink :to="{ name: 'register' }">Sign Up</RouterLink>
    </footer>
  </AuthLayout>
</template>
