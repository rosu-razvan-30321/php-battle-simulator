<?php

namespace App;

class RapidFireSkill implements SkillInterface
{
    public function getName(): string
    {
        return 'Rapid Fire';
    }

    public function getChance(): float
    {
        return 0.15;
    }
}