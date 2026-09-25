<?php

namespace App;

class Monster extends Character
{
    public function __construct()
    {
        $health = rand(50, 80);
        $strength = rand(55, 80);
        $defence = rand(50, 70);
        $speed = rand(40, 60);
        $luck = rand(30, 45) / 100;

        parent::__construct("Wild Monster", $health, $strength, $defence, $speed, $luck);
    }
}