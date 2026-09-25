<?php

// Definim namespace-ul aplicației pentru ca Composer să știe unde să găsească clasa
namespace App;

// Clasa de bază (părinte) pentru toate caracterele din joc
class Character
{
    // Proprietăți protejate (protected): pot fi accesate doar din interiorul acestei clase și de către clasele copii (ex: Hero, Monster)
    protected string $name;
    protected int $health;
    protected int $strength;
    protected int $defence;
    protected int $speed;
    protected float $luck; // Ex: 0.15 reprezintă 15% șansă de noroc

    // Array în care vom stoca abilitățile speciale ale caracterului
    protected array $skills = [];

    // Constructorul: se apelează automat când creăm un caracter nou și îi setează atributele
    public function __construct(string $name, int $health, int $strength, int $defence, int $speed, float $luck)
    {
        $this->name = $name;         // Setează numele
        $this->health = $health;     // Setează viața
        $this->strength = $strength; // Setează puterea de atac
        $this->defence = $defence;   // Setează apărarea
        $this->speed = $speed;       // Setează viteza
        $this->luck = $luck;         // Setează norocul
    }

    // Metode de tip Getter pentru a citi proprietățile din exterior
    public function getName(): string { return $this->name; }
    public function getHealth(): int { return $this->health; }
    public function getSpeed(): int { return $this->speed; }
    public function getLuck(): float { return $this->luck; }

    // Metodă pentru adăugarea unei abilități noi în lista de skill-uri
    public function addSkill(SkillInterface $skill): void
    {
        $this->skills[] = $skill; // Adaugă skill-ul primit în array-ul $skills
    }

    // Verifică dacă caracterul mai are viață
    public function isAlive(): bool
    {
        return $this->health > 0; // Returnează true dacă HP > 0, altfel false
    }

    // Calculam dacă caracterul are noroc în runda curentă
    public function isLucky(): bool
    {
        // Generăm un numzr aleatoriu între 0.01 și 1.00 și îl comparăm cu nivelul de noroc
        return (mt_rand(1, 100) / 100) <= $this->luck;
    }

    // Metoda care scade viața caracterului cand primește daune
    public function takeDamage(int $damage): void
    {
        $this->health -= $damage;
        if ($this->health < 0) {
            $this->health = 0;
        }
    }

    // Metoda prin care caracterul executa un atac asupra unei ținte
    public function attack(Character $target): void
    {
        if ($target->isLucky()) {
            echo "{$target->getName()} a avut noroc și a evitat atacul!\n";
            return; // Se oprește atacul, nu se dau daune
        }

        $damage = $this->strength - $target->defence;

        if ($damage < 0) {
            $damage = 0;
        }

        $target->receiveAttackDamage($damage);
    }

    // Metoda prin care caracterul proceseaza daunele primite
    public function receiveAttackDamage(int $damage): void
    {
        echo "{$this->name} primește {$damage} daune!\n";
        $this->takeDamage($damage);
    }
}