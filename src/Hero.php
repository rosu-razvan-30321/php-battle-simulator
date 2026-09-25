<?php

namespace App;

class Hero extends Character
{
    public function __construct()
    {
        $health = rand(65, 100);
        $strength = rand(75, 90);
        $defence = rand(40, 50);
        $speed = rand(40, 50);
        $luck = rand(10, 20) / 100;

        parent::__construct("Kratos", $health, $strength, $defence, $speed, $luck);
    }

    // Abilitatea Rapid Fire
    public function attack(Character $target): void
    {
        parent::attack($target);

        if ($target->isAlive() && (mt_rand(1, 100) <= 15)) {
            echo "[SKILL ACTIVAT] Rapid Fire! Kratos mai atacă o dată!\n";
            parent::attack($target);
        }
    }

    // Abilitatea Magic Armour
    public function receiveAttackDamage(int $damage): void
    {
        if (mt_rand(1, 100) <= 15) {
            $damage = (int) floor($damage / 2);
            echo "[SKILL ACTIVAT] Magic Armour! Kratos înjumătățește daunele!\n";
        }

        parent::receiveAttackDamage($damage);
    }
}