<?php
	/**
	 * Файл содержит описание класса <var>SdmxDimension</var> -- класс размерности базы
	 *
	 * @todo стандартные имена размерностей
	 * @todo стандартные размерности
	 * @todo конструкторы размерностей
	 * @todo сортировка?
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 0.2
	 */

	/**
	 * Ось (размерность) базы
	 *
	 * Описывает одну из размерностей базы данных.
	 * Содержит в себе идентификатор (для быстрого доступа), название на человеческом языке и набор строк-значений
	 *
	 * @package sdmx
	 * @version 0.2
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
		 * Установка значения
		 * 
		 * @param string $rawValue сырое значение (индекс в массиве, см. структуру $this->values)
		 * @param string $value "нормальное" значение
		 * @return SdmxDimension бъект-владелец метода
		 */
		function SetValue($rawValue, $value) {
			$this->values[$rawValue] = $value;
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
			echo "[ ";
			for ($it = $this->GetValuesIterator(); $it->valid(); $it->next())
				echo "({$it->key()}, {$it->current()}) ";
			echo "]<br>\n";
		}
	}
?>
