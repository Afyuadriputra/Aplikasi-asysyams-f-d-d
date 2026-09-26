<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SemesterResource;
use App\Filament\Resources\SemesterResource\RelationManagers\PaymentsRelationManager;
use Tests\TestCase;

class SemesterPaymentRelationTest extends TestCase
{
    public function test_semester_resource_registers_payments_relation_manager(): void
    {
        $relations = SemesterResource::getRelations();

        // BUG REPRODUCED: SemesterResource getRelations() returns empty array []
        $this->assertContains(
            PaymentsRelationManager::class,
            $relations,
            'SemesterResource must register PaymentsRelationManager'
        );
    }
}