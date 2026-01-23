<template>
  <div class="layout">
    <header class="bg-white shadow-sm">
      <div class="container mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
          <router-link to="/" class="text-2xl font-bold text-blue-600">
            EMIGRAM MARKET
          </router-link>
          <nav class="flex gap-4 items-center">
            <router-link to="/products" class="text-gray-700 hover:text-blue-600 transition">
              Товары
            </router-link>
            <router-link
              v-if="authStore.isAuthenticated"
              to="/orders"
              class="text-gray-700 hover:text-blue-600 transition"
            >
              Заказы
            </router-link>
            <router-link to="/cart" class="text-gray-700 hover:text-blue-600 relative transition">
              Корзина
              <span
                v-if="cartItemsCount > 0"
                class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-semibold"
              >
                {{ cartItemsCount }}
              </span>
            </router-link>
            <button
              v-if="authStore.isAuthenticated"
              @click="handleLogout"
              class="text-gray-700 hover:text-blue-600 transition"
            >
              Выход
            </button>
            <router-link
              v-else
              to="/login"
              class="text-gray-700 hover:text-blue-600 transition"
            >
              Войти
            </router-link>
          </nav>
        </div>
      </div>
    </header>
    <main>
      <slot />
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useCartStore } from '../stores/cart';

const router = useRouter();
const authStore = useAuthStore();
const cartStore = useCartStore();

const cartItemsCount = computed(() => cartStore.itemsCount);

async function handleLogout() {
  await authStore.logout();
  router.push('/');
}
</script>




