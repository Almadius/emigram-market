<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
      <h1 class="text-3xl font-bold text-center mb-6">EMIGRAM MARKET</h1>
      <h2 class="text-xl text-center mb-8 text-gray-600">Вход в систему</h2>

      <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ error }}
      </div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
            Email
          </label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="test@emigram.com"
          />
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
            Пароль
          </label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="password"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="loading">Вход...</span>
          <span v-else>Войти</span>
        </button>
      </form>

      <div class="mt-4 text-sm text-gray-600 text-center">
        <p>Тестовый аккаунт:</p>
        <p>Email: test@emigram.com</p>
        <p>Password: password</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const email = ref('test@emigram.com');
const password = ref('password');
const loading = ref(false);
const error = ref(null);

async function handleLogin() {
  loading.value = true;
  error.value = null;

  try {
    await authStore.login(email.value, password.value);
    router.push('/products');
  } catch (err) {
    // Обработка ValidationException (422) - ошибки валидации Laravel
    console.error('Login error:', err);
    console.error('Error response:', err.response);
    
    if (err.response?.status === 419) {
      error.value = 'CSRF токен истек. Обновите страницу и попробуйте снова.';
    } else if (err.response?.data?.errors?.email) {
      error.value = err.response.data.errors.email[0] || 'Неверный email или пароль';
    } else if (err.response?.data?.message) {
      error.value = err.response.data.message;
    } else if (err.response?.data?.error) {
      error.value = err.response.data.error;
    } else if (err.message) {
      error.value = err.message;
    } else {
      error.value = 'Ошибка входа. Проверьте правильность введенных данных.';
    }
  } finally {
    loading.value = false;
  }
}
</script>




