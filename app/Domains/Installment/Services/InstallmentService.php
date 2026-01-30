<?php

declare(strict_types=1);

namespace App\Domains\Installment\Services;

use App\Domains\Installment\Contracts\InstallmentRepositoryInterface;
use App\Domains\Installment\Contracts\StripeServiceInterface;
use App\Domains\Installment\DTOs\CalculateInstallmentRequestDTO;
use App\Domains\Installment\DTOs\CalculateInstallmentResponseDTO;
use App\Domains\Installment\ValueObjects\InstallmentPlan;
use App\Domains\User\Contracts\UserRepositoryInterface;
use App\Domains\User\Enums\UserLevelEnum;

final class InstallmentService
{
    private const INTEREST_RATES = [
        UserLevelEnum::BRONZE->value => 5.0,
        UserLevelEnum::SILVER->value => 4.0,
        UserLevelEnum::GOLD->value => 3.0,
        UserLevelEnum::PLATINUM->value => 2.0,
        UserLevelEnum::DIAMOND->value => 1.0,
    ];

    public function __construct(
        private readonly InstallmentRepositoryInterface $repository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly StripeServiceInterface $stripeService,
    ) {}

    public function calculateInstallment(CalculateInstallmentRequestDTO $request): CalculateInstallmentResponseDTO
    {
        $user = $this->userRepository->findById($request->getUserId());
        if ($user === null) {
            throw new \InvalidArgumentException('User not found');
        }

        $level = $user->getLevel();
        $limit = $this->repository->getLimitForUserLevel($level);

        // Проверяем историю пользователя
        $history = $this->repository->getUserHistory($request->getUserId());
        $activeCount = $this->repository->getActiveInstallmentsCount($request->getUserId());

        // Проверяем лимиты
        if (! $limit->canAfford($request->getAmount(), $request->getRequestedMonths())) {
            return new CalculateInstallmentResponseDTO(
                approved: false,
                plan: null,
                limit: $limit,
                message: 'Requested amount or months exceed your limit'
            );
        }

        // Проверяем количество активных рассрочек
        if ($activeCount >= 3) {
            return new CalculateInstallmentResponseDTO(
                approved: false,
                plan: null,
                limit: $limit,
                message: 'Maximum number of active installments reached'
            );
        }

        // Рассчитываем план
        $interestRate = self::INTEREST_RATES[$level->value] ?? 5.0;
        $totalWithInterest = $request->getAmount() * (1 + $interestRate / 100);
        $monthlyPayment = $totalWithInterest / $request->getRequestedMonths();

        $plan = new InstallmentPlan(
            totalAmount: $request->getAmount(),
            months: $request->getRequestedMonths(),
            monthlyPayment: $monthlyPayment,
            interestRate: $interestRate,
            currency: $request->getCurrency()
        );

        return new CalculateInstallmentResponseDTO(
            approved: true,
            plan: $plan,
            limit: $limit,
            message: 'Installment plan approved'
        );
    }

    public function createInstallment(CalculateInstallmentRequestDTO $request, InstallmentPlan $plan): string
    {
        $stripePlanId = $this->stripeService->createInstallmentPlan($plan, $request->getUserId());

        return $stripePlanId;
    }
}
