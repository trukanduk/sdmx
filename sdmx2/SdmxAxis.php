<?php
	/**
	 * Описание класса <var>SdmxDimension</var> -- класс размерности базы
	 *
	 * @todo стандартные имена размерностей
	 * @todo стандартные размерности
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 1.1
	 */

	/**
	 * Ось (размерность) базы
	 *
	 * Описывает одну из размерностей базы данных.
	 * Содержит в себе идентификатор (для быстрого доступа), название на человеческом языке и набор строк-значений
	 *
	 * @package sdmx
	 * @version 1.1
	 */
	class SdmxAxis implements IteratorAggregate {
		/**
		 * Тип оси
		 *
		 * Тип оси (размерность/аттрибут). Сделан скорее для дебага)
		 * @var string
		 */
		protected $type = 'unknown';

		/**
		 * Получение типа оси
		 *
		 * @return string тип оси
		 */
		function GetType() {
			return $this->type;
		}

		/**
		 * Установка типа оси
		 *
		 * @param string $type новый тип оси
		 * @return SdmxAxis объект-хозяин метода
		 */
		function SetType($type) {
			$this->type = $type;
			return $this;
		}

		/**
		 * Имя размерности
		 *
		 * Идентификатор размерности на человеческом языке
		 * @var string
		 */
		protected $name = '';

		/**
		 * Получение имени
		 *
		 * @return string имя размерности
		 */
		function GetName() {
			return $this->name;
		}

		/**
		 * Установка имени
		 *
		 * @param string $name новое имя размерности
		 * @return SdmxDimension объект-хозяин метода
		 */
		function SetName($name) {
			$this->name = $name;
			return $this;
		}


		/**
		 * Идентификатор размерности
		 *
		 * Это - внутренний идентификатор размерности. В некоторых случаях (собственно, в случае аттрибутов)
		 * у размерности есть только идентификатор, но не имя. В таких случаях есть список стандартных имён (см. далее)
		 *
		 * @var string
		 */
		protected $id = '';

		/**
		 * Получение идентификатора
		 *
		 * @return string дентификатор размерности
		 */
		function GetId() {
			return $this->id;
		}

		/**
		 * Установка идентификатора
		 *
		 * @param string $id новый идентификатор
		 * @return SdmxDimension объект-хозяин метода
		 */
		function SetId($id) {
			$this->id = $id;
			return $this;
		}

		/**
		 * Массив приоритетов значений
		 *
		 * Маленький приоритет при сортировке стоит раньше (т.е. приоритет с возрастанием числа уменьшается)
		 * Необходимо для сортировки. Компаратор по сути возвращает разницу приоритетов.
		 * У каждого значения на оси есть свой приоритет (выставляется по ходу добавления, чем позже вставлен, тем больше приоритет)
		 * Массив имеет вид <var>['&lt;сырое значение>' => &lt;приоритет>]</var>
		 *
		 * @var int[]
		 */
		protected $valuesPriorities = array();

		/**
		 * Получение приоритета значения
		 *
		 * Метод возвращает приоритет значения по "сырому" значению
		 *
		 * @param string $rawValue сырое значение
		 * @return int приоритет значения. Если такое значение отсутствовало, вернётся <var>-1</var>
		 */
		function GetPriority($rawValue) {
			if (isset($this->valuesPriorities[$rawValue]))
				return $this->valuesPriorities[$rawValue];
			else
				return -1;
		}

		/**
		 * Максимальное значение приоритета
		 *
		 * Для добавления новых приоритетов необходимо знать мексимальный текущий приоритет
		 *
		 * @var int
		 */
		protected $maxPrior = 0;

		/**
		 * обновление приоритетов
		 *
		 * Обновляет приоритеты в сответствии с их позицией в массиве
		 * Записывает в ячейку с приориетом конкретного значения его порядковый номер в массиве значений
		 *
		 * @return SdmxAxis объект-хозяин метода
		 */
		function UpdatePriorities() {
			$this->maxPrior = 0;
			$this->valuesPriorities = array();
			foreach ($this->values as $rawValue => $value)
				$this->AddPriority($rawValue);
			return $this;
		}

		/**
		 * Добавление приоритета
		 *
		 * Добавляет приоритет, инкрементирует значение приоритета.
		 * Если приоритет уще существовал, то не трогаем его
		 *
		 * @param string $rawValue "сырое" значение, приоритет которому надо выставить
		 * @return SdmxAxis объект-хозяин метода
		 */
		function AddPriority($rawValue) {
			if ( ! isset($this->valuesPriorities[$rawValue]))
				$this->valuesPriorities[$rawValue] = $this->maxPrior++;
			return $this;
		}

		/**
		 * Сравнение двух значений
		 *
		 * Сравнивает значения по их приоритету
		 *
		 * @param string $first первое сравниваемое значение
		 * @param string $second второе сравниваемое значение
		 * @return int отрицательное, если первое стоит раньше второго, <var>0</var> при равенстве приоритетов, положительное, если второе больше 
		 */
		function ComparePriorities($first, $second) {
			return ($this->GetPriority($first) - $this->GetPriority($second));
		}

		/**
		 * Массив значений размерности
		 *
		 * Массив со всевозмодными значениями в форме <var>['&lt;сырое значение>' => '&lt;конечное значение>']</var>
		 * В случае Codelist'ов "сырое" и "конечное" значения будут различаться, в случае аттрибутов - совпадать.
		 *
		 * @var string[]
		 */
		protected $values = array();

		/**
		 * Получение итератора на массив значений
		 *
		 * @return ArrayIterator итератор на начало массива значений размерности
		 */
		function GetValuesIterator() {
			return new ArrayIterator($this->values);
		}

		/**
		 * Получение итератора на массив приоритетов
		 *
		 * @return ArrayIterator Итератор на начало массива приоритетов 
		 */
		function GetPrioritiesIterator() {
			return new ArrayIterator($this->valuesPriorities);
		}

		/**
		 * Получение итератора на массив значений
		 *
		 * @return ArrayIterator итератор на начало массива значений размерности
		 */
		function GetIterator() {
			return new ArrayIterator($this->values);
		}

		/**
		 * Получение значения
		 * 
		 * @param string $rawValue "сырое" значение (см. структуру $this->values)
		 * @param mixed $default возвращается, если значение не найдено
		 * @return mixed искомое значение (строка) или <var>$default</var>, если не найдёно
		 */
		function GetValue($rawValue, $default = false) {
			if (isset($this->values[$rawValue]))
				return $this->values[$rawValue];
			else
				return $default;
		}

		/**
		 * Добавление значения
		 *
		 * Если "сырое" значение дублируется, то ничего не меняется (ни приоритет, ни само значение)
		 * 
		 * @param string $rawValue сырое значение (индекс в массиве, см. структуру $this->values)
		 * @param string $value "нормальное" значение
		 * @return SdmxDimension бъект-владелец метода
		 */
		function AddValue($rawValue, $value) {
			if (isset($this->values[$rawValue]))
				return $this;

			// само значение
			$this->values[$rawValue] = $value;

			// теперь приоритет (если был -- не трогаем)
			$this->AddPriority($rawValue);

			// Если самый крутой -- выставим в качестве значения по умолчанию
			if ($this->GetPriority($rawValue) == 0)
				$this->SetDefaultRawValue($rawValue);

			return $this;
		}

		/**
		 * Удаление значения
		 *
		 * Не знаю, зачем это может пригодится, но на всякий случай сделаю
		 * Удаяет значение из массива значений
		 *
		 * @param string $rawValue "сырое" значение
		 * @return SdmxDimension объект-хозяин метода
		 */
		function UnsetValue($rawValue) {
			if (isset($this->values[$rawValue]))
				unset($this->values[$rawValue]);
			return $this;
		}

		/**
		 * Количество различных значений
		 *
		 * Возвращает размер массива значений
		 *
		 * @return int количество значений размерности
		 */
		function GetValuesCount() {
			return count($this->values);
		}

		/**
		 * Значегие по умолчанию
		 *
		 * Значение оси, которое присваивается точке при отсутствии значения по оси (см. OKSM)
		 * Если не было выставлено специально, таковым считается первый добавленный элемент
		 * @var string
		 */
		protected $defaultRawValue;

		/**
		 * Получение сырого значения по умолчанию
		 *
		 * @return string сырое значение
		 */
		function GetDefaultRawValue() {
			return $this->defaultRawValue;
		}

		/**
		 * Установка сырого значения по умолчанию
		 *
		 * @param string $rawValue сырое значение, которое будет выставлено в качестве значения по умолчанию
		 * @return SdmxAxis объект-хозяин метода
		 */
		function SetDefaultRawValue($rawValue) {
			$this->defaultRawValue = $rawValue;
			return $this;
		}

		/**
		 * Сортировка значений
		 *
		 * Сортирует массив значений по "сырым" значениям, используя переданную функцию <var>$cmp</var>
		 * По умолчанию $cmp -- стандартная операция <strong>натуральной</strong> сортировки строк
		 * Функция также полностью перераспределяет приоритеты
		 *
		 * @param callable $cmp Оператор сравнения -- int $cmp(mixed $a, mixed $b)
		 * @return SdmxAxis объект-хозяин метода
		 */
		function SortValues($cmp = null) {
			// осортим сам массив
			if ( ! $cmp) 
				ksort($this->values, SORT_NATURAL);
			else
				uksort($this->values, $cmp);

			// теперь переставим приоритеты
			$this->UpdatePriorities();
			return $this;
		}

		/**
		 * Конструктор
		 *
		 * Создаёт пустую безликую ось
		 */
		function __construct() {}

		/**
		 * Конструктор для аттрибутов
		 *
		 * Создаёт новый объект с заданным <var>$id</var> и таким же именем
		 *
		 * @param string $id идентификатор новой оси и оно же -- его имя
		 * @return SdmxAxis новый экземпляр класса
		 */
		static function CreateAttributeAxis($id) {
			$ret = new self();
			$ret->SetId($id)
			    ->SetName($id)
			    ->SetType('attribute');
			return $ret;
		}

		/**
		 * Конструктор для размерностей
		 *
		 * Создаёт новый объект с заданным <var>$id</var> и таким же именем
		 *
		 * @param string $id идентификатор новой оси и оно же -- его имя
		 * @return SdmxAxis новый экземпляр класса
		 */
		static function CreateDimensionAxis($id, $name) {
			$ret = new self();
			$ret->SetId($id)
			    ->SetName($name)
			    ->SetType('dimension');
			return $ret;
		}

		function __DebugPrint() {
			echo "Id: '{$this->GetId()}', Type: '{$this->GetType()}', Name: '{$this->GetName()}', Values count: {$this->GetValuesCount()}<br>\n";
			echo "Values: [ ";
			//for ($it = $this->GetValuesIterator(); $it->valid(); $it->next())
			foreach ($this->GetValuesIterator() as $key => $value)
				echo "(raw={$key}, val={$value}, prior={$this->GetPriority($key)}) ";
			echo "]<br>\n";
			return $this;
		}
	}
?>
