<script setup>
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import AuthLayout from '../../components/layout/AuthLayout.vue'
import { register } from '../../services/api'

const route = useRoute()
const router = useRouter()

const phone = ref('')
const email = ref('')
const password = ref('')
const passwordRepeat = ref('')
const verificationCode = ref('')
const isPasswordVisible = ref(false)
const isPasswordRepeatVisible = ref(false)
const captchaCode = ref(generateCaptchaCode())
const errors = ref([])
const isSubmitting = ref(false)

const prefilledInviteCode = computed(() => {
  const inviteParam = route.params.referralCode
  const inviteQuery = route.query.invite

  const invite =
    typeof inviteParam === 'string'
      ? inviteParam
      : Array.isArray(inviteParam) && typeof inviteParam[0] === 'string'
        ? inviteParam[0]
        : typeof inviteQuery === 'string'
          ? inviteQuery
          : ''

  return invite ? invite.slice(0, 64) : ''
})

const inviteCode = ref(prefilledInviteCode.value)
const passwordFieldType = computed(() => (isPasswordVisible.value ? 'text' : 'password'))
const passwordRepeatFieldType = computed(() =>
  isPasswordRepeatVisible.value ? 'text' : 'password',
)
const captchaSrc = computed(() => createCaptchaDataUrl(captchaCode.value))

watch(
  prefilledInviteCode,
  (value) => {
    if (value) {
      inviteCode.value = value
    }
  },
  { immediate: true },
)

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

function togglePasswordRepeat() {
  isPasswordRepeatVisible.value = !isPasswordRepeatVisible.value
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

function buildUsername() {
  const phoneDigits = normalizePhoneNumber(phone.value).replace(/\D/g, '')
  const suffix = Math.random().toString(36).slice(2, 8)
  return `user_${phoneDigits || 'guest'}_${suffix}`
}

function buildFullName() {
  const emailName = email.value.trim().split('@')[0]
  return emailName || `User ${normalizePhoneNumber(phone.value)}`
}

function buildWithdrawPin() {
  const phoneDigits = normalizePhoneNumber(phone.value).replace(/\D/g, '')
  return (phoneDigits.slice(-6) || '000000').padStart(6, '0')
}

function normalizeErrors(data) {
  if (!data) {
    return ['Terjadi kesalahan saat registrasi.']
  }

  if (typeof data.detail === 'string') {
    return [data.detail]
  }

  if (typeof data.message === 'string') {
    return [data.message]
  }

  const messages = Object.entries(data)
    .flatMap(([, value]) => (Array.isArray(value) ? value : [value]))
    .filter((value) => typeof value === 'string')

  return messages.length ? messages : ['Terjadi kesalahan saat registrasi.']
}

async function submitForm() {
  errors.value = []
  const normalizedPhone = normalizePhoneNumber(phone.value)
  const normalizedInviteCode = inviteCode.value.trim()

  if (!normalizedPhone || normalizedPhone === '+62') {
    errors.value = ['Nomor phone wajib diisi dengan format yang valid.']
    return
  }

  if (!normalizedInviteCode) {
    errors.value = ['Invite code wajib diisi.']
    return
  }

  if (password.value !== passwordRepeat.value) {
    errors.value = ['Password dan Repeat password harus sama.']
    return
  }

  if (verificationCode.value.trim().toUpperCase() !== captchaCode.value) {
    errors.value = ['Verification code tidak sesuai.']
    refreshCaptcha()
    verificationCode.value = ''
    return
  }

  isSubmitting.value = true

  try {
    const { ok, data } = await register({
      username: buildUsername(),
      password: password.value,
      password2: passwordRepeat.value,
      email: email.value.trim(),
      full_name: buildFullName(),
      phone: normalizedPhone,
      referral_code: normalizedInviteCode,
      otp: verificationCode.value.trim(),
      withdraw_pin: buildWithdrawPin(),
    })

    if (!ok) {
      errors.value = normalizeErrors(data)
      return
    }

    await router.push({ name: 'login', query: { registered: '1' } })
  } catch (error) {
    errors.value = [error instanceof Error ? error.message : 'Tidak bisa terhubung ke server register.']
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <AuthLayout title="Sign Up">
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

      <div class="form-field">
        <input
          id="email"
          v-model="email"
          name="email"
          type="email"
          placeholder="Email"
          autocomplete="email"
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
          autocomplete="new-password"
          minlength="8"
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

      <div class="form-field password-wrap">
        <input
          id="password_repeat"
          v-model="passwordRepeat"
          name="password_repeat"
          :type="passwordRepeatFieldType"
          placeholder="Repeat password"
          autocomplete="new-password"
          minlength="8"
          required
        />
        <button
          type="button"
          class="toggle-password"
          :class="{ 'is-visible': isPasswordRepeatVisible }"
          aria-label="Show or hide repeat password"
          @click="togglePasswordRepeat"
        >
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path>
            <circle cx="12" cy="12" r="3.25"></circle>
            <path class="eye-slash" d="M4 4l16 16"></path>
          </svg>
        </button>
      </div>

      <div class="form-field">
        <input
          id="invite_code"
          v-model="inviteCode"
          name="invite_code"
          type="text"
          placeholder="Invite code"
          autocomplete="off"
          maxlength="7"
          required
        />
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
        {{ isSubmitting ? 'Signing Up...' : 'Sign Up' }}
      </button>
    </form>

    <footer class="register-foot">
      <p>Already have an account?</p>
      <RouterLink :to="{ name: 'login' }">Sign In</RouterLink>
    </footer>
  </AuthLayout>
</template>
