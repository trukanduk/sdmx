<?php
	/**
	 * Описание класса <var>SdmxTableGenerator</var> -- итератора по строкам для генерации таблиц
	 *
	 * Файл содержит описание класса <var>SdmxTableGenerator</var>, генерирующего таблицы на основе <var>SdmxDataSet</var>
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 1.0
	 */

	require_once('SdmxData.php');
	require_once('ISdmxDataSet.php');

	require_once('SdmxTableRowGenerator.php');

	/**
	 * Стек срезов множества точек
	 *
	 * Служебный класс, используемый для взаимодействия итераторов таблицы.
	 *
	 * @package sdmx
	 * @version 1.0
	 */
	class SdmxTableGeneratorSlicesStack {
		/**
		 * Множество точек, на основе которых генерятся срезы
		 * @var ISdmxDataSet
		 */
		protected $dataSet;

		/**
		 * Получение множества точек
		 * @return ISdmxDataSet Множество точек данного объекта
		 */
		function GetDataSet() {
			return $this->dataSet;
		}

		/**
		 * Установка множества точек
		 * @param ISdmxDataSet $dataSet новое множество
		 * @return SdmxTableGeneratorSlicesStack объект-хозяин метода
		 */
		function SetDataSet(ISdmxDataSet $dataSet) {
			$this->dataSet = $dataSet;
			return $this;
		}

		/**
		 * Массив осей, отложенных по вертикали
		 * @var string[]
		 */
		protected $yAxes = array();

		/**
		 * Установка массива осей
		 *
		 * Устанавливает весь массив осей
		 * @param string[] новый массив осей
		 * @return SdmxTableGeneratorSlicesStack объект-хозяин метода
		 */
		function SetYAxes(&$yAxes) {
			$this->yAxes = $yAxes;
			return $this;
		}

		/**
		 * Массив срезов множества
		 *
		 * Пусть i-й ячейке массива лежит некий срез. В массиве <var>$this->slicesValues</var> в i-й ячейке лежит
		 * значение, которое указывает на то, какое подмножество будет разбито на срезы в следующей ячейке, т.е.
		 * в (i + 1)-й ячейке хранится срез подмножества, лежащего в ячейке <var>[i][$this->slicesValues[i]]</var>
		 * @var ISdmxDataSet[][]
		 */
		protected $slices = array();

		/**
		 * Массив значений, указывающих на разбиваемые подмножества
		 *
		 * см. <var>$this->slices</var> (Массив срезов множества)
		 * @var string[]
		 */
		protected $slicesValues = array();

		/**
		 * Получение подмножества
		 *
		 * Возвращает подмножество, а так же кеширует весь срез. По сути получается что-то вроде рекурсивного стека
		 * Для понимания происходящего надо осознать, что эта функция будет вызываться последловательно на строке
		 * Точнее писать влом
		 * @param $colInd Индекс столбца (т.е. индекс оси в <var>$this->yAxes</var>, которую надо расхреначить)
		 * @param $valueInd индекс значения, которое должно зафиксироваться
		 * @return ISdmxDataSet интересующее подмножество
		 */
		function GetSubset($colInd, $valueInd) {
			// получим новый срез, если его не было
			if ( ! isset($this->slices[$colInd])) {
				if (isset($this->slices[$colInd - 1][$this->slicesValues[$colInd - 1]]))
					$this->slices[$colInd] = $this->slices[$colInd - 1][$this->slicesValues[$colInd - 1]]->GetSlice($this->yAxes[$colInd]);
				else
					$this->slices[$colInd] = array();
			}

			// Если текущее значение отличается от существующего (или его не было), то обнулим нахрен всё после.
			if (( ! isset($this->slicesValues[$colInd])) ||
				$this->dataSet->GetAxisValueByIndex($this->yAxes[$colInd], $valueInd) != $this->slicesValues[$colInd]) {
				for ($i = $colInd + 1; $i < count($this->yAxes); ++$i) {
					unset($this->slices[$i]);
					unset($this->slicesValues[$i]);
				}
			}

			// Теперь установим, какое подмножество из среза мы сейчас взяли, и, наконец, вернём его
			$this->slicesValues[$colInd] = $this->dataSet->GetAxisValueByIndex($this->yAxes[$colInd], $valueInd);
			return $this->slices[$colInd][$this->slicesValues[$colInd]];
		}

		/**
		 * Инициализация массива срезов
		 *
		 * @return SdmxTableGeneratorSlicesStack объект-хозяин метода
		 */
		function InitSlices() {
			if (count($this->yAxes) == 0)
				return $this;

			$this->slices = array($this->dataSet->Split($this->yAxes[0]));
			$this->slicesValues = array();

			return $this;
		}

		/**
		 * Получение последнего нагенерированного подмножества
		 * @return ISdmxDataSet
		 */
		function GetLastSubset() {
			if (count($this->yAxes) != 0)
				return $this->slices[count($this->slices) - 1][$this->slicesValues[count($this->slicesValues) - 1]];
			else
				return $this->dataSet;
		}
		/**
		 * Конструктор
		 *
		 * @param ISdmxDataSet $dataSet множество точек
		 * @param string[] $yAxes множество осей слева у таблицы
		 */
		function __construct(ISdmxDataSet $dataSet, &$yAxes) {
			$this->dataSet = $dataSet;
			$this->yAxes = $yAxes;

			$this->InitSlices();
		}

		function __DebugPrint() {
			echo "SLICES STACK:<br>\n";
			for ($i = 0; $i < count($this->slicesStack); $i++) {
				echo $this->slicesValues[$i] . ' ';
			}

		}
	}

	/**
	 * Генератор таблиц
	 *
	 * Класс-итератор по строкам таблицы, сгенерированной на основе SdmxDataSet
	 * 
	 * @package sdmx
	 * @version 1.0
	 */
	class SdmxTableGenerator implements Iterator {
		/**
		 * Множество точек
		 * @var ISdmxDataSet
		 */
		protected $dataSet;

		/**
		 * Получение множества точек
		 * @return ISdmxDataSet множество точек, на котором строится таблица
		 */
		function GetDataSet() {
			return $this->dataSet;
		}

		/**
		 * Стек срезов множества точек
		 * @var SdmxTableGeneratorSlicesStack
		 */
		protected $slicesStack;

		/**
		 * Создаёт и инициализирует стек со срезами
		 *
		 * Вызывать после установки множества точек и массивов осей
		 * @return SdmxTableGenerator Объект-хозяин метода
		 */
		function InitSlicesStack() {
			$this->slicesStack = new SdmxTableGeneratorSlicesStack($this->dataSet, &$this->yAxes);
			return $this;
		}

		/**
		 * Индекс строки
		 * @var int
		 */
		protected $yInd = 0;

		/**
		 * Получение индекса строки
		 * @return int индекс строки
		 */
		function GetYInd() {
			return $this->yInd;
		}

		/**
		 * Массив осей, отложенных по горизонтали
		 * 
		 * Самая толстая ось -- нулевая
		 * @var string[]
		 */
		protected $xAxes = array();

		/**
		 * Получений массива осей, отложенных по горизонтали
		 * @return string[]
		 */
		function GetXAxes() {
			return $this->xAxes;
		}

		/**
		 * Получение идентификатора горизонтальной оси по индексу
		 * @param int $ind индекс оси
		 * @param mixed $default значение по умолчанию (возвращается при ошибке)
		 * @return mixed идентификатор оси (<var>string</var>) или <var>$default</var> при ошибке
		 */
		function GetXAxis($ind, $default = false) {
			if (isset($this->xAxes[$ind]))
				return $this->xAxes[$ind];
			else
				return $default;
		}

		/**
		 * Получение количества горизонтальных осей
		 * @return int
		 */
		function GetXAxesCount() {
			return count($this->xAxes);
		}

		/**
		 * Массив осей, отложенных по вертикали
		 * 
		 * Самая толстая ось -- нулевая
		 * @var string[]
		 */
		protected $yAxes = array();

		/**
		 * Получений массива осей, отложенных по вертикали
		 * @return string[]
		 */
		function GetYAxes() {
			return $this->yAxes;
		}

		/**
		 * Получение идентификатора вертикальной оси по индексу
		 * @param int $ind индекс оси
		 * @param mixed $default значение по умолчанию (возвращается при ошибке)
		 * @return mixed идентификатор оси (<var>string</var>) или <var>$default</var> при ошибке
		 */
		function GetYAxis($ind, $default = false) {
			if (isset($this->yAxes[$ind]))
				return $this->yAxes[$ind];
			else
				return $default;
		}

		/**
		 * Получение количества вертикальных осей
		 * @return int
		 */
		function GetYAxesCount() {
			return count($this->yAxes);
		}

		/**
		 * Количество ячеек со значениями по горизонтали
		 * @var int
		 */
		protected $cellsXCount = 1;

		/**
		 * Получение количества ячеек со значениями по горизонтали
		 * @return int количество ячеек со значениями по горизонтали
		 */
		function GetCellsXCount() {
			return $this->cellsXCount;
		}

		/**
		 * Получение количества столбцов в шапке слева
		 * @return int
		 */
		function GetHeaderColsCount() {
			if (count($this->yAxes) == 0) {
				if (count($this->xAxes) == 0)
					return 0;
				else
					return 1;
			} else
				return count($this->yAxes);
		}

		/**
		 * Получение ширины таблицы
		 * @return int Количество ячеек по горизонтали (считая шапки)
		 */
		function GetTableWidth() {
			if (count($this->xAxes) == 0 && count($this->yAxes) == 0)
				return 0;
			else
				return $this->cellsXCount + $this->GetHeaderColsCount();
		}

		/**
		 * Количество ячеек со значениями по вертикали
		 * @var int
		 */
		protected $cellsYCount = 1;

		/**
		 * Получение количества ячеек со значениями по вертикали
		 * @return int
		 */
		function GetCellsYCount() {
			return $this->cellsYCount;
		}

		function GetHeaderRowsCount() {
			if (count($this->xAxes) == 0) {
				if (count($this->yAxes) == 0)
					return 0;
				else
					return 1;
			} else
				return count($this->xAxes);
		}

		/**
		 * Получение высоты таблицы
		 * @return int
		 */
		function GetTableHeight() {
			if (count($this->yAxes) == 0 && count($this->xAxes) == 0)
				return 0;
			else
				return $this->cellsYCount + $this->GetHeaderRowsCount();
		}

		/**
		 * Массив с размерами ячеек горизонтальной шапки
		 * @var int[]
		 */
		protected $headersXWidths = array();

		/**
		 * Получение ширины ячейки шапки
		 * @param int $yInd индекс строки (или индекс оси в <var>$xAxes</var>)
		 * @param mixed $default значение, которое вернётся в случае отсутствия такого значения
		 * @return int размер ячейки
		 */
		function GetHeadersXWidth($yInd, $default = false) {
			if (count($this->xAxes) == 0) {
				if ($yind == 0 && count($this->yAxes) != 0)
					return 1;
				else
					return $defalt;
			} else {
				if (isset($this->headersXWidths[$yInd]))
					return $this->headersXWidths[$yInd];
				else
					return $default;
			}
		}

		/**
		 * Массив с размерами вертикальной шапки
		 * @var int[]
		 */
		protected $headersYHeights = array();

		/**
		 * Получение высоты ячейки шапки
		 * @param int $xInd индекс строки (или индекс оси в <var>$yAxes</var>)
		 * @param mixed $default значение, которое вернётся в случае отсутствия такого значения
		 * @return int размер ячейки
		 */
		function GetHeaderYHeight($xInd, $default = false) {
			if (count($this->yAxes) == 0) {
				if ($xInd == 0 && count($this->yAxes) != 0)
					return 1;
				else
					return $default;
			} else {
				if (isset($this->headersYHeights[$xInd]))
					return $this->headersYHeights[$xInd];
				else
					return $default;
			}
		}

		/**
		 * Получение индекса значения горизонтальной оси на столбце
		 * @param int $xInd индекс столбца (без учёта шапок)
		 * @param int $axisInd индекс оси в <var>$$this->xAxes</var>
		 * @param mixed $default значение, которое будет возвращено в случае ошибки
		 * @return mixed индекс значения (<var>int</var>) или <var>$default</var> при ошибке
		 */
		function GetXAxisValueIndex($xInd, $axisInd, $default = false) {
			if ($xInd < 0 || $xInd >= $this->cellsXCount || $axisInd >= count($this->xAxes))
				return $default;
			else
				return $xInd / $this->headersXWidths[$axisInd] % $this->dataSet->GetAxisValuesCount($this->xAxes[$axisInd]);
		}

		/**
		 * Получение индекса значения вертикальной оси на строке
		 * @param int $yInd индекс строки (без учёта шапок)
		 * @param int $axisInd индекс оси в <var>$this->yAxes</var>
		 * @param mixed $default значение, которое будет возвращено в случае ошибки
		 * @return mixed индекс значения (<var>int</var>) или <var>$default</var> при ошибке
		 */
		function GetYAxisValueIndex($yInd, $axisInd, $default = false) {
			if ($yInd < 0 || $yInd >= $this->cellsYCount || $axisInd >= count($this->yAxes))
				return $default;
			else
				return $yInd / $this->headersYHeights[$axisInd] % $this->dataSet->GetAxisValuesCount($this->yAxes[$axisInd]);
		}

		/**
		 * Следующий итератор
		 * @return void
		 */
		function next() {
			++$this->yInd;
		}

		/**
		 * Обнуление итератора
		 * @return void
		 */
		function rewind() {
			$this->yInd = 0;
			$this->InitSlicesStack();
		}

		/**
		 * Проверка на валидость
		 * @return bool
		 */
		function valid() {
			//return ($this->yInd < $this->GetHeaderRowsCount());
			return ($this->yInd < $this->GetTableHeight());
		}

		/**
		 * Получение итератора на текущую строку
		 * @return SdmxTableRowGenerator
		 */
		function current() {
			return new SdmxTableRowGenerator($this, $this->slicesStack);
		}

		/**
		 * Получение ключа строки -- её индекса
		 * @return int
		 */
		function key() {
			return $this->GetYInd();
		}

		/**
		 * Конструктор
		 * @param ISdmxDataSet $dataSet Множество точек, которое надо запихнуть в таблицу
		 * @param string[] $xAxes идентификаторы осей, отложенных по горизонтали (шапка сверху)
		 * @param string[] $yAxes идентификаторы осей, отложенных по вертикали (шапка слева)
		 * @return bool <var>false</var> при ошибке
		 */
		function __construct(ISdmxDataSet $dataSet, $xAxes, $yAxes) {
			// Смержим два массива для удобства
			$sortOrder = array_merge($yAxes, $xAxes);
			// Проверим, все ли оси есть в множестве
			foreach ($sortOrder as $axisId) {
				if ($dataSet->GetAxis($axisId, false) === false)
					return false;
			}

			// Проверим, все ли _нефиксированные_ оси будут использоваться
			foreach ($dataSet->GetUnfixedAxesIterator() as $axisId => $axis) {
				if ( ! in_array($axisId, $sortOrder))
					return false;
			}

			// Проинициализируем поля
			$this->dataSet = $dataSet->SortPoints($sortOrder);
			$this->xAxes = $xAxes;
			$this->yAxes = $yAxes;
			$this->yInd = 0;

			// Проинициализируем ширины/высоты ячеек и ширину/высоту таблицы
			if (count($xAxes) > 0) {
				$this->headersXWidths[count($xAxes) - 1] = 1;
				$this->cellsXCount = $this->dataSet->GetAxisValuesCount($xAxes[0]);
				for ($i = count($xAxes) - 1; $i > 0; --$i) {
					$this->headersXWidths[$i - 1] = $this->headersXWidths[$i]*$this->dataSet->GetAxisValuesCount($xAxes[$i]);
					$this->cellsXCount *= $this->dataSet->GetAxisValuesCount($xAxes[$i]);
				}
			} else {
				$this->headersXWidths[0] = 1;
				$this->cellsXCount = 1;
			}

			if (count($yAxes) > 0) {
				$this->headersYHeights[count($yAxes) - 1] = 1;
				$this->cellsYCount = $this->dataSet->GetAxisValuesCount($yAxes[0]);
				for ($i = count($yAxes) - 1; $i > 0; --$i) {
					$this->headersYHeights[$i - 1] = $this->headersYHeights[$i]*$this->dataSet->GetAxisValuesCount($yAxes[$i]);
					$this->cellsYCount *= $this->dataSet->GetAxisValuesCount($yAxes[$i]);
				}
			} else {
				$this->headersYHeights[0] = 1;
				$this->cellsYCount = 1;
			}

			// Наконец, проинициализируем стек срезов
			$this->InitSlicesStack();
		}
	}
?>
