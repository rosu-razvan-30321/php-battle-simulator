<?php

namespace Tests;

use App\Character;
use PHPUnit\Framework\TestCase;

class CharacterTest extends TestCase
{
    // Test 1: Verificam ca formula de damage calculează corect (Strength - Defence)
    public function testDamageCalculation(): void
    {
        $attacker = new Character("Attacker", 100, 80, 40, 50, 0.0);
        $defender = new Character("Defender", 100, 50, 50, 40, 0.0);

        $attacker->attack($defender);

        $this->assertEquals(70, $defender->getHealth());
    }

    // Test 2: Verificam că viața nu scade sub 0
    public function testHealthCannotBeNegative(): void
    {
        $character = new Character("Hero", 20, 50, 10, 40, 0.0);

        $character->takeDamage(50);

        $this->assertEquals(0, $character->getHealth());
    }

    // Test 3: Verificam starea de viața (isAlive)
    public function testIsAliveStatus(): void
    {
        $character = new Character("Hero", 50, 50, 10, 40, 0.0);

        $this->assertTrue($character->isAlive());

        $character->takeDamage(50);

        $this->assertFalse($character->isAlive());
    }
}