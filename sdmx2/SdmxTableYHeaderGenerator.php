<?php
	/**
	 * Описание интерфейса <var>SdmxTableYHeaderGenerator</var>
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 2.0
	 */

	require_once('ISdmxTableRowGenerator.php');
	require_once('SdmxTableGenerator.php');

	/**
	 * Генератор шапки слева
	 *
	 * @package sdmx
	 * @version 2.0
	 */
	class SdmxTableYHeaderGenerator implements ISdmxTableRowGenerator {
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
			return $this->GetCellYInd() % $this->parentGenerator->GetHeaderYHeight($this->xInd);
		}

		/**
		 * Получение количества объединённых снизу ячеек, учитывая текущую
		 *
		 * Ячейки могут быть объединены. Функция возвращает число ячеек, с которыми объединена данная
		 * ячейка снизу, включая её саму (т.е. сколько ячеек под текущей объединено с ней + она сама)
		 * @return int количество ячеек снизу
		 */
		function GetMergedDown() {
			return $this->parentGenerator->GetHeaderYHeight($this->xInd) - $this->GetMergedUp();
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
			if ($this->parentGenerator->GetYAxesCount() == 0)
				return 'Значения:';
			else
				return $this->GetObject()->GetValue();
		}
		/**
		 * Получение объекта ячейки
		 *
		 * Это -- "сырое значение" для ячейки. В случае заголовков это null (в левом верхнем углу таблицы) или <var>SdmxCoordinate</var>,
		 * в случае ячейки со значением -- <var>SdmxDataPoint</var>
		 * @return mixed null, SdmxCoordinate или SdmxDataPoint
		 */
		function GetObject() {
			if ($this->parentGenerator->GetYAxesCount() == 0)
				return null;
			else
				return new SdmxCoordinate($this->parentGenerator->GetDataSet()->GetAxis($this->parentGenerator->GetYAxis($this->xInd)),
					                      $this->parentGenerator->GetDataSet()->GetAxisValueByIndex($this->parentGenerator->GetYAxis($this->xInd),
					                      	    $this->parentGenerator->GetYAxisValueIndex($this->GetCellYInd(), $this->xInd)));
		}

		/**
		 * Получение типа ячейки
		 * @return int SdmxTableRowGenerator::{ TABLE_CELL_TYPE_EDGE_HEADER |
		 *                                      TABLE_CELL_TYPE_X_HEADER |
		 *                                      TABLE_CELL_TYPE_Y_HEADER |
		 *                                      TABLE_CELL_TYPE_REGULAR_CELL }
		 */
		function GetType() {
			return SdmxTableRowGenerator::TABLE_CELL_TYPE_Y_HEADER;
		}

		/**
		 * Следующая ячейка
		 * @return void
		 */
		function next() {
			if ( ! $this->valid())
				return;

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
			return $this->xInd < $this->parentGenerator->GetHeaderColsCount();
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
