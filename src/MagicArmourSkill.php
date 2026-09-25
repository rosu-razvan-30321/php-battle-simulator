<?php

namespace App;

class MagicArmourSkill implements SkillInterface
{
    public function getName(): string
    {
        return 'Magic Armour';
    }

    public function getChance(): float
    {
        return 0.15; // 15% șansă
    }
}