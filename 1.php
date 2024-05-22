<?php
function checkSimple(int $num): bool
{
    if ($num <= 1) {
        return false;
    }

    for ($i = 2; $i <= sqrt($num); $i++) {
        if ($num % $i == 0) {
            return false;
        }
    }

    return true;
}
function findSimple(int $a,int $b): array
{
    if ($a < 0 || $b < 0)   {
        throw new InvalidArgumentException('Число должно быть положительным');
    }

    if ($a > $b) {
        throw new InvalidArgumentException('Первое должно быть меньше второго');
    }

    if ($a === $b) {
        throw new InvalidArgumentException('Числа не должны быть равны');
    }

    $result = [];
    for ($i = $a; $i <= $b; $i++) {
        if (checkSimple($i)) {
            $result[] = $i;
        }
    }

    return $result;
}

function createTrapeze(array $a): array
{

    if (empty($a)) {
        throw new InvalidArgumentException('Массив пустой');
    }

    if (count($a) % 3 != 0) {
        throw new InvalidArgumentException('Количество элементов в массиве должно быть кратно 3');
    }

    foreach ($a as $value) {
        if ($value < 0) {
            throw new InvalidArgumentException('Все элементы должны быть положительны');
        }
    }

    $result = [];

    foreach (array_chunk($a, 3) as $chunk) {
        $result[] = array_combine(['a', 'b', 'c'], $chunk);
    }

    return $result;
}

function squareTrapeze(array &$a): void
{
    foreach ($a as &$value) {

        if (!isset($value['a'], $value['b'], $value['c'])) {
            throw new InvalidArgumentException('Массив должен содержать ключи a b c на латинице');
        }

        if (!is_numeric($value['a']) || !is_numeric($value['b']) || !is_numeric($value['c'])) {
            throw new InvalidArgumentException('Массив должен содержать числовые значения');
        }


        $value['s'] = ($value['a'] + $value['b']) / 2 * $value['c'];
    }


}

// поправил опечатку в условии
function getSizeForLimit(array $a, float $b): array
{

    if (empty($a)) {
        return [];
    }

    $result = [];

    foreach ($a as $value) {
        // наверное быстрее будет проверять по одному условию поэтому заменил !array_key_exists('s', $value) || $value['s'] === null на !isset($value['s'])
        if (!isset($value['s'])) {
            throw new InvalidArgumentException('Должен быть ключ s или ключ равен null');
        }

        if (empty($result) || $value['s'] <= $b && $value['s'] > $result['s']) {
            $result = $value;
        }
    }

    return $result;

}

function getMin(array $a)
{
    $min = $a[array_key_first($a)];
    foreach ($a as $value) {
        if ($value < $min) {
            $result = $value;
            $min = $result;
        }
    }

    return $result;

}

function printTrapeze($a)
{
    echo "<table border='6'>";
    echo "<tr><th>Номер трапеции</th><th>a</th><th>b</th><th>c</th><th>s</th></tr>";
    $number = 1;
    foreach ($a as $value) {
        echo "<tr>";
        echo "<td>{$number}</td>";
        echo "<td>{$value['a']}</td>";
        echo "<td>{$value['b']}</td>";
        echo "<td>{$value['c']}</td>";
        if ($value['s'] % 2 == 0) {
            echo "<td>{$value['s']}</td>";
        } else {
            echo "<td bgcolor='#f0ffff'>{$value['s']} . ' нечетная площадь'}</td>";
        }
        $number++;
    }
}

abstract class BaseMath
{
    public function exp1(float $a, float $b, float $c): int
    {
        return $a * ($b ** $c);
    }

    public function exp2(float $a,float $b,float $c): float
    {
        return ($a / $b) ** $c;
    }

    abstract public function getValue(): float;

}

class F1 extends BaseMath
{
    public function __construct(private readonly float $a, private readonly float $b, private readonly float $c)
    {
    }

    public function getValue(): float
    {
        $exp1 = $this->exp1($this->a, $this->b, $this->c);
        $exp2 = $this->exp2($this->a, $this->c, $this->b);

        return ($exp1 + (($exp2 % 3) ** min($this->a, $this->b, $this->c)));
    }
}