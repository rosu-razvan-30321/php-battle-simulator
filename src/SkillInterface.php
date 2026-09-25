<?php

namespace App;

interface SkillInterface
{
    public function getName(): string;
    public function getChance(): float;
}