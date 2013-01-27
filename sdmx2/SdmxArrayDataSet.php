<?php
	/**
	 * Содержит описание класса <var>SdmxArrayDataSet</var>.
	 *
	 * Простейшая реализация <var>ISdmxDataSet</var>.
	 * 
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 1.0
	 */

	require_once('ISdmxDataSet.php');
	require_once('SdmxDataPoint.php');
	require_once('SdmxAxis.php');

	/**
	 * Итератор по осям с определённой "фиксированностью"
	 *
	 * @see SdmxArrayDataSet::axesValues
	 * @package sdmx
	 * @version 1.0
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
		 * Итератор прыгает по осям со значением <var>SdmxArrayAxis::IsFixed()</var>, равным <var>$areAxesFixed</var>
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

			while ($this->axesIterator->valid() && $this->IsCorrectIterator())
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
	 * Простейший DataSet
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
		 * Массив имеет вид: <var>['axisId' => ['value1', 'value2', ...] ]</var>, где 'value1', 'value2' и т.д. --
		 * реально используемые в множестве значения.
		 * Если у какой-то оси только одно значение, то эта ось обзывается "фиксированной"
		 *
		 * @var string
		 */
		protected $axesValues = array();

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
			$this->axes[$axis->GetId()] = $axis;
			$this->axesValues[$axis->GetId()] = array();
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
		 * Фиксированна ли ось
		 *
		 * Фиксированная ось -- это ось с одним значением.
		 *
		 * @param string $axisId идентификатор интересуемой оси
		 * @param mixed $default значение, которое будет возвращено в случае отсутствия оси
		 * @return mixed В случае наличия оси -- <var>bool</var> или <var>$default</var> в случае её отсутствия
		 */
		function IsAxisFixed($axisId, $default = false) {
			if (isset($this->axesValues[$axisId]))
				return (count($this->axesValues[$axisId]) == 1);
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

		protected $points = array();

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
		function GetValuesIterator($axisId, $default = false) {
			if (isset($this->axesValues[$axisId]))
				return new ArrayIterator($this->axesValues[$axisId]);
			else
				return $default
		}

		/**
		 * Получение количества значений оси
		 *
		 * @param string $axisId идентификатор оси
		 * @param mixed $default значение, которое вернётся при отсутствии оси
		 * @return mixed Количество значений (<var>int</var>) или <var>$default</var> в случае отсутствия таковой
		 */
		function GetValuesCount($axisId, $default = false) {
			if (isset($this->axesValues[$axisId]))
				return count($this->axesValues[$axisId]);
			else
				return $default	
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
		function AddPoint(SdmxDataPoint $point);

		/**
		 * Сортирует точки и возвращает итератор
		 * 
		 * Сортирует точки в множестве. Если массив <var>$axesOrder</var> задан, то будет задан приоритет осей при сортировке
		 * (т.е. сначала будет сравниваться ось, стоящая раньше в массиве, при равенстве -- далее). Если не задан, то он будет
		 * сформирован сам в соответствии с последовательностью добавления осей в объект
		 *
		 * @param string[] $axesOrder массив с приоритетами осей при сравнении (или <var>null</var>, чтобы использовать очерёдность добавления осей)
		 * @return Iterator итератор на множество значений (эквивалентно вызову <var>GetPointsIterator()</var>)
		 */
		function SortPoints($axesOrder = null);

		/**
		 * Получение итератора на массив точек
		 *
		 * @return Iterator итератор на множество точек
		 */
		function GetPointsIterator();

		/**
		 * Получение среза по оси
		 *
		 * Возвращает ассоциативный массив вида <var>['&lt;сырое значение>' => ISdmxDataSet()]</var>, где в качестве индексов
		 * выступают все сырые значения оси с идентификатором <var>$axisId</var>, а в каждом IDataSet'е
		 * находятся все точки из делимого множества с значением оси <var>$axisId</var>, равным индексу в массиве
		 *
		 * @param string $axisId идентификатор оси, по которой произойдёт деление
		 * @return ISdmxDataSet[] массив с множествами
		 */
		function GetSlice($axisId);

		function __DebugPrint();
	}
?>
