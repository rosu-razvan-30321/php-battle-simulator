<?php

namespace App;

class BattleEngine
{
    private Hero $hero;
    private Monster $monster;

    public function __construct(Hero $hero, Monster $monster)
    {
        $this->hero = $hero;
        $this->monster = $monster;
    }

    public function startBattle(): void
    {
        echo "=== STATISTICI INIȚIALE ===\n";
        echo "{$this->hero->getName()}: HP {$this->hero->getHealth()}, Speed {$this->hero->getSpeed()}, Luck " . ($this->hero->getLuck() * 100) . "%\n";
        echo "{$this->monster->getName()}: HP {$this->monster->getHealth()}, Speed {$this->monster->getSpeed()}, Luck " . ($this->monster->getLuck() * 100) . "%\n\n";

        // Stabilim cine ataca primul (Cine are Speed mai mare, sau Luck în caz de egalitate)
        $attacker = $this->hero;
        $defender = $this->monster;

        if ($this->monster->getSpeed() > $this->hero->getSpeed()) {
            $attacker = $this->monster;
            $defender = $this->hero;
        } elseif ($this->monster->getSpeed() === $this->hero->getSpeed()) {
            if ($this->monster->getLuck() > $this->hero->getLuck()) {
                $attacker = $this->monster;
                $defender = $this->hero;
            }
        }

        echo "Primul care atacă este: {$attacker->getName()}!\n\n";

        for ($turn = 1; $turn <= 15; $turn++) {
            echo "--- TURA {$turn} ---\n";
            echo "{$attacker->getName()} atacă pe {$defender->getName()}...\n";

            $attacker->attack($defender);

            echo "HP Rămas -> {$defender->getName()}: {$defender->getHealth()}\n\n";

            // Verificam daca aparatorul a murit
            if (!$defender->isAlive()) {
                echo "{$attacker->getName()} A CÂȘTIGAT LUPTA!\n";
                return;
            }

            $temp = $attacker;
            $attacker = $defender;
            $defender = $temp;
        }

        echo "Au trecut 15 ture! Lupta s-a încheiat la egalitate.\n";
    }
}