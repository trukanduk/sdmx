<?php
	/**
	 * Описание интерфейса <var>SdmxTableRegularCellsGenerator</var>
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 1.0
	 */

	require_once('ISdmxTableRowGenerator.php');
	require_once('SdmxTableGenerator.php');

	/**
	 * Генератор ячеек со значениями
	 *
	 * @package sdmx
	 * @version 1.0
	 */
	class SdmxTableRegularCellsGenerator implements ISdmxTableRowGenerator {
		/**
		 * Стек срезов множества точек
		 * @var SdmxTableGeneratorSlicesStack
		 */
		protected $slicesStack;

		/**
		 * Родительский итератор
		 * @var SdmxTableGenerator
		 */ 
		protected $parentGenerator;

		/**
		 * Итератор по подмножеству
		 * @var Iterator
		 */
		protected $pointsIterator;

		/**
		 * Проверка корректности итератора по подмножеству
		 *
		 * В подмножестве может отсутствовать точка, соответствующая ячейке.
		 * Метод проверяет, соответствует ли текущая точка (текущий итератор) ячейке
		 * @return bool
		 */
		protected function IsPointsIteratorCorrect() {
			$ret = $this->pointsIterator->valid();
			for ($axisInd = 0; $ret &&  $axisInd < $this->parentGenerator->GetXAxesCount(); ++$axisInd) {
				$ret &= ($this->parentGenerator->GetDataSet()->GetAxisValueByIndex($this->parentGenerator->GetXAxis($axisInd),
					                                                           $this->parentGenerator->GetXAxisValueIndex($this->xInd, $axisInd)) ==
				         $this->pointsIterator->current()->GetCoordinate($this->parentGenerator->GetXAxis($axisInd))->GetRawValue());
			}
			return $ret;
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
			if ($this->IsPointsIteratorCorrect())
				return $this->pointsIterator->current();
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
			if ($this->IsPointsIteratorCorrect())
				$this->pointsIterator->next();
			$this->xInd++;
		}
		/**
		 * Сброс итератора
		 * @return void
		 */
		function rewind() {
			$this->xInd = 0;
			$this->pointsIterator->rewind();
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
		function __construct(SdmxTableGenerator $parentGenerator, SdmxTableGeneratorSlicesStack $slicesStack) {
			$this->parentGenerator = $parentGenerator;
			$this->slicesStack = $slicesStack;
			if ( ! is_null($slicesStack->GetLastSubset()))
				$this->pointsIterator = $slicesStack->GetLastSubset()->GetPointsIterator();
			else
				$this->pointsIterator = new ArrayIterator(array()); // всегда не валидный итератор. В дальнейшем такой ситуации не будет вообще

			$this->xInd = 0;
		}
	}
?>
