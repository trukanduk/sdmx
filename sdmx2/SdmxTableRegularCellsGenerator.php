<?php
	/**
	 * Описание интерфейса <var>SdmxTableRegularCellsGenerator</var>
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 2.0
	 */

	require_once('ISdmxTableRowGenerator.php');
	require_once('SdmxTableGenerator.php');

	/**
	 * Генератор ячеек со значениями
	 *
	 * @package sdmx
	 * @version 2.0
	 */
	class SdmxTableRegularCellsGenerator implements ISdmxTableRowGenerator {
		/**
		 * Родительский итератор
		 * @var SdmxTableGenerator
		 */ 
		protected $parentGenerator;

		/**
		 * Текущие координаты ячейки
		 * @var mixed[]
		 */
		protected $coordinates = array();

		/**
		 * Переход к следующему набору координат
		 * @return SdmxTableRegularCellsGenerator объект-хозяин метода
		 */
		protected function NextCoordinates() {
			$i = $this->parentGenerator->GetXAxesCount() - 1;
			while ($i >= 0 && ++$this->coordinates[$this->parentGenerator->GetXAxis($i)] ==
					$this->parentGenerator->GetDataSet()->GetAxisValuesCount($this->parentGenerator->GetXAxis($i))) {
				$this->coordinates[$this->parentGenerator->GetXAxis($i)] = 0;
				--$i;
			}
			return $this;
		}

		/**
		 * Инициализация массива координат
		 * @return SdmxTableRegularCellsGenerator объект-хозяин метода
		 */
		protected function InitCoordinates() {
			for ($i = 0; $i < $this->parentGenerator->GetXAxesCount(); ++$i)
				$this->coordinates[$this->parentGenerator->GetXAxis($i)] = 0;

			for ($i = 0; $i < $this->parentGenerator->GetYAxesCount(); ++$i)
				$this->coordinates[$this->parentGenerator->GetYAxis($i)] = $this->parentGenerator->GetDataSet()->
					GetAxisValueByIndex($this->parentGenerator->GetYAxis($i), $this->parentGenerator->GetYAxisValueIndex($this->GetCellYInd(), $i), 0);
			foreach ($this->parentGenerator->GetDataSet()->GetFixedAxesIterator() as $axisId => $axis) {
				if ( ! isset($this->coordinates[$axisId]))
					$this->coordinates[$axisId] = $this->parentGenerator->GetDataSet()->GetAxisValueByIndex($axisId, 0, 0);
			}
			//echo "!!! "; var_dump($this->coordinates); echo " !!!\n"; echo "{$this->parentGenerator->GetYAxesCount()} \n";
		}

		/**
		 * Индекс ячейки
		 * @var int
		 */
		protected $xInd = 0;

		/**
		 * Получение индекса ячейки в строке (с учётом шапок)
		 * @return int индекс ячейки в строке
		 */
		function GetXInd() {
			return $this->xInd;
		}

		/**
		 * Получение индекса строки в таблице (с учётом шапок)
		 * @return int индекс строки в таблице
		 */
		function GetYInd() {
			return $this->parentGenerator->GetYInd();
		}

		/**
		 * Получение индекса строки без учёта шапки
		 * @return int
		 */
		protected function GetCellYInd() {
			return ($this->GetYInd() - $this->parentGenerator->GetHeaderRowsCount());
		}

		/**
		 * Получение количества объединённых сверху ячеек
		 *
		 * Ячейки могут быть объединены. Функция возвращает число ячеек, с которыми объединена данная
		 * ячейка сверху (т.е. сколько ячеек над текущей объединено с ней)
		 * @return int количество ячеек сверху
		 */
		function GetMergedUp() {
			return 0;
		}

		/**
		 * Получение количества объединённых снизу ячеек, учитывая текущую
		 *
		 * Ячейки могут быть объединены. Функция возвращает число ячеек, с которыми объединена данная
		 * ячейка снизу, включая её саму (т.е. сколько ячеек под текущей объединено с ней + она сама)
		 * @return int количество ячеек снизу
		 */
		function GetMergedDown() {
			return 1;
		}
		/**
		 * Получение количества объединённых слева ячеек
		 *
		 * Ячейки могут быть объединены. Функция возвращает число ячеек, с которыми объединена данная
		 * ячейка слева (т.е. сколько ячеек слева от текущей объединено с ней)
		 * @return int количество ячеек слева
		 */
		function GetMergedLeft() {
			return 0;
		}
		/**
		 * Получение количества объединённых справа ячеек, учитывая текущую
		 *
		 * Ячейки могут быть объединены. Функция возвращает число ячеек, с которыми объединена данная
		 * ячейка справа (т.е. сколько ячеек справа от текущей объединено с ней + она сама)
		 * @return int количество ячеек справа
		 */
		function GetMergedRight() {
			return 1;
		}

		/**
		 * Получение значения ячейки
		 * @return string значение, которое может быть записано в ячейку
		 */
		function GetValue() {
			if ($this->GetObject() === null)
				return '';
			else
				return $this->GetObject()->GetValue();
		}
		/**
		 * Получение объекта ячейки
		 *
		 * Это -- "сырое значение" для ячейки. В данном случае это <var>SdmxDataPoint</var> или <var>null</var>
		 * (если нет точки, соответствующей ячейке)
		 * @return mixed null, SdmxCoordinate или SdmxDataPoint
		 */
		function GetObject() {
			/*
			$coordinates = array();
			for ($i = 0; $i < $this->parentGenerator->GetXAxesCount(); ++$i)
				$coordinates[$this->parentGenerator->GetXAxis($i)] = $this->parentGenerator->GetXAxisValueIndex($this->xInd, $i);
			for ($i = 0; $i < $this->parentGenerator->GetYAxesCount(); ++$i)
				$coordinates[$this->parentGenerator->GetYAxis($i)] = $this->parentGenerator->GetYAxisValueIndex($this->GetCellYInd(), $i);
			foreach ($this->parentGenerator->GetDataSet()->GetFixedAxesIterator() as $axisId => $axis)
				if ( ! isset($coordinates[$axisId]))
					$coordinates[$axisId] = $this->parentGenerator->GetDataSet()->GetFixedAxisValue($axisId);
			//var_dump($coordinates);
			*/
			//var_dump(&$this->coordinates);
			$point = $this->parentGenerator->GetDataSet()->GetPoint(&$this->coordinates, false);
			if ($point !== false)
				return $point;
			else
				return null;
		}

		/**
		 * Получение типа ячейки
		 * @return int SdmxTableRowGenerator::{ TABLE_CELL_TYPE_EDGE_HEADER |
		 *                                      TABLE_CELL_TYPE_X_HEADER |
		 *                                      TABLE_CELL_TYPE_Y_HEADER |
		 *                                      TABLE_CELL_TYPE_REGULAR_CELL }
		 */
		function GetType() {
			return SdmxTableRowGenerator::TABLE_CELL_TYPE_REGULAR_CELL;
		}

		/**
		 * Следующая ячейка
		 * @return void
		 */
		function next() {
			$this->NextCoordinates();
			$this->xInd++;
		}
		/**
		 * Сброс итератора
		 * @return void
		 */
		function rewind() {
			$this->xInd = 0;
			$this->InitCoordinates();
		}

		/**
		 * Получение значения итератора
		 * @return string значение ячейки
		 */
		function current() {
			return $this->GetValue();
		}
		/**
		 * Получение ключа итератора -- тип ячейки
		 * @return int тип ячейки
		 */
		function key() {
			return $this->GetType();
		}
		/**
		 * Рабочий ли итератор
		 * @return bool рабочий ли итератор
		 */
		function valid() {
			return $this->xInd < $this->parentGenerator->GetCellsXCount();
		}

		/**
		 * Конструктор
		 * @param SdmxTableGenerator $parentGenerator родительский генератор
		 */
		function __construct(SdmxTableGenerator $parentGenerator) {
			$this->parentGenerator = $parentGenerator;

			$this->xInd = 0;
			$this->InitCoordinates();
		}
	}
?>
