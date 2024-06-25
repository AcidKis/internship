<?php

//- функцию convertString($a, $b). Результат ее выполнение: если в строке $a содержится 2 и более подстроки $b,
// то во втором месте заменить подстроку $b на инвертированную подстроку.

// добавлено: в случае если подстрока не найдена возвращаем строку как есть, а не выбрасываем исключение
function convertString(string $a,string $b): string
{

    $position = 0;

    for ($i = 1; $i <= 2; $i++) {
        $position = stripos($a, $b, $position);
        if ($position === false) {
            return $a;
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


// Добавлено: добавлена проверка на случай если не будет ключа в массиве для pivot
function mySortForKey(array $a, string $b): array
{
    if (count($a) < 2) {
        return $a;
    }


    if (!isset($a[0][$b])) {
        throw new InvalidArgumentException('Не найден ключ в массиве ');
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


//Правки по бд: хранение свойств товара в отдельной таблице, связанная таблица product_property, добавлена уникальность
//нескольким столбцам

//по импорту экспорту: парсинг выведен в отдельную функцию, наконец-то получилось сделать чтобы и выглядело более менее
//по человечески, и чтобы все работало все теги,свойства категории прокидваюься, также использовал многомерный массив
//и там и там для удобства понимания

//не хотел возится с установкой xDebug :P
function vardump($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}

function parseFile(string $a): array
{
    $xml = simplexml_load_file($a);
    $productsData = [];
    foreach ($xml->{'Товар'} as $product) {

        $productsData[(int) $product['Код']] = [
            "Название продукта" => (string) $product['Название'],
            "Цена" => [],
            "Свойства" => [],
            "Разделы" => [],
        ];

        foreach ($product->{'Цена'} as $price) {
            $productsData[(int) $product['Код']]["Цена"][(string)$price["Тип"]] = (float) $price;
        }

        foreach ($product->{'Свойства'}->children() as $property) {
            $productsData[(int) $product['Код']]["Свойства"][(string)$property->getName()][] = "$property" . (isset($property["ЕдИзм"]) ? " $property[ЕдИзм]" : "");
        }

        foreach ($product->{'Разделы'}->{'Раздел'} as $category) {
            $productsData[(int) $product['Код']]["Разделы"][] = (string) $category;
        }
    }

    return $productsData;
}


function importXml(string $a): void
{

    $mysqli = new mysqli('localhost', 'root', 'root', 'test_samson');

    mysqli_set_charset($mysqli, "utf8mb4");

    foreach (parseFile($a) as $code => $product) {
        $name = $product['Название продукта'];

        $result = $mysqli->query("SELECT id FROM a_product WHERE product_code='$code'");
        if ($result->num_rows == 0) {
            $mysqli->query("INSERT INTO a_product (product_code, name) VALUES ('$code', '$name')");
            $product_id = $mysqli->insert_id;
        } else {
            $row = $result->fetch_assoc();
            $product_id = $row['id'];
        }

        foreach ($product['Цена'] as $type => $price) {
            $type = $mysqli->real_escape_string($type);
            // Проверка существования цены
            $result = $mysqli->query("SELECT id FROM a_price WHERE product_id='$product_id' AND price_type='$type'");
            if ($result->num_rows == 0) {
                $mysqli->query("INSERT INTO a_price (product_id, price_type, price) VALUES ('$product_id', '$type', '$price')");
            }
        }

        foreach ($product['Свойства'] as $property_name => $property_values) {
            foreach ($property_values as $value) {
                $property_unit = "";

                if (str_contains($value, " ")) {
                    list($value, $property_unit) = explode(" ", $value);
                }

                // Проверка существования свойства
                $result = $mysqli->query("SELECT id FROM a_property WHERE property_name='$property_name' AND property_unit='$property_unit'");
                if ($result->num_rows == 0) {
                    $mysqli->query("INSERT INTO a_property (property_name, property_unit) VALUES ('$property_name', '$property_unit')");
                    $property_id = $mysqli->insert_id;
                } else {
                    $row = $result->fetch_assoc();
                    $property_id = $row['id'];
                }

                $result = $mysqli->query("SELECT * FROM product_property WHERE product_id='$product_id' AND property_id='$property_id' AND property_value='$value'");
                if ($result->num_rows == 0) {
                    $mysqli->query("INSERT INTO product_property (product_id, property_id, property_value) VALUES ('$product_id', '$property_id', '$value')");
                }
            }
        }

        $parent_id = null;

        foreach ($product['Разделы'] as $category_name) {
            // Проверка существования категории
            $category_code = md5($category_name);

            $result = $mysqli->query("SELECT id FROM a_category WHERE category_name='$category_name'");
            if ($result->num_rows == 0) {

                if (isset($parent_id)) {
                    $mysqli->query("INSERT INTO a_category (category_name, parent_id, category_code) VALUES ('$category_name' , '$parent_id', '$category_code')");
                } else {
                    $mysqli->query("INSERT INTO a_category (category_name, category_code) VALUES ('$category_name', '$category_code')");
                }

                $category_id = $mysqli->insert_id;
                $parent_id = $category_id;

            } else {
                $row = $result->fetch_assoc();
                $category_id = $row['id'];
            }

            $result = $mysqli->query("SELECT * FROM product_category WHERE product_id='$product_id' AND category_id='$category_id'");
            if ($result->num_rows == 0) {
                $mysqli->query("INSERT INTO product_category (product_id, category_id) VALUES ('$product_id', '$category_id')");
            }

        }

    }


    $mysqli->close();
}

function exportXml(string $filename, string $categoryCode): void
{
    $mysqli = new mysqli('localhost', 'root', 'root', 'test_samson');
    mysqli_set_charset($mysqli, "utf8mb4");
    // Забираем всех детей с категории
    $result = $mysqli->query("SELECT id FROM a_category WHERE category_code = '$categoryCode'");
    $categoryId= $result->fetch_row();
    $ids = [(int) $categoryId[0]];
    $childs = null;
    while ($childs == false) {
        $check = end($ids);
        $childs = $mysqli->query("SELECT id FROM a_category WHERE parent_id = '$check'");
        while ($row = $childs->fetch_assoc()) {
            $ids[] = (int) $row['id'];
        }
    }

    $ids = implode("," , $ids);

    // Запрос данных из базы данных
    $query = "SELECT pc.product_id, pc.category_id, prd.product_code, prd.name, pp.property_value, prp.property_name, prp.property_unit, c.category_name, prc.price_type, prc.price
	
	FROM product_category pc
    LEFT JOIN a_product prd on pc.product_id = prd.id
	JOIN a_price prc on pc.product_id = prc.product_id
    JOIN product_property pp on pc.product_id = pp.product_id
    JOIN a_property prp on pp.property_id = prp.id
    JOIN a_category c ON pc.category_id = c.id
    
	WHERE pc.category_id IN ('$ids')";

    $result = $mysqli->query($query);

    $dom = new DOMDocument('1.0', 'windows-1251');
    $products = $dom->createElement('Товары');

    $productsData = [];

    while ($row = $result->fetch_assoc()) {
        $productCode = $row['product_code'];
        if (!isset($productsData[$productCode])) {
            $productsData[$productCode] = [
                'Название продукта' => $row['name'],
                'Цена' => [],
                'Свойства' => [],
                'Разделы' => []
            ];
        }
        //цены
        $productsData[$productCode]['Цена'][$row['price_type']] = $row['price'];
        //свойства

        //для избежания предупреждения что ключа нет
        if (!isset($productsData[$productCode]['Свойства'][(string)$row['property_name']])) {
            $productsData[$productCode]['Свойства'][(string)$row['property_name']] = [];
        }

        if (!in_array($row['property_value'] . ($row['property_unit'] != '' ? " " . $row['property_unit'] : ""), $productsData[$productCode]['Свойства'][(string)$row['property_name']]) ){
            $productsData[$productCode]['Свойства'][$row['property_name']][] = $row['property_value'] . ($row['property_unit'] != '' ? " " . $row['property_unit'] : "");
        }

        //Разделы
        if (!in_array($row['category_name'],$productsData[$productCode]['Разделы'])){
            $productsData[$productCode]['Разделы'][] = $row['category_name'];
        }

    }

    vardump($productsData);

    foreach ($productsData as $code => $product) {
        $productElement = $dom->createElement('Товар');
        $productElement->setAttribute('Код', $code);
        $productElement->setAttribute('Название', $product['Название продукта']);

        foreach ($product['Цена'] as $type => $price) {
            $priceElement = $dom->createElement('Цена', $price);
            $priceElement->setAttribute('Тип', $type);
            $productElement->appendChild($priceElement);
        }

        $propertiesElement = $dom->createElement('Свойства');
        foreach ($product['Свойства'] as $name => $value) {
            foreach ($value as $propValues) {
                if (str_contains($propValues, " ")) {
                    list($propValue, $property_unit) = explode(" ", (string)$propValues);
                    $propertyElement = $dom->createElement($name, $propValue);
                    $propertyElement->setAttribute('ЕдИзм', $property_unit);
                } else {
                    $propertyElement = $dom->createElement($name, $propValues);
                }
                $propertiesElement->appendChild($propertyElement);
            }
        }
        $productElement->appendChild($propertiesElement);

        $categoriesElement = $dom->createElement('Разделы');
        foreach (array_unique($product['Разделы']) as $category) {
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
exportXml('2-xml.xml','7543b9cbdc39385afbd10412f30cef08');
