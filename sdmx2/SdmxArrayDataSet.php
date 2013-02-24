<?php
	/**
	 * Содержит описание класса <var>SdmxArrayDataSet</var>.
	 *
	 * Простейшая реализация <var>ISdmxDataSet</var>.
	 * 
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 2.0
	 */

	require_once('ISdmxDataSet.php');
	require_once('SdmxCoordinate.php');
	require_once('SdmxDataPoint.php');
	require_once('SdmxAxis.php');

	/**
	 * Итератор по осям с определённой "фиксированностью"
	 *
	 * @see SdmxArrayDataSet::axesValues
	 * @package sdmx
	 * @version 2.0
	 */
	class SdmxArrayDataSetFixedAxesIterator implements Iterator {
		/**
		 * Множество
		 *
		 * @var SdmxArrayDataSet
		 */
		protected $dataSet;

		/**
		 * Получение множества точек
		 *
		 * @return SdmxArrayDataSet множество точек
		 */
		function GetDataSet() {
			return $this->dataSet;
		}

		/**
		 * Итератор на массив осей в множестве
		 * 
		 * @var ArrayIterator
		 */
		protected $axesIterator;

		/**
		 * Указывает ли внутренний итератор на правильную ось
		 *
		 * @return bool
		 */
		protected function IsCorrectIterator() {
			return ($this->dataSet->IsAxisFixed($this->axesIterator->key()) == $this->areAxesFixed);
		}

		/**
		 * "Фиксированность" рассматриваемых осей
		 * 
		 * Итератор прыгает по осям со значением <var>SdmxArrayAxis::IsAxisFixed()</var>, равным <var>$areAxesFixed</var>
		 * 
		 * @var bool
		 */
		protected $areAxesFixed;

		/**
		 * Текущее значение
		 *
		 * @return SdmxAxis текущая "подходящая" ось
		 */
		function current() {
			return $this->axesIterator->current();
		}

		/**
		 * Идентификатор оси
		 *
		 * @return scalar идентификатор оси
		 */
		function key() {
			return $this->axesIterator->key();
		}

		/**
		 * Следующее значение итератора
		 *
		 * @return void
		 */
		function next() {
			if ( ! $this->axesIterator->valid()) 
				return;

			$this->axesIterator->next();

			while ($this->axesIterator->valid() && ! $this->IsCorrectIterator())
				$this->axesIterator->next();
		}

		/**
		 * Первое значение
		 *
		 * @return void
		 */
		function rewind() {
			$this->axesIterator->rewind();
			if ( ! $this->IsCorrectIterator())
				$this->next();
		}

		/**
		 * Рабочий ли итератор
		 *
		 * @return bool
		 */
		function valid() {
			return $this->axesIterator->valid();
		}

		/**
		 * Конструктор
		 *
		 * @param SdmxArrayDataSet $dataSet родительское множество точек
		 * @param bool $fixType мы будем прыгать по фиксированным или нефиксированным осям?
		 */
		function __construct(SdmxArrayDataSet $dataSet, $fixType) {
			$this->dataSet = $dataSet;
			$this->areAxesFixed = $fixType;
			$this->axesIterator = $dataSet->GetAxesIterator();
		}
	}

	/**
	 * Простейший одномерный DataSet
	 *
	 * Класс реализовывает простейший <var>DataSet</var>, состоящий из одного массива.
	 * Не соптимизирован ни для каких типов запросов.
	 *
	 * @package sdmx
	 * @version 1.0
	 */
	class SdmxArrayDataSet implements ISdmxDataSet, IteratorAggregate {
		/**
		 * Массив осей
		 * 
		 * @var SdmxAxis[]
		 */
		protected $axes = array();

		/**
		 * Массив значений осей
		 * 
		 * В множестве могут не использоваться все возможные значения.
		 * Массив имеет вид: <var>['axisId' => ['value1' => 'value1, 'value2' => 'value2, ...] ]</var>, где 'value1', 'value2' и т.д. --
		 * реально используемые в множестве значения.
		 * Если у какой-то оси только одно значение, то эта ось обзывается "фиксированной"
		 *
		 * @var string
		 */
		protected $axesValues = array();


		/**
		 * Индексированный массив значений осей
		 * 
		 * В множестве могут не использоваться все возможные значения.
		 * Массив имеет вид: <var>['axisId' => [$ind => 'value1, $ind => 'value2, ...] ]</var>, где 'value1', 'value2' и т.д. --
		 * реально используемые в множестве значения.
		 * Если у какой-то оси только одно значение, то эта ось обзывается "фиксированной"
		 *
		 * @var string
		 */
		protected $axesValuesByInd = array();

		/**
		 * Получение оси
		 *
		 * @param string $axisId Идентификатор требуемой оси
		 * @param mixed $default значение по умолчанию -- в случае отсутствия искомой оси
		 * @return mixed искомая ось (<var>SdmxAxis</var>) или <var>$default</var>.
		 */
		function GetAxis($axisId, $default = false) {
			if (isset($this->axes[$axisId]))
				return $this->axes[$axisId];
			else
				return $default;
		}

		/**
		 * Добавление новой оси
		 *
		 * Сначала в объект должны загоняться оси, а потом уже точки. Если добавляется ось к набору точек,
		 * то вываливается исключение
		 *
		 * @throws Exception Если в объекте же содержались точки
		 * @param SdmxAxis $axis новая ось
		 * @return ISdmxDataSet объект-хозяин объекта
		 */
		function AddAxis(SdmxAxis $axis) {
			if (count($this->points) > 0)
				throw new Exception('Нельзя добавлять оси в непустое множество!');
			$this->axes[$axis->GetId()] = $axis;
			$this->axesValues[$axis->GetId()] = array();
			$this->axesValuesByInd[$axis->GetId()] = array();
			$this->fixedAxesValues[$axis->GetId()] = 0;
			if ($axis->GetAxisValuesCount() > $this->maxAxisValuesCount)
				$this->maxAxisValuesCount = $axis->GetAxisValuesCount();
			return $this;
		}

		/**
		 * Добавляет значение оси
		 *
		 * Следует отметить, что значение перед непосредственным добавлением проверяется в фильтре
		 * @param string $axisId идентификатор оси
		 * @param string $value добавляемое значение
		 * @return ISdmxDataSet объект-хозяин метода
		 */
		function AddAxisValue($axisId, $value) {
			if ( ! $this->filter->IsAxisValueSifted($axisId, $value))
				return $this;

			if ( ! isset($this->axesValues[$axisId][$value])) {
				$this->axesValues[$axisId][$value] = 0;
				$this->axesValuesByInd[$axisId][] = $value;
			}

			return $this;
		}

		/**
		 * Добавление используемого значения оси
		 * 
		 * Добавляет значение оси, а также обнвляет состояние оси (фиксированная/нефиксированная)
		 * Не проверяет сущетвование оси в множестве.
		 *
		 * @param string $axisId идентификатор оси
		 * @param string $value "сырое" значение
		 * @return объект-хозяин метода
		 */
		protected function AddUsedAxisValue($axisId, $value) {
			$this->AddAxisValue($axisId, $value);

			$this->axesValues[$axisId][$value]++;

			if (isset($this->fixedAxesValues[$axisId])) {
				if ($this->fixedAxesValues[$axisId] === 0)
					$this->fixedAxesValues[$axisId] = $value;
				elseif ($this->fixedAxesValues[$axisId] != $value)
					unset($this->fixedAxesValues[$axisId]);
			}
			return $this;
		}

		/**
		 * Получение итератора на массив осей
		 *
		 * @return Iterator итератор на массив осей
		 */
		function GetAxesIterator() {
			return new ArrayIterator($this->axes);
		}

		/**
		 * Получение количества осей
		 *
		 * @return int Количество осей
		 */
		function GetAxesCount() {
			return count($this->axes);
		}

		/**
		 * Массив с значениями фиксированных осей множества
		 * @var string[] массив в виде <var>[axisId => $axisValue]</var>
		 */
		protected $fixedAxesValues = array();

		/**
		 * Получение количества фиксированных осей
		 *
		 * @return int количество фиксированных осей множества
		 */ 
		function GetFixedAxesCount() {
			return count($this->fixedAxesValues);
		}

		/**
		 * Получение количества нефиксированных осей
		 *
		 * @return int количество нефиксированных осей множества
		 */ 
		function GetUnfixedAxesCount() {
			return count($this->axes) - count($this->fixedAxesValues);
		}

		/**
		 * Фиксированна ли ось
		 *
		 * Фиксированная ось -- это ось с одним значением.
		 *
		 * @param string $axisId идентификатор интересуемой оси
		 * @param mixed $default значение, которое будет возвращено в случае отсутствия оси
		 * @return mixed В случае наличия оси -- <var>bool</var> или <var>$default</var> в случае её отсутствия
		 */
		function IsAxisFixed($axisId, $default = false) {
			if (isset($this->axes[$axisId]))
				return isset($this->fixedAxesValues[$axisId]);
			else
				return $default;
		}

		/**
		 * Получение итератора на массив фиксированных осей
		 *
		 * @return Iterator итератор на фиксированные оси
		 */
		function GetFixedAxesIterator() {
			return new SdmxArrayDataSetFixedAxesIterator($this, true);
		}

		/**
		 * Получение итератора на массив нефиксированных осей
		 *
		 * @return Iterator итератор на нефиксированные оси
		 */
		function GetUnfixedAxesIterator() {
			return new SdmxArrayDataSetFixedAxesIterator($this, false);
		}

		/**
		 * Фильтр мноожества
		 * @var SdmxAxesSystemFilter
		 */
		protected $filter;

		/**
		 * Получение фильтра, используемого в множестве
		 *
		 * @return SdmxAxesSystemFilter фильтр множества
		 */
		function GetFilter() {
			return $this->filter;
		}

		/**
		 * Установка нового фильтра множества
		 *
		 * Звпрещено устанавливать фильтр на непустое множество!
		 * @throws Exception в случае попытки отфильтровать непустое множествоы
		 * @param SdmxAxesSystemFilter $filter новый фильтр множества
		 * @return ISdmxDataSet изменённое множество-хозяин метода
		 */
		function SetFilter(SdmxAxesSystemFilter $filter) {
			if (count($this->points) > 0)
				throw new Exception('Множество должно быть пустым!');

			$this->filter = $filter;

			foreach ($this->axesValuesByInd as $axisId => &$values) {
				foreach ($values as $ind => &$value) {
					if ($this->filter->IsAxisValueSifted($axisId, $value)) {
						unset($this->axesValues[$axisId][$value]);
						unset($this->axesValuesByInd[$axisId][$ind]);
					}
				}

			}

			return $this;
		}

		/**
		 * Фильтрация множества в новое множество
		 * 
		 * Копирует множество и применяет к нему данный фильтр
		 * @param SdmxAxesSystemFilter $filter Новый фильтр
		 * @return ISdmxDataSet новое множество, полученное путём фильтрации множества-хозяина метода
		 */
		function CopyWithFilter(SdmxAxesSystemFilter $filter) {
			$ret = new SdmxArrayDataSet($filter);

			foreach ($this->axesValuesByInd as $axisId => &$values) {
				foreach ($values as $value)
					$ret->AddAxisValue($axisId, $value);
			}

			foreach ($this->axes as $axis)
				$ret->AddAxis($axis);
			
			foreach ($this->points as $point)
				$ret->AddPoint($point);

			return $ret;
		}

		/**
		 * Моссив точек
		 *
		 * Массив точек в формате <var>[$coordinatesHash => $sdmxDataPoint]</var>
		 * @var SdmxDataPoint[]
		 */
		protected $points = array();

		/**
		 * Максимальное количество значений в осях
		 * @var int
		 */
		protected $maxAxisValuesCount = 0;

		/**
		 * Считает индекс точки в массиве $points
		 *
		 * Переменная $coordinates может быть <var>SdmxDataPoint</var>, или массивом в формате
		 * <var>[$axisId => $value, $axisId => $valueInd]</var>
		 * @param mixed $coordinates Координаты интересуемой точки
		 * @return string строка-индекс точки в массиве
		 */
		protected function CalculatePointHash($coordinates) {
			$ret = '';
			if (is_a($coordinates, 'SdmxDataPoint')) {
				foreach ($this->axes as $axisId => $axis) {
					$ret .= "&{$coordinates->GetCoordinate($axisId)->GetRawValue()}";
				}
			} else {
				foreach ($this->axes as $axisId => $axis) {
					if (is_string($coordinates[$axisId]))
						$ret .= "&{$coordinates[$axisId]}";
					elseif (is_numeric($coordinates[$axisId]))
						$ret .= "&{$this->GetAxisValueByIndex($axisId, $coordinates[$axisId])}";
					elseif (is_a($coordinates[$axisId], 'SdmxCoordinate'))
						$ret .= "&{$coordinates[$axisId]->GetRawValue()}";
				}
			}
			return $ret;
		}

		/**
		 * Получение итератора на массив значений оси
		 *
		 * Список значений какой-то оси в срезе может отличаться от полного.
		 * Функция возвращает итератор на массив со значениями (сырыми) оси конкретно этого объекта
		 *
		 * @param string $axisId идентификатор оси
		 * @param mixed $defаult Значение, которое вернётся в случае отсутствия оси
		 * @return mixed либо <var>Iterator</var> -- итератор на массив со значениями оси, либо <var>$default</var> при ошибке
		 */
		function GetAxesValuesIterator($axisId, $default = false) {
			if (isset($this->axesValues[$axisId]))
				return new ArrayIterator($this->axesValuesByInd[$axisId]);
			else
				return $default;
		}

		/**
		 * Получение значения фиксированной оси
		 *
		 * Функция возвращает единственное значение фиксиованной оси
		 *
		 * @param string $axisId Идентификатор оси, значение которой необходимо вернуть
		 * @param mixed $default Значение, которое будет возвращено в случае отсутствия такой фиксированной оси или если множество пустое
		 * @return mixed первое сырое значение оси (<var>string</var>) или <var>$default</var>, если она не была найдена
		 */
		function GetFixedAxisValue($axisId, $default = false) {
			if (isset($this->fixedAxesValues[$axisId]) && $this->fixedAxesValues[$axisId] !== null)
				return $this->fixedAxesValues[$axisId];
			else
				return $default;
		}



		/**
		 * Получение значения оси по индексу
		 *
		 * @param string $axisId идентификатор оси
		 * @param int $ind индекс значения
		 * @param mixed $default значение, которое будет возвращено в случае ошибки
		 * @return mixed "сырое" значение оси или <var>$default</var> в случае ошибки
		 */
		function GetAxisValueByIndex($axisId, $ind, $default = false) {
			if (isset($this->axesValuesByInd[$axisId]) && isset($this->axesValuesByInd[$axisId][$ind]))
				return $this->axesValuesByInd[$axisId][$ind];
			else
				return $default;
		}
		
		/**
		 * Получение количества значений оси
		 *
		 * @param string $axisId идентификатор оси
		 * @param mixed $default значение, которое вернётся при отсутствии оси
		 * @return mixed Количество значений (<var>int</var>) или <var>$default</var> в случае отсутствия таковой
		 */
		function GetAxisValuesCount($axisId, $default = false) {
			if (isset($this->axesValues[$axisId]))
				return count($this->axesValues[$axisId]);
			else
				return $default;
		}

		/**
		 * Добавление точки
		 *
		 * Все точки должны загоняться после добавления всех осей, а также все точки должны иметь коодринаты по всем осям
		 * DataSet'а (и, соответственно, только по ним).
		 *
		 * @throws Exception Если что-то не так с осями
		 * @param SdmxDataPoint $point дабавляемая точка
		 * @return ISdmxDataSet объект-хозяин метода
		 */
		function AddPoint(SdmxDataPoint $point) {
			if ( ! $this->filter->IsSifted($point))
				return $this;

			// Проверим оси. Точка должна иметь координаты по всем осям (и только по ним)
			foreach ($point as $axisId => $coord) {
				if ( ! $this->GetAxis($axisId, false) || $this->GetAxis($axisId) !== $coord->GetAxis())
					throw new Exception('Точки должны иметь координаты всех осей множества и никаких других!');
			}

			if ($this->GetAxesCount() !== $point->GetCoordinatesCount())
				throw new Exception('Точки должны иметь координаты всех осей множества и никаких других!');

			// Теперь загоним точку в множество
			$this->points[$this->CalculatePointHash($point)] = $point;

			foreach ($point as $axisId => $coord) {
				$this->AddUsedAxisValue($axisId, $coord->GetRawValue());
			}

			return $this;
		}

		/**
		 * Сортирует точки и возвращает итератор
		 * 
		 * Сортирует точки в множестве. Если массив <var>$axesOrder</var> задан, то будет задан приоритет осей при сортировке
		 * (т.е. сначала будет сравниваться ось, стоящая раньше в массиве, при равенстве -- далее). Если не задан, то он будет
		 * сформирован сам в соответствии с последовательностью добавления осей в объект
		 * При взятии срезов гарантируется, что сортироваться подмножества будут так же, как отсортировано исходное множество
		 *
		 * @param string[] $axesOrder массив с приоритетами осей при сравнении (или <var>null</var>, чтобы использовать очерёдность добавления осей)
		 * @return ISdmxDataSet Отсортированное множество точек (в данном случае -- оно само.)
		 */
		/*
		function SortPoints($axesOrder = null) {
			throw new Exception('SdmxArrayDataSet::Sort! stub!');
			if ($axesOrder) {
				$compareOrder = array();
				foreach ($axesOrder as $axisId) {
					if ( ! $this->IsAxisFixed(true))
						$compareOrder[] = $axisId;
				}
			} else {
				$compareOrder = array();
				foreach ($this->GetAxesIterator() as $axisId => $axis)
					$compareOrder[] = $axisId;
			}

			$this->QuickSortPoints(0, count($this->points), $compareOrder);
			return $this;
		}
		*/

		/**
		 * Сортировка точек
		 *
		 * Сортирует подмассив точек в множестве
		 *
		 * @param int $beginInd начальный элемент
		 * @param int $endInd индекс последнего эл-та + 1
		 * @param string[] $axesOrder оси, по которым надо сравнивать точки (в т.ч. их последовательность)
		 * @return void
		 */
		protected function QuickSortPoints($beginInd, $endInd, $axesOrder) {
			if ($endInd - $beginInd < 2)
				return;

			$left = $beginInd;
			$right = $endInd - 1;

			$central = $this->points[($beginInd + $endInd - 1)/2];
			while ($left <= $right) {
				// global $DEBUG;
				// echo $DEBUG . ' ';
				while ($left < $right && SdmxDataPoint::Compare($this->points[$left], $central, $axesOrder) < 0)
					++$left;
				while ($left < $right && SdmxDataPoint::Compare($central, $this->points[$right], $axesOrder) < 0)
					--$right;

				if ($left <= $right) {
					$tmp = $this->points[$left];
					$this->points[$left] = $this->points[$right];
					$this->points[$right] = $tmp;
					++$left;
					--$right;
				}
			}

			if ($left < $endInd)
				$this->QuickSortPoints($beginInd, $left, $axesOrder);
			if ($right > $beginInd)
				$this->QuickSortPoints($right, $endInd, $axesOrder);
			return;
		}

		/**
		 * Получение итератора на массив точек
		 *
		 * @return Iterator итератор на множество точек
		 */
		function GetPointsIterator() {
			return new ArrayIterator($this->points);
		}

		/**
		 * Получение первой точки множества
		 *
		 * Функция возвращает первую точку множества. Имеет смысл, когда она единственная
		 *
		 * @param mixed $default значение, которое будет возвращено в случае отсутствия точек в множестве
		 * @return SdmxDataPoint первая точка множества (или <var>$default</var>, если множество пусто)
		 */
		function GetFirstPoint($default = false) {
			if (count($this->points) > 0)
				return $this->GetPointsIterator()->current();
			else
				return $default;
		}

		/**
		 * Получение итератора на массив точек
		 *
		 * @return Iterator итератор на массив точек
		 */
		function GetIterator() {
			return new ArrayIterator($this->points);
		}

		/**
		 * Получение точки множества по координатам
		 *
		 * Ищет и возвращает точку с заданными координатами
		 * Набор координат задаётся либо в виде <var>SdmxDataPoint</var> с координатами, либо в виде
		 * массива в двух возможных вариантах: <var>[$axisId => $value, $axisId => $valueInd]</var>
		 * Индекс должен быть строго int'ом, значение -- строго строкой
		 * Должны быть все оси, иначе ничего не будет возвращено!
		 * @param mixed $coordinates Координаты искомой точки
		 * @param mixed $default Значение, которое будет возвращено, или, если такой точки нет, то <var>$default</var>
		 */
		function GetPoint($coordinates, $default = false) {
			$hash = $this->CalculatePointHash($coordinates);
			if (isset($this->points[$hash]))
				return $this->points[$hash];
			else
				return $default;
		}

		/**
		 * Очистка множества
		 *
		 * Удаляет все точки и все оси из множества (но если есть какие-то специфичные параметры, то они остаются)
		 *
		 * @return ISdmxDataSet объект-хозяин метода
		 */
		function Clear() {
			$this->axesValues = array();
			$this->axes = array();
			$this->points = array();
			$this->unfixedAxesCount = 0;
			return $this;
		}

		/**
		 * Конструктор
		 */
		function __construct() {
			$this->filter = new SdmxAxesSystemFilter(array());
		}

		function __DebugPrintAxes($printValues = true) {
			echo "Axes: <br>\n";
			foreach ($this->GetAxesIterator() as $axis) {
				$axis->__DebugPrint();
				if ($printValues) {
					echo "Used values: [";
					foreach ($this->GetAxesValuesIterator($axis->GetId()) as $val)
						echo "$val, ";
					echo "] <br>\n";
				}
				if ($this->IsAxisFixed($axis->GetId()))
					echo "FIXED<br>\n";
				else
					echo "UNFIXED<br>\n";
			}
			echo "<br>\n";
			return $this;
		}

		function __DebugPrintPoints() {
			echo "Points: <br>\n";
			foreach ($this->GetPointsIterator() as $point) {
				$point->__DebugPrint();
			}
			echo "<br>\n";
			return $this;
		}

		function __DebugPrint() {
			$this->__DebugPrintAxes()
			     ->__DebugPrintPoints();
			return $this;
		}
	}

?>
