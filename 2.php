<?php

//- функцию convertString($a, $b). Результат ее выполнение: если в строке $a содержится 2 и более подстроки $b,
// то во втором месте заменить подстроку $b на инвертированную подстроку.
function convertString(string $a,string $b): string
{

    $position = 0;

    for ($i = 1; $i <= 2; $i++) {
        $position = stripos($a, $b, $position);
        if ($position === false) {
            throw new InvalidArgumentException('Подстрока не найдена или меньше 2х');
        }
        if ($i == 1) {
            $position += strlen($b);
        }
    }

    return substr_replace($a, strrev($b), $position, strlen($b));
}

//- функцию mySortForKey($a, $b). $a – двумерный массив вида [['a'=>2,'b'=>1],['a'=>1,'b'=>3]],
// $b – ключ вложенного массива. Результат ее выполнения: двумерном массива $a отсортированный по возрастанию значений для ключа $b.
// В случае отсутствия ключа $b в одном из вложенных массивов, выбросить ошибку класса Exception с индексом неправильного массива.

// Я попробовал использовать алгоритм быстрой сортировки из "грокаем алгоритмы", не уверен, что получилось правильно
// и быстро, но код вроде как работает
function mySortForKey(array $a, string $b): array
{
    if (count($a) < 2) {
        return $a;
    }

    $pivot = $a[0][$b];
    $less = [];
    $more = [];
    $equal = [];

    foreach ($a as $value) {

        if (!isset($value[$b])) {
            throw new InvalidArgumentException('Не найден ключ в массиве ' . key($value));
        }

        if ($value[$b] < $pivot) {
            $less[] = $value;
        } elseif ($value[$b] > $pivot) {
            $more[] = $value;
        } else {
            $equal[] = $value;
        }
    }

    return array_merge(mySortForKey($less, $b), $equal, mySortForKey($more, $b));
}

var_dump(mySortForKey([
    ['a' => 2, 'b' => 1],
    ['a' => 3, 'b' => 3],
    ['a' => 1, 'b' => 2],
    ['a' => 5, 'b' => 5],
    ['a' => 1, 'b' => 1]
], 'a'));