<template>
  <Layout>
    <div class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-6">Корзина</h1>

      <div v-if="cartStore.loading" class="text-center py-12">
        <p class="text-gray-500">Загрузка...</p>
      </div>

      <div v-else-if="cartStore.items.length === 0" class="text-center py-12">
        <p class="text-gray-500 text-lg mb-4">Корзина пуста</p>
        <router-link to="/products" class="text-blue-600 hover:underline">
          Перейти к товарам
        </router-link>
      </div>

      <div v-else class="bg-white rounded-lg shadow p-6">
        <div class="space-y-4 mb-6">
          <div
            v-for="item in cartStore.items"
            :key="item.product_id"
            class="flex justify-between items-center border-b pb-4"
          >
            <div class="flex-1">
              <h3 class="font-semibold">{{ item.product_name }}</h3>
              <p class="text-sm text-gray-500">
                {{ formatPrice(item.price) }} {{ item.currency }} x {{ item.quantity }}
              </p>
            </div>
            <div class="flex items-center gap-4">
              <!-- Изменение количества -->
              <div class="flex items-center gap-2">
                <button
                  @click="updateQuantity(item.product_id, item.quantity - 1)"
                  :disabled="item.quantity <= 1"
                  class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  -
                </button>
                <span class="w-12 text-center font-medium">{{ item.quantity }}</span>
                <button
                  @click="updateQuantity(item.product_id, item.quantity + 1)"
                  class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded hover:bg-gray-50"
                >
                  +
                </button>
              </div>
              
              <div class="text-lg font-semibold w-24 text-right">
                {{ formatPrice(item.total) }} {{ item.currency }}
              </div>
              <button
                @click="handleRemoveItem(item.product_id)"
                class="text-red-600 hover:text-red-800 px-2"
                title="Удалить товар"
              >
                🗑️
              </button>
            </div>
          </div>
        </div>

        <div class="border-t pt-4 flex justify-between items-center">
          <div class="text-xl font-bold">
            Итого: {{ formatPrice(cartStore.total) }} EUR
          </div>
          <div class="flex gap-4">
            <button
              @click="cartStore.clearCart()"
              class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50"
            >
              Очистить корзину
            </button>
            <router-link
              to="/checkout"
              class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
            >
              Оформить заказ
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import { useCartStore } from '../stores/cart';
import toast from '../utils/toast';

const cartStore = useCartStore();
const updating = ref({});

function formatPrice(price) {
  return new Intl.NumberFormat('ru-RU', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(price);
}

async function updateQuantity(productId, newQuantity) {
  if (newQuantity < 1) {
    return;
  }
  
  updating.value[productId] = true;
  try {
    await cartStore.updateItem(productId, newQuantity);
  } catch (error) {
    console.error('Failed to update quantity:', error);
    alert('Не удалось изменить количество');
  } finally {
    updating.value[productId] = false;
  }
}

async function handleRemoveItem(productId) {
  try {
    await cartStore.removeItem(productId);
    toast.success('Товар удален из корзины');
  } catch (error) {
    console.error('Failed to remove item:', error);
    toast.error('Не удалось удалить товар');
  }
}

onMounted(() => {
  cartStore.fetchCart();
});
</script>




