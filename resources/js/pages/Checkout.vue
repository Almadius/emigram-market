<template>
  <Layout>
    <div class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-6">Оформление заказа</h1>

      <div v-if="cartStore.loading" class="text-center py-12">
        <p class="text-gray-500">Загрузка...</p>
      </div>

      <div v-else-if="cartStore.items.length === 0" class="text-center py-12">
        <p class="text-gray-500 text-lg mb-4">Корзина пуста</p>
        <router-link to="/products" class="text-blue-600 hover:underline">
          Перейти к товарам
        </router-link>
      </div>

      <div v-else>
        <!-- Группировка товаров по магазинам -->
        <div v-for="(shopItems, shopDomain) in groupedByShop" :key="shopDomain" class="mb-8">
          <div class="bg-white rounded-lg shadow p-6 mb-4">
            <h2 class="text-xl font-semibold mb-4">
              Магазин: {{ shopItems[0].shop_domain || 'Неизвестный магазин' }}
            </h2>

            <!-- Товары магазина -->
            <div class="space-y-4 mb-6">
              <div
                v-for="item in shopItems"
                :key="item.product_id"
                class="flex justify-between items-center border-b pb-4"
              >
                <div class="flex-1">
                  <h3 class="font-semibold">{{ item.product_name }}</h3>
                  <p class="text-sm text-gray-500">
                    {{ formatPrice(item.price) }} {{ item.currency }} x {{ item.quantity }}
                  </p>
                </div>
                <div class="text-lg font-semibold">
                  {{ formatPrice(item.total) }} {{ item.currency }}
                </div>
              </div>
            </div>

            <!-- Итого по магазину -->
            <div class="border-t pt-4 mb-4">
              <div class="flex justify-between items-center mb-4">
                <span class="text-lg font-semibold">Итого по магазину:</span>
                <span class="text-xl font-bold">
                  {{ formatPrice(shopTotal(shopItems)) }} {{ shopItems[0].currency }}
                </span>
              </div>

              <!-- Форма оформления заказа -->
              <form @submit.prevent="handleCheckout(shopItems[0])" class="space-y-4">
                <div v-if="errors[shopDomain]" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                  {{ errors[shopDomain] }}
                </div>

                <div v-if="success[shopDomain]" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                  Заказ успешно оформлен! ID заказа: {{ success[shopDomain] }}
                </div>

                <button
                  type="submit"
                  :disabled="processing[shopDomain] || success[shopDomain]"
                  class="w-full px-6 py-3 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
                >
                  <span v-if="processing[shopDomain]">Оформление...</span>
                  <span v-else-if="success[shopDomain]">Заказ оформлен</span>
                  <span v-else>Оформить заказ для этого магазина</span>
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Общая сумма -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex justify-between items-center">
            <span class="text-xl font-bold">Общая сумма всех заказов:</span>
            <span class="text-2xl font-bold text-blue-600">
              {{ formatPrice(cartStore.total) }} EUR
            </span>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Layout from '../components/Layout.vue';
import { useCartStore } from '../stores/cart';
import api from '../composables/useApi';
import toast from '../utils/toast';

const cartStore = useCartStore();
const router = useRouter();

const processing = ref({});
const errors = ref({});
const success = ref({});

// Группировка товаров по магазинам
const groupedByShop = computed(() => {
  const grouped = {};
  cartStore.items.forEach(item => {
    const shopDomain = item.shop_domain || 'unknown';
    if (!grouped[shopDomain]) {
      grouped[shopDomain] = [];
    }
    grouped[shopDomain].push(item);
  });
  return grouped;
});

function shopTotal(shopItems) {
  return shopItems.reduce((sum, item) => sum + parseFloat(item.total), 0);
}

function formatPrice(price) {
  return new Intl.NumberFormat('ru-RU', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(price);
}

async function handleCheckout(firstItem) {
  const shopDomain = firstItem.shop_domain;
  const shopId = firstItem.shop_id;
  const currency = firstItem.currency || 'EUR';

  // Проверка наличия обязательных полей
  if (!shopDomain || !shopId) {
    errors.value[shopDomain] = 'Отсутствуют данные о магазине. Пожалуйста, обновите корзину.';
    return;
  }

  processing.value[shopDomain] = true;
  errors.value[shopDomain] = null;
  success.value[shopDomain] = null;

  try {
    const response = await api.post('/api/v1/orders', {
      shop_domain: shopDomain,
      shop_id: parseInt(shopId, 10), // Убеждаемся, что shop_id - число
      currency: currency,
    });

    const orderId = response.data.data?.id || response.data.id;
    success.value[shopDomain] = orderId;
    
    toast.success(`Заказ #${orderId} успешно оформлен!`);
    
    // Обновляем корзину после успешного заказа
    await cartStore.fetchCart();

    // Если корзина пуста, перенаправляем на главную
    if (cartStore.items.length === 0) {
      setTimeout(() => {
        router.push('/orders');
      }, 2000);
    }
  } catch (error) {
    const errorMessage = error.response?.data?.message 
      || error.response?.data?.error 
      || error.message 
      || 'Не удалось оформить заказ';
    errors.value[shopDomain] = errorMessage;
    toast.error(errorMessage);
    console.error('Failed to create order:', error);
  } finally {
    processing.value[shopDomain] = false;
  }
}

onMounted(() => {
  cartStore.fetchCart();
});
</script>
