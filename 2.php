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

// либо я что-то не понял, либо функция очень сложная, весь день с ней провозился
function importXml(string $a)
{
    $xml = simplexml_load_file($a);



    $mysqli = new mysqli('localhost', 'root', 'root', 'test_samson');

    mysqli_set_charset($mysqli, "utf8mb4");

    foreach ($xml->{'Товар'} as $product) {
        $code = (int) $product['Код'];
        $name = (string) $product['Название'];

        $mysqli->query("INSERT INTO a_product (code, name) 
        VALUES ($code, '$name')");

        $product_id = $mysqli->insert_id;

        foreach ($product->{'Цена'} as $price) {
            $type = (string) $price['Тип'];
            $priceForType = (float) $price;
            $mysqli->query("INSERT INTO a_price (product_id, price_type, price) VALUES ($product_id, '$type', $priceForType)");
        }

        foreach ($product->{'Свойства'}->children() as  $property) {
            $propertyName = $property->getName();
            $propertyValue = (string) $property  . $property->attributes()['ЕдИзм'];
            $mysqli->query("INSERT INTO a_property (product_id, property_name, property_value) VALUES ($product_id, '$propertyName', '$propertyValue')");
        }

        $parent_id = null;

        foreach ($product->{'Разделы'}->{'Раздел'} as $tag) {
            $sectionName = (string) $tag;
            $codeProd = rand(1, 100);

            $repeatCheck = $mysqli->query("SELECT id FROM a_category WHERE name = '$sectionName'");



            if ($repeatCheck->num_rows > 0) {
                $category_id = $repeatCheck->fetch_assoc()['id'];
            } else {
                $mysqli->query("INSERT INTO a_category (code, name, parent_id) VALUES ($codeProd, '$sectionName', " . ($parent_id ? $parent_id : "NULL") . ")");
                $category_id = $mysqli->insert_id;
            }

            $mysqli->query("INSERT INTO product_category (product_id, category_id) 
            VALUES ($product_id, $category_id)");

            $parent_id = $category_id;
        }

    }

    $mysqli->close();
}

function exportXml(string $filename, int $categoryId)
{
    $mysqli = new mysqli('localhost', 'root', 'root', 'test_samson');
    mysqli_set_charset($mysqli, "utf8mb4");

    // Получение всех дочерних категорий
    $ids = [$categoryId];
    //забираем пока не закончатся родители
    while ($childs == false) {
        $childs = $mysqli->query("SELECT parent_id FROM a_category WHERE id = $categoryId");
    }

    while ($row = $childs->fetch_assoc()) {
        $ids[] = $row['parent_id'];
    }
    $ids = implode("," , $ids);
    // Запрос данных из базы данных
    $query = "SELECT 
                p.id AS product_id, p.code, p.name AS product_name, 
                c.name AS category_name, 
                pr.price_type, pr.price,
                prp.property_name, prp.property_value
              FROM product_category pc
              JOIN a_product p ON pc.product_id = p.id
              JOIN a_category c ON pc.category_id = c.id
              JOIN a_price pr ON pc.product_id = pr.product_id
              JOIN a_property prp ON pc.product_id = prp.product_id 
              WHERE pc.category_id IN ($ids)";

    $result = $mysqli->query($query);

    $dom = new DOMDocument('1.0', 'windows-1251');
    $products = $dom->createElement('Товары');

    $productsData = [];

    while ($row = $result->fetch_assoc()) {
        $productId = $row['product_id'];
        if (!isset($productsData[$productId])) {
            $productsData[$productId] = [
                'code' => $row['code'],
                'name' => $row['product_name'],
                'prices' => [],
                'properties' => [],
                'categories' => []
            ];
        }
        $productsData[$productId]['prices'][$row['price_type']] = $row['price'];
        //так и не придумал как всунуть второй формат тк ключи уникальные
        $productsData[$productId]['properties'][$row['property_name']] = $row['property_value'];
        //2 раздела я впихнул, но они по сути обязательные, а свойства могут менятся, и повторятся
        $productsData[$productId]['categories'][] = $row['category_name'];
    }

    foreach ($productsData as $product) {
        $productElement = $dom->createElement('Товар');
        $productElement->setAttribute('Код', $product['code']);
        $productElement->setAttribute('Название', $product['name']);

        foreach ($product['prices'] as $type => $price) {
            $priceElement = $dom->createElement('Цена', $price);
            $priceElement->setAttribute('Тип', $type);
            $productElement->appendChild($priceElement);
        }

        $propertiesElement = $dom->createElement('Свойства');
        foreach ($product['properties'] as $name => $value) {
            $propertyElement = $dom->createElement($name, $value);
            $propertiesElement->appendChild($propertyElement);
        }
        $productElement->appendChild($propertiesElement);

        $categoriesElement = $dom->createElement('Разделы');
        foreach (array_unique($product['categories']) as $category) {
            $categoryElement = $dom->createElement('Раздел', $category);
            $categoriesElement->appendChild($categoryElement);
        }
        $productElement->appendChild($categoriesElement);

        $products->appendChild($productElement);
    }

    $dom->appendChild($products);
    $dom->save($filename);

    $mysqli->close();

}

exportXml('2-xml.xml', 3);