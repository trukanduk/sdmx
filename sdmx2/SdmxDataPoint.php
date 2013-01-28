<?php
	/**
	 * Файл содержит описание класса точки в пространстве базы (т.е. ячейки таблицы)
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 1.0.1
	 */

	require_once('SdmxCoordinate.php');

	/**
	 * Точка в пространстве (ячейка таблицы)
	 *
	 * Содержит значение и набор координат (по которым можно пройтись итератором)
	 *
	 * @package sdmx
	 * @version 1.0.1
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
		function AddCoordinate(SdmxCoordinate $coord) {
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
		 * Получение количества координат
		 *
		 * @return int количество координат
		 */
		function GetCoordinatesCount() {
			return count($this->coordinates);
		}

		/**
		 * Сравнение двух объектов
		 *
		 * Сравнивает два объекта и возвращает отрицательное значение, если первый элемент меньше второго
		 * ноль, если они равны и положительное, если второй меньше первого
		 * Необязательный параметр <var>$axesConpareOrder</var> задаёт порядок осей для сравнения (состоит из идентификаторов осей).
		 * Например, если <var>$axesCompareOrder == ['year', 'month']</var>, то сначала объекты будут сравниваться по координате <var>'year'</var>,
		 * при равенстве -- по координате 'month'. По умолчанию берётся порядок следования осей в первом объекте (однако так делать не рекомендуется)
		 *
		 * @param SdmxDataPoint $first первый объект
		 * @param SdmxDataPoint $second второй объект
		 * @param string[] $axesCompareOrder массив с идентификаторами осей в порядке сравнения
		 * @return int отрицательное значение, если первый объект меньше второго, ноль при равенстве и положительное число, если второй меньше первого
		 */
		static function Compare(SdmxDataPoint $first, SdmxDataPoint $second, $axesCompareOrder = null) {
			$ret = 0;
			// У нас есть два случая: если у нас задан массив или если не задан.
			if (is_array($axesCompareOrder) && count($axesCompareOrder) > 0)
				for ($it = new ArrayIterator($axesCompareOrder); $ret == 0 && $it->valid(); $it->next())
					$ret = SdmxCoordinate::Compare($first->GetCoordinate($it->current()), $second->GetCoordinate($it->current()));
			else
				for ($it = $first->GetCoordinatesIterator(); $ret == 0 && $it->valid(); $it->next())
					$ret = SdmxCoordinate::Compare($it->current(), $second->GetCoordinate($it->key()));

			return $ret;
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
			echo "Coordinates: [";
			foreach ($this->GetCoordinatesIterator() as $coord)
				$coord->__DebugPrint();
			echo "] Value: {$this->GetValue()}<br>\n";
			return $this;
		}
	}
?>
