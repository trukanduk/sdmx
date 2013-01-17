<?php
	/**
	 * Файл содержит описание класса точки в пространстве базы (т.е. ячейки таблицы)
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 0.1
	 */

	require_once('SdmxCoordinate.php');

	/**
	 * Точка в пространстве (ячейка таблицы)
	 *
	 * Содержит значение и набор координат (по которым можно пройтись итератором)
	 *
	 * @package sdmx
	 * @version 0.1
	 */
	class SdmxDataPoint implements IteratorAggregate {
		/**
		 * значение в ячейке
		 * @var string
		 */
		protected $value = '';

		/**
		 * Полученик значения в ячейки
		 *
		 * @return string значение
		 */
		function GetValue() {
			return $this->value;
		}

		/**
		 * Получение значения объекта
		 *
		 * @return string значение объекта
		 */
		function __toString() {
			return $this->GetValue();
		}

		/**
		 * Установка значения точки
		 *
		 * @param string $value новое значение
		 * @return SdmxDataPoint объект-хозяин метода
		 */
		function SetValue($value) {
			$this->value = $value;
			return $this;
		}

		/**
		 * Координаты точки
		 *
		 * Массив координат в виде <var>['$lt;AxisId>' => SdmxCoordinate()]</var>
		 * @var SdmxCoordinate[]
		 */
		protected $coordinates = array();

		/**
		 * Получение координаты
		 *
		 * @param string $axisId индекс оси (rawValue)
		 * @param mixed $default значение, возвращаемое при отсутствии искомой координаты
		 * @return mixed Искомая ось (SdmxCoordinate)
		 */
		function GetCoordinate($axisId, $default = false) {
			if (isset($this->coordinates[$axisId]))
				return $this->coordinates[$axisId];
			else
				return $default;
		}

		/**
		 * Добавление координаты
		 *
		 * После добавления нельзя менять ось! (всё остальное тоже не рекомендуется)
		 *
		 * @param SdmxCoordinate $coord добавляемая координата
		 * @return SdmxDataPoint объект-хозяин метода
		 */
		function AddCoordinate(SdmxCoordinate $cord) {
			$this->coordinates[$coord->GetAxisId('')] = $coord;
			return $this;
		}

		/**
		 * Получение итератора на массив значений
		 *
		 * @return ArrayIterator итератор на начало массива координат
		 */
		function GetCoordinatesIterator() {
			return new ArrayIterator($this->coordinates);
		}

		/**
		 * Получение итератора на объект
		 * 
		 * Равносильно вызову <var>GetCoordinatesIterator()</var>
		 * 
		 * @return ArrayIterator итератор на начало массива координат
		 */
		function GetIterator() {
			return new ArrayIterator($this->coordinates);
		}

		/**
		 * Конструктор
		 *
		 * Создаёт пустой объект (есть возможность проинициализировать значение)
		 * @param string $value значение в ячейке
		 */
		function __construct($value = '') {
			$this->SetValue($value);
		}

		function __DebugPrint() {
			for ($it = $this->GetIterator(); $it->valid(); $it->next())
				$it->current()->__DebugPrint();
		}
	}
?>
