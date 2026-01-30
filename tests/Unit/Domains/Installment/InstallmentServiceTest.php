<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Installment;

use App\Domains\Installment\Contracts\InstallmentRepositoryInterface;
use App\Domains\Installment\Contracts\StripeServiceInterface;
use App\Domains\Installment\DTOs\CalculateInstallmentRequestDTO;
use App\Domains\Installment\Services\InstallmentService;
use App\Domains\Installment\ValueObjects\InstallmentLimit;
use App\Domains\User\Contracts\UserRepositoryInterface;
use App\Domains\User\DTOs\UserDTO;
use App\Domains\User\Enums\UserLevelEnum;
use Mockery;
use PHPUnit\Framework\TestCase;

final class InstallmentServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_calculate_installment_for_gold_user(): void
    {
        $user = new UserDTO(
            id: 1,
            email: 'test@example.com',
            level: UserLevelEnum::GOLD
        );

        $limit = new InstallmentLimit(
            maxAmount: 5000.0,
            maxMonths: 18,
            minMonthlyPayment: 150.0,
            currency: 'EUR'
        );

        $repository = Mockery::mock(InstallmentRepositoryInterface::class);
        $repository->shouldReceive('getLimitForUserLevel')
            ->with(UserLevelEnum::GOLD)
            ->andReturn($limit);
        $repository->shouldReceive('getUserHistory')
            ->andReturn([]);
        $repository->shouldReceive('getActiveInstallmentsCount')
            ->andReturn(0);

        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $userRepository->shouldReceive('findById')
            ->with(1)
            ->andReturn($user);

        $stripeService = Mockery::mock(StripeServiceInterface::class);

        $service = new InstallmentService(
            $repository,
            $userRepository,
            $stripeService
        );

        $request = new CalculateInstallmentRequestDTO(
            userId: 1,
            amount: 2000.0, // 2000 / 12 = 166.67 >= 150.0
            requestedMonths: 12,
            currency: 'EUR'
        );

        $response = $service->calculateInstallment($request);

        $this->assertTrue($response->isApproved());
        $this->assertNotNull($response->getPlan());
        $this->assertEquals(2000.0, $response->getPlan()->getTotalAmount());
        $this->assertEquals(12, $response->getPlan()->getMonths());
    }
}
