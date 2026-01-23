<template>
  <div class="home">
    <Layout>
      <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold mb-6">EMIGRAM MARKET</h1>
        <p class="text-xl text-gray-600 mb-8">
          Агрегатор интернет-магазинов с персональными ценами
        </p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <div class="text-4xl mb-4">💰</div>
            <h2 class="text-2xl font-semibold mb-2">Персональные цены</h2>
            <p class="text-gray-600">Получайте лучшие цены с учетом вашего уровня (Bronze, Silver, Gold, Platinum, Diamond)</p>
          </div>
          <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <div class="text-4xl mb-4">🛒</div>
            <h2 class="text-2xl font-semibold mb-2">Мульти-магазин</h2>
            <p class="text-gray-600">Товары из разных магазинов в одной корзине. Оформляйте заказы по магазинам отдельно.</p>
          </div>
          <div class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <div class="text-4xl mb-4">💳</div>
            <h2 class="text-2xl font-semibold mb-2">Рассрочка</h2>
            <p class="text-gray-600">Удобная рассрочка платежей через Stripe. Лимиты зависят от вашего уровня.</p>
          </div>
        </div>

        <!-- Дополнительные преимущества -->
        <div v-if="authStore.isAuthenticated" class="bg-blue-50 rounded-lg p-6 mb-8">
          <h2 class="text-2xl font-semibold mb-4">Ваши преимущества</h2>
          <ul class="space-y-2 text-gray-700">
            <li class="flex items-center gap-2">
              <span class="text-green-500">✓</span>
              <span>Персональные скидки на основе вашего уровня</span>
            </li>
            <li class="flex items-center gap-2">
              <span class="text-green-500">✓</span>
              <span>Автоматическое отслеживание лучших цен</span>
            </li>
            <li class="flex items-center gap-2">
              <span class="text-green-500">✓</span>
              <span>Уведомления об изменении статуса заказов в реальном времени</span>
            </li>
          </ul>
        </div>
        <div class="mt-8 flex gap-4">
          <router-link
            to="/products"
            class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition"
          >
            Перейти к товарам
          </router-link>
          <router-link
            v-if="!authStore.isAuthenticated"
            to="/login"
            class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition"
          >
            Войти
          </router-link>
        </div>
      </div>
    </Layout>
  </div>
</template>

<script setup>
import Layout from '../components/Layout.vue';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
</script>

