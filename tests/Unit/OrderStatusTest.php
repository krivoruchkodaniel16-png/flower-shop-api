<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

/**
 * Модульные тесты перечисления статусов (без БД и фреймворка).
 */
class OrderStatusTest extends TestCase
{
    public function test_allowed_transitions_follow_the_workflow(): void
    {
        $this->assertTrue(OrderStatus::New->canTransitionTo(OrderStatus::Assembling));
        $this->assertTrue(OrderStatus::Assembling->canTransitionTo(OrderStatus::Ready));
        $this->assertTrue(OrderStatus::Ready->canTransitionTo(OrderStatus::Delivering));
        $this->assertTrue(OrderStatus::Delivering->canTransitionTo(OrderStatus::Delivered));
    }

    public function test_forbidden_transitions(): void
    {
        $this->assertFalse(OrderStatus::New->canTransitionTo(OrderStatus::Delivered));
        $this->assertFalse(OrderStatus::Delivering->canTransitionTo(OrderStatus::Cancelled));
        $this->assertFalse(OrderStatus::Delivered->canTransitionTo(OrderStatus::New));
        $this->assertSame([], OrderStatus::Cancelled->allowedTransitions());
    }

    public function test_roles_allowed_to_set_status(): void
    {
        $this->assertContains(UserRole::Florist, OrderStatus::Assembling->rolesAllowedToSet());
        $this->assertNotContains(UserRole::Courier, OrderStatus::Assembling->rolesAllowedToSet());
        $this->assertContains(UserRole::Courier, OrderStatus::Delivered->rolesAllowedToSet());
        $this->assertNotContains(UserRole::Customer, OrderStatus::Cancelled->rolesAllowedToSet());
    }
}
