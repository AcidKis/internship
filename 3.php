<?php
namespace Test3;

use http\Exception\InvalidArgumentException;

class newBase
{
    //вынес переменные вверх для удобства
    static private int $count = 0;
    static private array $arSetName = [];
    protected $name; //заменил на protected так как будет использоваться наследником
    protected $value;

    // так и не до конца понял чему верить param или коду, но судя по тому что в коде для тестирования числа в кавычках, решил сделать строкой
    /**
     * @param string $name
     */
    function __construct(string $name = '0')
    {
        if (empty($name) || $name == '0') { //не учитывало что в $name прилетит 0
            while (in_array(self::$count, self::$arSetName)) { //заменил на in_array так как она тут лучше подходит
                ++self::$count;
            }
            $name = (string)self::$count; //привести к строке
        }
        $this->name = $name;
        self::$arSetName[] = $this->name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return '*' . $this->name  . '*';
    }

    /**
     * @param mixed $value
     */
    public function setValue(mixed $value): self// добавил тип приходящего значения и тип возращаемого
    {
        //для избежания предупреждения о том что в load используется void метод попробовал возвращать объект как в Fluent interface
        $this->value = $value;
        return $this;
    }
    /**
     * @return string
     */
    public function getSize(): string //судя по описанию выше должны вернуть строку, добавляем возвращаемый тип
    {
        // не до конца понял смысл этой функции, что за размер мы хотим получить, зачем сериализация если это не объект и вроде не json туда суем
        // но если код сам по себе правильный то нам нужно перевести первый $size к строке так как после функции strlen она является int
        // вообще глупость возвращать сумму длинны строки сериализированных данных с длинной строки длинны строки сериализированных данных, по хорошему вернуть просто $size
        $size = strlen(serialize($this->value));
        return (string)$size;
    }
    public function __sleep(): array // срабатывает при сериализации, по сути отправляет на сериализацию только $value
    {
        return ['value', 'name'];
    }
    /**
     * @return string
     */
    public function getSave(): string // возвращаем string
    {
        $value = serialize($this->value); //добавлена this
        return $this->name . ':' . strlen($value) . ':' . $value; //заменил sizeof на strlen потому что sizeof считает кол-во элементов массива, что тут неуместно
    }
    /**
     * @return newBase
     */
    static public function load(string $value): newBase // тут загружаем сохраненный объект
    {
        $arValue = explode(':', $value); //разбиваем на массив прошлый сейв
        return (new newBase($arValue[0]))
            ->setValue(unserialize($arValue[2])); // под нулевым ключем хранится имя, только приводим его к int
        // не совсем понял для чего такие сложности если можно просто setValue(unserialize($arValue[2]))
        //PS все таки понял почему такие сложности сериализация содержит наш разделитель, в дальнейшем пригодится :c
    }
}
class newView extends newBase
{
    private $type = null;
    private $size = 0;
    private $property = null;
    /**
     * @param mixed $value
     */
    public function setValue(mixed $value): self // тут тоже добавляет self чтобы не ругался
    {
        parent::setValue($value);
        $this->setType();
        $this->setSize();
        return $this;
    }
    public function setProperty($value): self //так как возвращаем this
    {
        $this->property = $value;
        return $this;
    }
    private function setType(): self
    {
        $this->type = gettype($this->value);
        return $this;
    }
    private function setSize(): self
    {
        if ($this->value instanceof newView) { //более удобно проверить с помощью instanceof
            $this->size = (int)parent::getSize() + 1 + strlen($this->property); //пререводим в int
        } elseif ($this->type == 'test') {
            $this->size = parent::getSize();
        } else {
            $this->size = strlen(serialize($this->value)); //привод к строке
        }
        return $this;
    }

    /**
     * @return array
     */

    // поправил @return __sleep должен возвращать массив
    public function __sleep(): array
    {
        return ['property'];
    }
    /**
     * @return string
     */
    public function getName(): string
    {
        if (empty($this->name)) {
            throw new InvalidArgumentException('The object doesn\'t have name'); //заменил на другое предупреждение
        }
        return '"' . $this->name  . '": ';
    }

    // ниже добавил проверки на наличие других свойств
    /**
     * @return string
     */
    public function getType(): string
    {
        if (empty($this->name)) {
            throw new InvalidArgumentException('The object doesn\'t have type');
        }
        return ' type ' . $this->type  . ';';
    }
    /**
     * @return string
     */
    public function getSize(): string
    {
        if (empty($this->name)) {
            throw new InvalidArgumentException('The object doesn\'t have size value');
        }
        return ' size ' . $this->size . ';';
    }
    public function getInfo(): void
    {
        try {
            echo $this->getName()
                . $this->getType()
                . $this->getSize()
                . "\r\n";
        } catch (InvalidArgumentException $exc) {
            echo 'Error: ' . $exc->getMessage();
        }
    }
    /**
     * @return string
     */
    public function getSave(): string
    {
        if ($this->type == 'test') {
            $this->value = $this->value->getSave(); //??
        }
        return parent::getSave() . serialize($this->property);
    }
    /**
     * @return newView
     */
    static public function load(string $value): newView // меняю на newView так как загружаем уже его
    {
        $arValue = explode(':', $value);
        vardump(unserialize(substr($value, strlen($arValue[0]) + 1 + strlen($arValue[1]) + 1, $arValue[1])));
        return (new newView($arValue[0]))
            ->setValue(unserialize(substr($value, strlen($arValue[0]) + 1 + strlen($arValue[1]) + 1, $arValue[1])))
            ->setProperty(unserialize(substr($value, strlen($arValue[0]) + 1 + strlen($arValue[1]) + 1 + $arValue[1])));

    }
}
function gettype($value): string //в целом была странная проверка на тип с бесконечными циклами, поменял
{
    if (is_object($value)) {
        $type = get_class($value);
    } else {
        $type = "Not a object";
    }
    return $type;
}

function vardump($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}

$obj = new newBase('12345');
$obj->setValue('text');



$obj2 = new \Test3\newView('O9876');
$obj2->setValue($obj);
$obj2->setProperty('field');
$obj2->getInfo();


$save = $obj2->getSave();
$obj3 = newView::load($save);


vardump($obj2->getSave() == $obj3->getSave());

