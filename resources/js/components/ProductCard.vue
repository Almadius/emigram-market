<template>
  <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
    <div v-if="product.product.image_url" class="h-48 bg-gray-200">
      <img
        :src="product.product.image_url"
        :alt="product.product.name"
        class="w-full h-full object-cover"
      />
    </div>
    <div class="p-4">
      <h3 class="text-lg font-semibold mb-2">{{ product.product.name }}</h3>
      <p v-if="product.product.description" class="text-gray-600 text-sm mb-4 line-clamp-2">
        {{ product.product.description }}
      </p>
      
      <div class="mb-4">
        <div v-if="product.emigram_price" class="mb-2">
          <div class="flex items-center gap-2">
            <div class="text-2xl font-bold text-blue-600">
              {{ formatPrice(product.emigram_price.price) }} {{ product.emigram_price.currency }}
            </div>
            <div
              v-if="product.discount_breakdown"
              class="relative group"
            >
              <button
                class="text-blue-500 hover:text-blue-700 text-sm cursor-help"
                title="Детали скидки"
              >
                ℹ️
              </button>
              <!-- Tooltip с breakdown -->
              <div
                class="absolute left-0 bottom-full mb-2 w-64 bg-gray-800 text-white text-xs rounded-lg shadow-lg p-3 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10"
              >
                <div class="font-semibold mb-2">Состав скидки:</div>
                <div class="space-y-1">
                  <div v-if="product.discount_breakdown.base_discount > 0" class="flex justify-between">
                    <span>Базовая скидка:</span>
                    <span class="font-semibold">{{ product.discount_breakdown.base_discount.toFixed(1) }}%</span>
                  </div>
                  <div v-if="product.discount_breakdown.personal_discount > 0" class="flex justify-between">
                    <span>Персональная скидка:</span>
                    <span class="font-semibold">{{ product.discount_breakdown.personal_discount.toFixed(1) }}%</span>
                  </div>
                  <div class="border-t border-gray-600 pt-1 mt-1 flex justify-between font-bold">
                    <span>Итого скидка:</span>
                    <span>{{ product.discount_breakdown.total_discount_percent.toFixed(1) }}%</span>
                  </div>
                </div>
                <div class="mt-2 pt-2 border-t border-gray-600 text-xs text-gray-300">
                  Уровень: {{ getLevelName(product.discount_breakdown.user_level) }}
                </div>
              </div>
            </div>
          </div>
          <div v-if="product.store_price" class="text-sm text-gray-500 line-through">
            {{ formatPrice(product.store_price.price) }} {{ product.store_price.currency }}
          </div>
          <div v-if="product.emigram_price.savings_percent > 0" class="text-sm text-green-600 mt-1">
            Экономия: {{ product.emigram_price.savings_absolute.toFixed(2) }} {{ product.emigram_price.currency }}
            ({{ product.emigram_price.savings_percent.toFixed(0) }}%)
          </div>
        </div>
        <div v-else-if="product.store_price" class="text-2xl font-bold">
          {{ formatPrice(product.store_price.price) }} {{ product.store_price.currency }}
        </div>
      </div>

      <button
        @click="$emit('add-to-cart', product.product.id)"
        class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition"
      >
        В корзину
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  product: {
    type: Object,
    required: true,
  },
});

defineEmits(['add-to-cart']);

function formatPrice(price) {
  return new Intl.NumberFormat('ru-RU', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(price);
}

function getLevelName(level) {
  const levels = {
    1: 'Bronze',
    2: 'Silver',
    3: 'Gold',
    4: 'Platinum',
    5: 'Diamond',
  };
  return levels[level] || 'Unknown';
}
</script>
