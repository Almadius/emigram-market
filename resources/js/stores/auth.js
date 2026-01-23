import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../composables/useApi';

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || null);
  const user = ref(null);

  const isAuthenticated = computed(() => !!token.value);

  async function login(email, password) {
    try {
      // Сначала получаем CSRF cookie для Sanctum stateful API
      await api.get('/sanctum/csrf-cookie');
      
      // Затем делаем запрос на логин
      const response = await api.post('/api/login', { email, password });
      token.value = response.data.token;
      user.value = response.data.user;
      localStorage.setItem('token', token.value);
      api.setToken(token.value);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async function logout() {
    try {
      await api.post('/api/logout');
    } catch (error) {
      // Ignore errors on logout
    } finally {
      token.value = null;
      user.value = null;
      localStorage.removeItem('token');
      api.setToken(null);
    }
  }

  function init() {
    if (token.value) {
      api.setToken(token.value);
    }
  }

  return {
    token,
    user,
    isAuthenticated,
    login,
    logout,
    init,
  };
});
