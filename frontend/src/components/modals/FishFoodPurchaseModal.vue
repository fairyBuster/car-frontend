<script setup>
import { computed, onBeforeUnmount, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  item: {
    type: Object,
    default: null,
  },
  balance: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits(['update:modelValue', 'confirm'])

const hasEnoughBalance = computed(
  () => Number(props.balance) >= Number(props.item?.purchasePrice ?? 0),
)

function closeModal() {
  emit('update:modelValue', false)
}

function confirmPurchase() {
  if (!hasEnoughBalance.value) {
    return
  }

  emit('confirm')
  closeModal()
}

watch(
  () => props.modelValue,
  (value) => {
    document.body.classList.toggle('is-modal-open', value)
  },
  { immediate: true },
)

function onKeydown(event) {
  if (event.key === 'Escape' && props.modelValue) {
    closeModal()
  }
}

window.addEventListener('keydown', onKeydown)

onBeforeUnmount(() => {
  document.body.classList.remove('is-modal-open')
  window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <div class="fish-food-modal" :hidden="!modelValue">
    <div class="fish-food-modal-backdrop" @click="closeModal"></div>
    <div class="fish-food-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="fishFoodModalTitle">
      <button type="button" class="fish-food-modal-close" aria-label="Close" @click="closeModal">×</button>

      <div class="fish-food-modal-content">
        <span class="fish-food-modal-pill">Fish Food buy</span>
        <h2 id="fishFoodModalTitle" class="fish-food-modal-title">Fish Food</h2>

        <div class="fish-food-modal-summary">
          <div class="fish-food-modal-box">
            <span>Fish</span>
            <strong>{{ item?.name || 'Guppy' }}</strong>
          </div>
          <div class="fish-food-modal-box">
            <span>Times</span>
            <strong>5 days of food</strong>
          </div>
          <div class="fish-food-modal-box">
            <span>Amount</span>
            <strong>Rp{{ Number(item?.purchasePrice ?? 0).toFixed(2) }}</strong>
          </div>
        </div>

        <p class="fish-food-modal-text">
          Payment is deducted only from the main balance. Bonus balance cannot be used for this purchase.
        </p>
        <p class="fish-food-modal-balance">
          Available balance: <strong>Rp{{ balance.toFixed(2) }}</strong>
        </p>
        <p class="fish-food-modal-error" :hidden="hasEnoughBalance">
          There is not enough main balance for this food purchase.
        </p>

        <button type="button" class="fish-food-modal-submit" :disabled="!hasEnoughBalance" @click="confirmPurchase">
          Confirm
        </button>
      </div>
    </div>
  </div>
</template>

<style>
.fish-food-modal[hidden] {
  display: none;
}

.fish-food-modal {
  position: fixed;
  inset: 0;
  z-index: 1200;
  display: grid;
  place-items: center;
  padding: 18px;
}

.fish-food-modal-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(7, 31, 42, 0.54);
  backdrop-filter: blur(8px);
}

.fish-food-modal-dialog {
  position: relative;
  width: min(100%, 360px);
  border-radius: 24px;
  background:
    radial-gradient(circle at top right, rgba(117, 232, 210, 0.2), transparent 38%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(244, 251, 253, 0.98) 100%);
  border: 1px solid rgba(18, 113, 133, 0.16);
  box-shadow: 0 28px 60px rgba(8, 51, 67, 0.24);
  overflow: hidden;
}

.fish-food-modal-close {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 50%;
  background: rgba(236, 248, 251, 0.95);
  color: #30505e;
  font-size: 1.4rem;
  line-height: 1;
  cursor: pointer;
}

.fish-food-modal-content {
  display: grid;
  gap: 14px;
  padding: 24px 18px 18px;
}

.fish-food-modal-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 28px;
  padding: 0 14px;
  border-radius: 999px;
  background: linear-gradient(180deg, #e9f9fb 0%, #dff4f8 100%);
  color: #176980;
  font-size: 0.73rem;
  font-weight: 800;
}

.fish-food-modal-title {
  margin: 0;
  color: #133245;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.38rem;
  line-height: 1.1;
}

.fish-food-modal-summary {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
}

.fish-food-modal-box {
  display: grid;
  gap: 6px;
  padding: 12px 10px;
  border-radius: 16px;
  background: linear-gradient(180deg, #f4fbfd 0%, #edf8fb 100%);
  border: 1px solid rgba(18, 113, 133, 0.1);
}

.fish-food-modal-box span {
  color: #63808c;
  font-size: 0.68rem;
  font-weight: 700;
  line-height: 1.2;
}

.fish-food-modal-box strong {
  color: #123045;
  font-size: 0.9rem;
  line-height: 1.25;
}

.fish-food-modal-text,
.fish-food-modal-balance,
.fish-food-modal-error {
  margin: 0;
  font-size: 0.82rem;
  line-height: 1.5;
}

.fish-food-modal-text {
  color: #496772;
}

.fish-food-modal-balance {
  color: #1c5162;
  font-weight: 700;
}

.fish-food-modal-error {
  padding: 10px 12px;
  border-radius: 14px;
  background: linear-gradient(180deg, #fff4ee 0%, #ffe8de 100%);
  color: #a64e31;
  font-weight: 700;
}

.fish-food-modal-submit {
  height: 46px;
  border: 0;
  border-radius: 16px;
  background: linear-gradient(90deg, #10c39a 0%, #1aa9b4 100%);
  color: #ffffff;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.92rem;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 16px 28px rgba(15, 177, 146, 0.22);
}

.fish-food-modal-submit:disabled {
  cursor: not-allowed;
  opacity: 0.6;
  box-shadow: none;
}
</style>
