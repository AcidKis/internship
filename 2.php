<?php

//- функцию convertString($a, $b). Результат ее выполнение: если в строке $a содержится 2 и более подстроки $b,
// то во втором месте заменить подстроку $b на инвертированную подстроку.
function convertString(string $a,string $b)
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