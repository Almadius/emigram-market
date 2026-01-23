<?php

declare(strict_types=1);

namespace App\Domains\User\Services;

use App\Domains\Order\Contracts\OrderRepositoryInterface;
use App\Domains\User\Contracts\UserRepositoryInterface;
use App\Domains\User\Enums\UserLevelEnum;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для расчета динамических уровней пользователей
 * Уровень зависит от:
 * - Количества заказов
 * - Общей суммы покупок
 * - Времени в системе
 * - История платежей
 */
final class UserLevelService
{
    private const LEVEL_THRESHOLDS = [
        UserLevelEnum::BRONZE->value => [
            'min_orders' => 0,
            'min_total' => 0.0,
            'min_months' => 0,
        ],
        UserLevelEnum::SILVER->value => [
            'min_orders' => 5,
            'min_total' => 500.0,
            'min_months' => 1,
        ],
        UserLevelEnum::GOLD->value => [
            'min_orders' => 15,
            'min_total' => 2000.0,
            'min_months' => 3,
        ],
        UserLevelEnum::PLATINUM->value => [
            'min_orders' => 50,
            'min_total' => 10000.0,
            'min_months' => 6,
        ],
        UserLevelEnum::DIAMOND->value => [
            'min_orders' => 200,
            'min_total' => 50000.0,
            'min_months' => 12,
        ],
    ];

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly OrderRepositoryInterface $orderRepository,
    ) {
    }

    /**
     * Рассчитывает уровень пользователя на основе его активности
     */
    public function calculateLevel(int $userId): UserLevelEnum
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            return UserLevelEnum::BRONZE;
        }

        // Получаем статистику пользователя
        $orders = $this->orderRepository->findByUserId($userId);
        $orderCount = count($orders);
        $totalSpent = array_sum(array_map(fn($order) => $order->getTotal(), $orders));
        
        // Время в системе (месяцы с момента регистрации)
        $monthsActive = $this->getMonthsActive($userId);

        // Определяем уровень на основе порогов
        $level = UserLevelEnum::BRONZE;
        
        foreach (self::LEVEL_THRESHOLDS as $levelValue => $thresholds) {
            if ($orderCount >= $thresholds['min_orders'] 
                && $totalSpent >= $thresholds['min_total']
                && $monthsActive >= $thresholds['min_months']) {
                $level = UserLevelEnum::from($levelValue);
            }
        }

        return $level;
    }

    /**
     * Обновляет уровень пользователя, если он изменился
     */
    public function updateUserLevel(int $userId): bool
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            return false;
        }

        $currentLevel = $user->getLevel();
        $calculatedLevel = $this->calculateLevel($userId);

        // Если уровень изменился, обновляем
        if ($calculatedLevel !== $currentLevel) {
            try {
                $this->userRepository->updateLevel($userId, $calculatedLevel);
                Log::info('User level updated', [
                    'user_id' => $userId,
                    'old_level' => $currentLevel->value,
                    'new_level' => $calculatedLevel->value,
                ]);
                return true;
            } catch (\Exception $e) {
                Log::error('Error updating user level', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        }

        return false;
    }

    /**
     * Получает количество месяцев активности пользователя
     */
    private function getMonthsActive(int $userId): int
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            return 0;
        }

        // Получаем дату регистрации из модели User
        $userModel = \App\Models\User::find($userId);
        if ($userModel === null || $userModel->created_at === null) {
            return 0;
        }

        $createdAt = \Carbon\Carbon::parse($userModel->created_at);
        $now = \Carbon\Carbon::now();
        
        return (int) max(0, $createdAt->diffInMonths($now));
    }

    /**
     * Получает статистику пользователя для отображения
     */
    public function getUserStats(int $userId): array
    {
        $orders = $this->orderRepository->findByUserId($userId);
        $orderCount = count($orders);
        $totalSpent = array_sum(array_map(fn($order) => $order->getTotal(), $orders));
        $monthsActive = $this->getMonthsActive($userId);
        $currentLevel = $this->calculateLevel($userId);
        
        // Определяем следующий уровень и прогресс
        $nextLevel = $this->getNextLevel($currentLevel);
        $progress = $this->calculateProgress($orderCount, $totalSpent, $monthsActive, $currentLevel, $nextLevel);

        return [
            'current_level' => $currentLevel->value,
            'current_level_label' => $currentLevel->getLabel(),
            'next_level' => $nextLevel?->value,
            'next_level_label' => $nextLevel?->getLabel(),
            'order_count' => $orderCount,
            'total_spent' => $totalSpent,
            'months_active' => $monthsActive,
            'progress' => $progress,
        ];
    }

    /**
     * Получает следующий уровень после текущего
     */
    private function getNextLevel(UserLevelEnum $currentLevel): ?UserLevelEnum
    {
        $levels = [
            UserLevelEnum::BRONZE,
            UserLevelEnum::SILVER,
            UserLevelEnum::GOLD,
            UserLevelEnum::PLATINUM,
            UserLevelEnum::DIAMOND,
        ];

        $currentIndex = array_search($currentLevel, $levels, true);
        
        if ($currentIndex === false || $currentIndex === count($levels) - 1) {
            return null; // Уже максимальный уровень
        }

        return $levels[$currentIndex + 1];
    }

    /**
     * Рассчитывает прогресс до следующего уровня (0-100%)
     */
    private function calculateProgress(
        int $orderCount,
        float $totalSpent,
        int $monthsActive,
        UserLevelEnum $currentLevel,
        ?UserLevelEnum $nextLevel
    ): array {
        if ($nextLevel === null) {
            return [
                'percent' => 100,
                'orders_remaining' => 0,
                'total_remaining' => 0.0,
                'months_remaining' => 0,
            ];
        }

        $currentThresholds = self::LEVEL_THRESHOLDS[$currentLevel->value];
        $nextThresholds = self::LEVEL_THRESHOLDS[$nextLevel->value];

        $ordersProgress = min(100, ($orderCount - $currentThresholds['min_orders']) 
            / max(1, $nextThresholds['min_orders'] - $currentThresholds['min_orders']) * 100);
        
        $totalProgress = min(100, ($totalSpent - $currentThresholds['min_total']) 
            / max(1, $nextThresholds['min_total'] - $currentThresholds['min_total']) * 100);
        
        $monthsProgress = min(100, ($monthsActive - $currentThresholds['min_months']) 
            / max(1, $nextThresholds['min_months'] - $currentThresholds['min_months']) * 100);

        // Средний прогресс
        $overallProgress = ($ordersProgress + $totalProgress + $monthsProgress) / 3;

        return [
            'percent' => round($overallProgress, 2),
            'orders_remaining' => max(0, $nextThresholds['min_orders'] - $orderCount),
            'total_remaining' => max(0.0, $nextThresholds['min_total'] - $totalSpent),
            'months_remaining' => max(0, $nextThresholds['min_months'] - $monthsActive),
        ];
    }
}

