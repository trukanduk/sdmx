<?php
	/**
	 * Описание интерфейса <var>SdmxTableXHeaderGenerator</var>
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 1.0
	 */

	require_once('ISdmxTableRowGenerator.php');
	require_once('SdmxTableGenerator.php');

	/**
	 * Генератор верхней шапки
	 *
	 * @package sdmx
	 * @version 1.0
	 */
	class SdmxTableXHeaderGenerator implements ISdmxTableRowGenerator {
		/**
		 * Родительский итератор
		 * @var SdmxTableGenerator
		 */ 
		protected $parentGenerator;

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
			return $this->xInd + $this->parentGenerator->GetHeaderColsCount();
		}

		/**
		 * Получение индекса строки в таблице (с учётом шапок)
		 * @return int индекс строки в таблице
		 */
		function GetYInd() {
			return $this->parentGenerator->GetYInd();
		}

		protected function GetCellWidth() {
			return $this->parentGenerator->GetHeadersXWidth($this->parentGenerator->GetYInd());
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
			return $this->xInd % $this->GetCellWidth();
		}
		/**
		 * Получение количества объединённых справа ячеек, учитывая текущую
		 *
		 * Ячейки могут быть объединены. Функция возвращает число ячеек, с которыми объединена данная
		 * ячейка справа (т.е. сколько ячеек справа от текущей объединено с ней + она сама)
		 * @return int количество ячеек справа
		 */
		function GetMergedRight() {
			return $this->GetCellWidth() - $this->GetMergedLeft();
		}

		/**
		 * Получение значения ячейки
		 * @return string значение, которое может быть записано в ячейку
		 */
		function GetValue() {
			if ($this->parentGenerator->GetXAxesCount() == 0)
				return 'Значения:';
			else
				return $this->GetObject()->GetValue();
		}
		/**
		 * Получение объекта ячейки
		 *
		 * Это -- "сырое значение" для ячейки. В данном случае это 
		 * @return mixed null или SdmxCoordinate
		 */
		function GetObject() {
			if ($this->parentGenerator->GetXAxesCount() == 0)
				return null;
			else
				return new SdmxCoordinate($this->parentGenerator->GetDataSet()->GetAxis($this->parentGenerator->GetXAxis($this->GetYInd())),
				                          $this->parentGenerator->GetDataSet()->GetAxisValueByIndex($this->parentGenerator->GetXAxis($this->GetYInd()),
				                         	     $this->parentGenerator->GetXAxisValueIndex($this->xInd, $this->GetYInd())));
		}

		/**
		 * Получение типа ячейки
		 * @return int SdmxTableRowGenerator::{ TABLE_CELL_TYPE_EDGE_HEADER |
		 *                                      TABLE_CELL_TYPE_X_HEADER |
		 *                                      TABLE_CELL_TYPE_Y_HEADER |
		 *                                      TABLE_CELL_TYPE_REGULAR_CELL }
		 */
		function GetType() {
			return SdmxTableRowGenerator::TABLE_CELL_TYPE_X_HEADER;
		}

		/**
		 * Следующая ячейка
		 * @return void
		 */
		function next() {
			$this->xInd++;
		}
		/**
		 * Сброс итератора
		 * @return void
		 */
		function rewind() {
			$this->xInd = 0;
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
		}
	}
?>
